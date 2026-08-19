<?php
// Simula as variáveis de servidor para o Multisite funcionar via CLI
$_SERVER['HTTP_HOST']   = 'igc.usp.br';
$_SERVER['SERVER_NAME'] = 'igc.usp.br';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/wp-multi-sitemap.php';

//$_SERVER['HTTP_HOST']   = 'dev2.igc.usp.br';
//$_SERVER['SERVER_NAME'] = 'dev2.igc.usp.br';
//$_SERVER['REQUEST_URI'] = '/multisite/';
//$_SERVER['SCRIPT_NAME'] = '/multisite/wp-multi-sitemap.php';

// Configuração para exibir erros de execução no terminal
ini_set( 'display_errors', 1 );
error_reporting( E_ALL );

// Carrega o ambiente do WordPress (NENHUM echo deve ser feito antes desta linha)
$wp_load = __DIR__ . '/wp-load.php';

if ( ! file_exists( $wp_load ) ) {
    die( "ERRO: O arquivo wp-load.php nao foi encontrado em: {$wp_load}\n" );
}

require_once $wp_load;

echo "1. Ambiente WordPress Multisite carregado com sucesso!\n";

if ( ! is_multisite() ) {
    die( "ERRO: Esta instalacao nao e uma rede Multisite.\n" );
}

echo "2. Buscando subsites ativos...\n";

// Busca subsites ativos (públicos e sem restrições)
$sites = get_sites( [
    'public'   => 1,
    'archived' => 0,
    'spam'     => 0,
    'deleted'  => 0,
    'number'   => 0,
] );

echo "Subsites encontrados: " . count( $sites ) . "\n";

$xml = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>' );
$total_urls = 0;

foreach ( $sites as $site ) {
    echo "Processando subsite ID {$site->blog_id} ({$site->domain}{$site->path})...\n";

    switch_to_blog( $site->blog_id );

    // Adiciona a Home do subsite
    $url_node = $xml->addChild( 'url' );
    $url_node->addChild( 'loc', esc_url( home_url( '/' ) ) );
    $url_node->addChild( 'lastmod', gmdate( 'c' ) );
    $url_node->addChild( 'priority', '1.0' );
    $total_urls++;

    // Busca Post Types públicos
    $post_types = get_post_types( [ 'public' => true ], 'names' );
    unset( $post_types['attachment'] );

    $posts = get_posts( [
        'post_type'        => $post_types,
        'post_status'      => 'publish',
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'suppress_filters' => true,
    ] );

    foreach ( $posts as $post_id ) {
        $permalink = get_permalink( $post_id );
        if ( $permalink ) {
            $modified = get_post_modified_time( 'c', true, $post_id );
            $url_node = $xml->addChild( 'url' );
            $url_node->addChild( 'loc', esc_url( $permalink ) );
            $url_node->addChild( 'lastmod', $modified ? $modified : gmdate( 'c' ) );
            $url_node->addChild( 'priority', '0.8' );
            $total_urls++;
        }
    }

    restore_current_blog();
}

echo "3. Salvando o arquivo XML...\n";

$output_path = __DIR__ . '/sitemap.xml';

$dom = new DOMDocument( '1.0' );
$dom->preserveWhiteSpace = false;
$dom->formatOutput       = true;
$dom->loadXML( $xml->asXML() );

if ( $dom->save( $output_path ) ) {
    echo "SUCESSO: Sitemap gerado em {$output_path}\n";
    echo "Total de URLs inseridas: {$total_urls}\n";
} else {
    echo "ERRO: Nao foi possivel escrever o arquivo sitemap.xml. Verifique as permissoes da pasta.\n";
}