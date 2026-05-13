<?php
/**
 * Memory Lane — password reset email sending.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Sends a password reset email. $template ∈ { password_reset, welcome_set_password }.
 */
function ml_send_reset_email( WP_User $user, $template = 'password_reset' ) {
    $key = get_password_reset_key( $user );
    if ( is_wp_error( $key ) ) return false;

    $path = $template === 'welcome_set_password' ? "welcome/{$key}" : "reset-password/{$key}";
    $url  = home_url( "/{$path}?login=" . rawurlencode( $user->user_login ) );

    return ml_mail_send( $user->user_email, $template, array(
        'user'  => $user,
        'url'   => $url,
        'token' => $key,
    ), $user->ID );
}
