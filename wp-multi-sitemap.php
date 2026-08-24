<?php
/**
 * Script para gerar sitemap de WordPress Multisite (Versão Final com Verificação de Site Público)
 * 
 * Instruções:
 * 1. Salve este arquivo como wp-multi-sitemap-2.php na raiz do WordPress.
 * 2. Execute via CLI: php wp-multi-sitemap-2.php
 */

// 1. Simula ambiente HTTP para evitar avisos de plugins de redirecionamento no CLI
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'igc.usp.br';
    $_SERVER['SERVER_NAME'] = 'igc.usp.br';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['HTTPS'] = 'on'; // Força reconhecimento de HTTPS pelo WP
    error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);
    ini_set('display_errors', '1');
}

// 2. Carrega o WordPress usando getcwd() (à prova de falhas de cópia/underscores)
$wp_root = getcwd();
require_once($wp_root . '/wp-load.php');

if (!is_multisite()) {
    die("Erro: Este script requer uma instalação WordPress Multisite.\n");
}

global $wpdb;

$config = [
    'prioridades' => [
        'raiz_principal' => 1.0,
        'raiz_especial'  => 0.9,
        'raiz_normal'    => 0.8,
        'pagina'         => 0.7,
        'post'           => 0.6,
        'evento'         => 0.5,
        'categoria'      => 0.4,
    ],
    'raizes_especiais' => [
        'graduacao', 'posgraduacao', 'cpq', 
        'culturaextensao', 'inclusaoepertencimento', 'gmg', 'gaa'
    ],
    'pasta_sitemap' => $wp_root . '/sitemap',
];

if (!file_exists($config['pasta_sitemap'])) {
    mkdir($config['pasta_sitemap'], 0755, true);
}

echo "=== Iniciando Geração de Sitemap Multisite ===\n";

// CAMADA 1 DE SEGURANÇA: Pega APENAS sites públicos, não arquivados, não spam e não deletados
$sites = get_sites([
    'number'   => 0,
    'public'   => 1, // <--- GARANTIA: Apenas sites públicos
    'archived' => 0,
    'spam'     => 0,
    'deleted'  => 0,
]);

echo "Subsites públicos e ativos encontrados: " . count($sites) . "\n\n";

$sitemap_files = [];
$sites_processados = 0;
$sites_pulados = 0;

// URL base do site principal forçada em HTTPS para o index
$main_site_url = untrailingslashit(get_home_url(1, '/', 'https'));

