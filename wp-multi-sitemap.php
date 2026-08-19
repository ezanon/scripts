<?php
// Impede execução via navegador por segurança (permite apenas via linha de comando / CRON)
if ( php_sapi_name() !== 'cli' && ! defined( 'DOING_CRON' ) ) {
    die( 'Acesso direto negado. Execute via linha de comando (CLI) ou Cron.' );
}

// Carrega o ambiente do WordPress
require_once __DIR__ . '/wp-load.php';

if ( ! is_multisite() ) {
    die( 'Este script requer uma instalação WordPress Multisite.' );
}

// Busca apenas subsites ativos (não arquivados, não deletados, não spam, públicos)
$sites = get_sites( [
    'public'   => 1,
    'archived' => 0,
    'mature'   => 0,
    'spam'     => 0,
    'deleted'  => 0,
    'number'   => 0, // Traz todos os subsites sem limite
] );

// Cria o documento XML
$xml = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>' );

$total_urls = 0;

foreach ( $sites as $site ) {
    // Alterna para o contexto do subsite atual
    switch_to_blog( $site->blog_id );

    // 1. Adiciona a Home do subsite
    $url_node = $xml->addChild( 'url' );
    $url_node->addChild( 'loc', esc_url( home_url( '/' ) ) );
    $url_node->addChild( 'lastmod', gmdate( 'c' ) );
    $url_node->addChild( 'changefreq', 'daily' );
    $url_node->addChild( 'priority', '1.0' );
    $total_urls++;

    // 2. Busca Post Types públicos (Posts, Páginas e CPTs públicos)
    $post_types = get_post_types( [ 'public' => true ], 'names' );
    
    // Remove anexos (mídia) do sitemap, se desejado
    unset( $post_types['attachment'] );

    $posts = get_posts( [
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids', // Otimiza o uso de memória
        'suppress_filters' => true,
    ] );

    foreach ( $posts as $post_id ) {
        $permalink = get_permalink( $post_id );
        $modified  = get_post_modified_time( 'c', true, $post_id );

        if ( $permalink ) {
            $url_node = $xml->addChild( 'url' );
            $url_node->addChild( 'loc', esc_url( $permalink ) );
            $url_node->addChild( 'lastmod', $modified );
            $url_node->addChild( 'changefreq', 'weekly' );
            $url_node->addChild( 'priority', '0.8' );
            $total_urls++;
        }
    }

    // Restaura o contexto do blog original
    restore_current_blog();
}

// Caminho de saída do arquivo na raiz
$output_path = __DIR__ . '/sitemap.xml';

// Salva o XML formatado
$dom = new DOMDocument( '1.0' );
$dom->preserveWhiteSpace = false;
$dom->formatOutput       = true;
$dom->loadXML( $xml->asXML() );

if ( $dom->save( $output_path ) ) {
    echo "Sitemap gerado com sucesso em: {$output_path}\n";
    echo "Total de URLs inseridas: {$total_urls}\n";
} else {
    echo "Erro ao salvar o arquivo sitemap.xml.\n";
}