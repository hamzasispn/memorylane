<?php
/**
 * Memory Lane — simple i18n.
 * Cookie-driven (ml_lang). Loads NL or EN dictionary; ml_t() looks up keys with NL fallback.
 */
defined( 'ABSPATH' ) || exit;

function ml_current_lang() {
    static $cached = null;
    if ( $cached !== null ) return $cached;

    if ( is_user_logged_in() ) {
        $meta = get_user_meta( get_current_user_id(), ML_META_LANG, true );
        if ( in_array( $meta, array( 'nl', 'en' ), true ) ) {
            return $cached = $meta;
        }
    }
    if ( isset( $_COOKIE['ml_lang'] ) && in_array( $_COOKIE['ml_lang'], array( 'nl', 'en' ), true ) ) {
        return $cached = $_COOKIE['ml_lang'];
    }
    return $cached = 'nl';
}

function ml_load_strings( $lang ) {
    static $cache = array();
    if ( isset( $cache[ $lang ] ) ) return $cache[ $lang ];

    $file = ML_INC . "i18n/strings/{$lang}.php";
    if ( ! file_exists( $file ) ) return $cache[ $lang ] = array();

    $strings = include $file;
    return $cache[ $lang ] = is_array( $strings ) ? $strings : array();
}

/**
 * Translate. $key is dotted, $default is the NL fallback shown if key missing.
 */
function ml_t( $key, $default = '' ) {
    $lang    = ml_current_lang();
    $strings = ml_load_strings( $lang );
    if ( isset( $strings[ $key ] ) ) return $strings[ $key ];
    if ( $lang !== 'nl' ) {
        $nl = ml_load_strings( 'nl' );
        if ( isset( $nl[ $key ] ) ) return $nl[ $key ];
    }
    return $default !== '' ? $default : $key;
}

/**
 * Echo helper.
 */
function ml_e( $key, $default = '' ) {
    echo esc_html( ml_t( $key, $default ) );
}

/**
 * REST endpoint to set language cookie.
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'memorylane/v1', '/lang', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( $req ) {
            $lang = sanitize_text_field( (string) $req->get_param( 'lang' ) );
            if ( ! in_array( $lang, array( 'nl', 'en' ), true ) ) {
                return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_lang' ), 400 );
            }
            setcookie( 'ml_lang', $lang, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
            if ( is_user_logged_in() ) {
                update_user_meta( get_current_user_id(), ML_META_LANG, $lang );
            }
            return array( 'ok' => true, 'lang' => $lang );
        },
    ) );
} );

/**
 * Helper to format dates in current locale.
 */
function ml_format_date( $timestamp, $format = '' ) {
    if ( ! $timestamp ) return '';
    if ( ! is_int( $timestamp ) ) $timestamp = strtotime( $timestamp );
    if ( ! $format ) $format = ml_current_lang() === 'en' ? 'F j, Y' : 'j F Y';
    return wp_date( $format, $timestamp );
}

function ml_format_datetime( $timestamp ) {
    if ( ! $timestamp ) return '';
    if ( ! is_int( $timestamp ) ) $timestamp = strtotime( $timestamp );
    $fmt = ml_current_lang() === 'en' ? 'M j, Y H:i' : 'j M Y H:i';
    return wp_date( $fmt, $timestamp );
}