foreach ($sites as $site) {
    // CAMADA 2 DE SEGURANÇA: Verificação extra no loop
    if ( ! $site->public ) {
        echo "[PULADO] Blog {$site->blog_id} ignorado (não é público).\n";
        $sites_pulados++;
        continue;
    }

    switch_to_blog($site->blog_id);
    
    // Força a URL correta do subsite em HTTPS
    $home_url = untrailingslashit(get_home_url($site->blog_id, '/', 'https'));
    $path = trim(parse_url($home_url, PHP_URL_PATH), '/');
    
    // Define o slug para o nome do arquivo (substitui '/' por '-' se houver, e usa 'main' se vazio)
    $site_slug = str_replace('/', '-', $path);
    $site_slug = $site_slug !== '' ? $site_slug : 'main';
    
    // Define prioridade da raiz
    if ($site->blog_id == 1) {
        $prioridade_raiz = $config['prioridades']['raiz_principal'];
    } elseif (in_array($path, $config['raizes_especiais'])) {
        $prioridade_raiz = $config['prioridades']['raiz_especial'];
    } else {
        $prioridade_raiz = $config['prioridades']['raiz_normal'];
    }

    $urls = [];
    $urls[] = [
        'loc'      => set_url_scheme($home_url . '/', 'https'),
        'lastmod'  => date('Y-m-d'),
        'priority' => $prioridade_raiz,
    ];

    $front_page_id = (int) get_option('page_on_front', 0);

    // --- CONSULTAS DIRETAS AO BANCO (Bypassa filtros de plugins como Polylang/TEC) ---
    
    // 1. Páginas
    $sql_pages = $wpdb->prepare(
        "SELECT ID, post_modified FROM {$wpdb->posts} 
         WHERE post_type = 'page' AND post_status = 'publish' AND ID != %d",
        $front_page_id
    );
    $paginas = $wpdb->get_results($sql_pages);
    foreach ($paginas as $p) {
        $urls[] = [
            'loc'      => set_url_scheme(get_permalink($p->ID), 'https'),
            'lastmod'  => date('Y-m-d', strtotime($p->post_modified)),
            'priority' => $config['prioridades']['pagina'],
        ];
    }

    // 2. Posts
    $sql_posts = $wpdb->prepare(
        "SELECT ID, post_modified FROM {$wpdb->posts} 
         WHERE post_type = 'post' AND post_status = 'publish'"
    );
    $posts = $wpdb->get_results($sql_posts);
    foreach ($posts as $p) {
        $urls[] = [
            'loc'      => set_url_scheme(get_permalink($p->ID), 'https'),
            'lastmod'  => date('Y-m-d', strtotime($p->post_modified)),
            'priority' => $config['prioridades']['post'],
        ];
    }

    // 3. Eventos (tribe_events)
    if (post_type_exists('tribe_events')) {
        $sql_events = $wpdb->prepare(
            "SELECT ID, post_modified FROM {$wpdb->posts} 
             WHERE post_type = 'tribe_events' AND post_status = 'publish'"
        );
        $eventos = $wpdb->get_results($sql_events);
        foreach ($eventos as $p) {
            $urls[] = [
                'loc'      => set_url_scheme(get_permalink($p->ID), 'https'),
                'lastmod'  => date('Y-m-d', strtotime($p->post_modified)),
                'priority' => $config['prioridades']['evento'],
            ];
        }
    }

    // 4. Categorias
    $sql_cats = $wpdb->prepare(
        "SELECT t.term_id FROM {$wpdb->term_taxonomy} tt
         INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
         WHERE tt.taxonomy = 'category' AND tt.count > 0"
    );
    $categorias = $wpdb->get_results($sql_cats);
    foreach ($categorias as $c) {
        $urls[] = [
            'loc'      => set_url_scheme(get_category_link($c->term_id), 'https'),
            'lastmod'  => date('Y-m-d'),
            'priority' => $config['prioridades']['categoria'],
        ];
    }

    // --- GERAÇÃO DO XML ---
    $sitemap_filename = "sitemap-{$site_slug}.xml";
    $sitemap_path     = $config['pasta_sitemap'] . '/' . $sitemap_filename;

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    $urlset = $dom->createElement('urlset');
    $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $dom->appendChild($urlset);

    foreach ($urls as $url_data) {
        $url = $dom->createElement('url');
        $url->appendChild($dom->createElement('loc', htmlspecialchars($url_data['loc'])));
        $url->appendChild($dom->createElement('lastmod', $url_data['lastmod']));
        $url->appendChild($dom->createElement('priority', $url_data['priority']));
        $urlset->appendChild($url);
    }

    $dom->save($sitemap_path);

    // O index sempre aponta para a raiz principal + /sitemap/ + nome_legivel.xml
    $sitemap_files[] = [
        'loc'     => $main_site_url . '/sitemap/' . $sitemap_filename,
        'lastmod' => date('Y-m-d'),
    ];

    echo sprintf(
        "[OK] Blog %2d | %-30s | URLs: %4d | Arquivo: %s\n",
        $site->blog_id,
        $path ?: 'raiz',
        count($urls),
        $sitemap_filename
    );

    $sites_processados++;
    restore_current_blog();
}

// Gera o Sitemap Index na raiz
$index_dom = new DOMDocument('1.0', 'UTF-8');
$index_dom->formatOutput = true;
$sitemapindex = $index_dom->createElement('sitemapindex');
$sitemapindex->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
$index_dom->appendChild($sitemapindex);

foreach ($sitemap_files as $sitemap_data) {
    $sitemap = $index_dom->createElement('sitemap');
    $sitemap->appendChild($index_dom->createElement('loc', htmlspecialchars($sitemap_data['loc'])));
    $sitemap->appendChild($index_dom->createElement('lastmod', $sitemap_data['lastmod']));
    $sitemapindex->appendChild($sitemap);
}

$index_path = $wp_root . '/sitemap.xml';
$index_dom->save($index_path);

echo "\n=== Concluído com Sucesso ===\n";
echo "Sites processados e adicionados ao sitemap: {$sites_processados}\n";
echo "Sites pulados (não públicos): {$sites_pulados}\n";
echo "Index gerado em: {$index_path}\n";
echo "URL do Index: {$main_site_url}/sitemap.xml\n";