<?php
defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────
// 1. REMOVE USELESS WORDPRESS DEFAULT STYLES/JS
// ─────────────────────────────────────────────
add_action( 'init', function () {
    // Remove emoji scripts & styles
    remove_action( 'wp_head',             'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles',     'print_emoji_styles' );
    remove_action( 'admin_print_styles',  'print_emoji_styles' );
    remove_filter( 'the_content_feed',    'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss',    'wp_staticize_emoji' );
    remove_filter( 'wp_mail',            'wp_staticize_emoji_for_email' );
} );

add_action( 'wp_enqueue_scripts', function () {
    // Remove block / global styles
    wp_dequeue_style( 'global-styles' );
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'classic-theme-styles' );

    // Remove default jQuery (we don't need it — Alpine handles reactivity)
    wp_dequeue_script( 'jquery' );
    wp_deregister_script( 'jquery' );
}, 100 );

// Remove RSD, wlwmanifest, shortlink, etc. from <head>
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_resource_hints', 2 );

// ─────────────────────────────────────────────
// 2. ENQUEUE DIST CSS + JS (from Vite build)
// ─────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function () {
    $dist = get_template_directory_uri() . '/assets/dist';
    $dir  = get_template_directory()     . '/assets/dist';
    $manifest_path = $dir . '/.vite/manifest.json';

    if ( ! file_exists( $manifest_path ) ) {
        return;
    }

    $manifest = json_decode( file_get_contents( $manifest_path ), true );
    $entry    = $manifest['assets/src/js/main.js'] ?? null;

    if ( ! $entry ) {
        return;
    }

    if ( ! empty( $entry['css'] ) ) {
        foreach ( $entry['css'] as $css_file ) {
            wp_enqueue_style(
                'virtual-tour',
                $dist . '/' . $css_file,
                [],
                null 
            );
        }
    }

    wp_enqueue_script(
        'virtual-tour',
        $dist . '/' . $entry['file'],
        [],
        null,
        [ 'strategy' => 'defer', 'in_footer' => true ]
    );
} );

// Standalone CSS for the booking date+time picker (not bundled by Vite so
// edits go live without a rebuild).
add_action( 'wp_enqueue_scripts', function () {
    $rel = '/assets/css/booking-picker.css';
    $abs = get_template_directory() . $rel;
    if ( file_exists( $abs ) ) {
        wp_enqueue_style(
            'ml-booking-picker',
            get_template_directory_uri() . $rel,
            array(),
            (string) filemtime( $abs )
        );
    }
} );

require_once get_template_directory() . '/inc/bootstrap.php';