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

// Hide the WP admin bar on the front-end (does not affect wp-admin chrome).
add_filter( 'show_admin_bar', '__return_false' );

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

/**
 * Read (and cache) the Vite build manifest.
 *
 * @return array<string,array> map of source-entry => chunk info, or [] if unbuilt.
 */
function ml_vite_manifest() {
    static $manifest = null;
    if ( null !== $manifest ) {
        return $manifest;
    }
    $path = get_template_directory() . '/assets/dist/.vite/manifest.json';
    $manifest = file_exists( $path )
        ? ( json_decode( file_get_contents( $path ), true ) ?: array() )
        : array();
    return $manifest;
}

/**
 * Enqueue a Vite entry (its JS chunk + any bundled CSS) by source key.
 *
 * @param string   $handle    WP handle prefix.
 * @param string   $entry_key Manifest key, e.g. 'assets/src/js/main.js'.
 * @param string[] $deps      Script dependencies.
 */
function ml_vite_enqueue( $handle, $entry_key, $deps = array() ) {
    $entry = ml_vite_manifest()[ $entry_key ] ?? null;
    if ( ! $entry ) {
        return;
    }
    $dist = get_template_directory_uri() . '/assets/dist';

    foreach ( (array) ( $entry['css'] ?? array() ) as $i => $css_file ) {
        wp_enqueue_style( $handle . ( $i ? "-$i" : '' ), "$dist/$css_file", array(), null );
    }

    wp_enqueue_script(
        $handle,
        "$dist/{$entry['file']}",
        $deps,
        null,
        array( 'strategy' => 'defer', 'in_footer' => true )
    );
}

// Site-wide bundle (marketing site + animations).
add_action( 'wp_enqueue_scripts', function () {
    ml_vite_enqueue( 'virtual-tour', 'assets/src/js/main.js' );
} );

// Booking page bundle (intl-tel-input + address autocomplete + submit) — only
// on the /boek route. Server values are handed to boek.js via window.mlBoek.
add_action( 'wp_enqueue_scripts', function () {
    if ( get_query_var( 'ml_route' ) !== 'boek' ) {
        return;
    }
    ml_vite_enqueue( 'ml-boek', 'assets/src/js/boek.js' );
    wp_localize_script( 'ml-boek', 'mlBoek', array(
        'restUrl' => esc_url_raw( rest_url( 'memorylane/v1/boek' ) ),
        'nonce'   => wp_create_nonce( 'wp_rest' ),
        'lang'    => function_exists( 'ml_current_lang' ) ? ml_current_lang() : 'nl',
        'i18n'    => array(
            'pickSlot'     => ml_t( 'boek.err.pick_slot', 'Kies eerst een datum en uur.' ),
            'loading'      => ml_t( 'common.loading', 'Laden...' ),
            'errorGeneric' => ml_t( 'common.error_generic', 'Er ging iets mis.' ),
            'network'      => 'Network error.',
        ),
    ) );
} );

// Mark the <body> on the booking page so boek.scss can style the shared
// fixed header for a light background.
add_filter( 'body_class', function ( $classes ) {
    if ( get_query_var( 'ml_route' ) === 'boek' ) {
        $classes[] = 'is-boek';
    }
    return $classes;
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