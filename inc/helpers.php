<?php

// Resgiter Cutom Menus 
function register_my_menus() {
    register_nav_menus(
        array(
            'primary' => __( 'Primary Menu' ),
            'footer' => __( 'Footer Menu'),
        )
    );
}
add_action( 'init', 'register_my_menus' );

/**
 * Generate a unique WP username from an email local-part.
 * Shared by the /boek booking flow and the Stripe checkout handler.
 */
if ( ! function_exists( 'ml_unique_username' ) ) {
    function ml_unique_username( $email ) {
        $base = sanitize_user( current( explode( '@', (string) $email ) ), true );
        if ( ! $base ) $base = 'user';
        $candidate = $base;
        $i = 1;
        while ( username_exists( $candidate ) ) {
            $candidate = $base . $i;
            $i++;
        }
        return $candidate;
    }
}