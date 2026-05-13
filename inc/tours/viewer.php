<?php
/**
 * Memory Lane — tour embed sanitizer + safe output helper.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize pasted iframe embed code. Rejects if no allowed iframe found.
 * Stores the sanitized HTML.
 */
function ml_sanitize_tour_embed( $raw ) {
    $raw = (string) $raw;
    if ( $raw === '' ) return '';

    $allowed = array(
        'iframe' => array(
            'src'              => true,
            'width'            => true,
            'height'           => true,
            'frameborder'      => true,
            'allowfullscreen'  => true,
            'allow'            => true,
            'loading'          => true,
            'referrerpolicy'   => true,
            'title'            => true,
            'style'            => true,
        ),
    );
    $clean = wp_kses( $raw, $allowed );

    // Validate src host against allowlist.
    if ( preg_match( '~<iframe[^>]+src=["\']([^"\']+)["\']~i', $clean, $m ) ) {
        $host = wp_parse_url( $m[1], PHP_URL_HOST );
        if ( ! $host ) return '';
        $allow_list = array_map( 'strtolower', ml_embed_domain_allowlist() );
        if ( ! in_array( strtolower( $host ), $allow_list, true ) ) {
            return ''; // reject
        }
    } else {
        return '';
    }

    return $clean;
}

/**
 * Echo-safe tour embed output. Returns iframe HTML or empty.
 */
function ml_safe_tour_embed( $embed_html ) {
    $allowed = array(
        'iframe' => array(
            'src' => true, 'width' => true, 'height' => true, 'frameborder' => true,
            'allowfullscreen' => true, 'allow' => true, 'loading' => true,
            'referrerpolicy' => true, 'title' => true, 'style' => true, 'class' => true,
        ),
    );
    // Force class so we can size it.
    $embed_html = preg_replace_callback(
        '/<iframe([^>]*)>/i',
        function ( $m ) {
            $attrs = $m[1];
            if ( strpos( $attrs, 'class=' ) === false ) {
                $attrs .= ' class="ml-tour-frame"';
            } else {
                $attrs = preg_replace( '/class="([^"]*)"/', 'class="$1 ml-tour-frame"', $attrs );
            }
            return '<iframe' . $attrs . '>';
        },
        $embed_html
    );
    return wp_kses( $embed_html, $allowed );
}
