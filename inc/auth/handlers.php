<?php
/**
 * Memory Lane — auth form POST handlers + rate limit.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Hashes an IP address for storage. Don't store raw IPs.
 */
function ml_ip_hash() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return hash( 'sha256', $ip . wp_salt() );
}

/**
 * Login attempt counter (transient-based).
 * Returns true if the request should be blocked.
 */
function ml_login_is_locked() {
    $key = 'ml_lock_' . ml_ip_hash();
    return (bool) get_transient( $key );
}

function ml_login_record_failure( $username ) {
    $iph = ml_ip_hash();
    $ck  = 'ml_failcount_' . $iph;
    $count = (int) get_transient( $ck );
    $count++;
    set_transient( $ck, $count, 15 * MINUTE_IN_SECONDS );
    if ( $count >= 5 ) {
        set_transient( 'ml_lock_' . $iph, 1, HOUR_IN_SECONDS );
    }
    // Audit row.
    global $wpdb;
    $wpdb->insert( ml_table( 'login_attempts' ), array(
        'ip_hash'      => $iph,
        'username'     => substr( (string) $username, 0, 191 ),
        'attempted_at' => current_time( 'mysql', true ),
        'success'      => 0,
    ) );
}

function ml_login_record_success( $username ) {
    delete_transient( 'ml_failcount_' . ml_ip_hash() );
    delete_transient( 'ml_lock_' . ml_ip_hash() );
    global $wpdb;
    $wpdb->insert( ml_table( 'login_attempts' ), array(
        'ip_hash'      => ml_ip_hash(),
        'username'     => substr( (string) $username, 0, 191 ),
        'attempted_at' => current_time( 'mysql', true ),
        'success'      => 1,
    ) );
}

/**
 * Login form POST.
 */
add_action( 'admin_post_nopriv_ml_login', 'ml_handle_login_post' );
add_action( 'admin_post_ml_login',        'ml_handle_login_post' );

function ml_handle_login_post() {
    check_admin_referer( 'ml_login' );

    if ( ml_login_is_locked() ) {
        ml_flash_set( 'error', ml_t( 'auth.login.error_locked' ) );
        wp_safe_redirect( home_url( '/login' ) );
        exit;
    }

    // Honeypot.
    if ( ! empty( $_POST['ml_hp'] ?? '' ) ) {
        wp_safe_redirect( home_url( '/login' ) );
        exit;
    }

    $email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $password = (string) ( $_POST['password'] ?? '' );
    $remember = ! empty( $_POST['remember'] );
    $redirect = ml_safe_redirect_to( wp_unslash( $_POST['redirect_to'] ?? '/dashboard' ) );

    $user = wp_authenticate( $email, $password );
    if ( is_wp_error( $user ) ) {
        ml_login_record_failure( $email );
        ml_flash_set( 'error', ml_t( 'auth.login.error_generic' ) );
        ml_flash_set( 'email', $email );
        wp_safe_redirect( home_url( '/login' ) );
        exit;
    }

    ml_login_record_success( $email );
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, $remember, is_ssl() );
    update_user_meta( $user->ID, ML_META_LAST_LOGIN, current_time( 'mysql', true ) );

    wp_safe_redirect( $redirect );
    exit;
}

/**
 * Forgot-password form POST. Always returns generic success.
 */
add_action( 'admin_post_nopriv_ml_forgot', 'ml_handle_forgot_post' );
add_action( 'admin_post_ml_forgot',        'ml_handle_forgot_post' );

function ml_handle_forgot_post() {
    check_admin_referer( 'ml_forgot' );
    if ( ! empty( $_POST['ml_hp'] ?? '' ) ) {
        ml_flash_set( 'success', ml_t( 'auth.forgot.success' ) );
        wp_safe_redirect( home_url( '/forgot-password' ) );
        exit;
    }

    $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $user  = $email ? get_user_by( 'email', $email ) : false;

    if ( $user ) {
        ml_send_reset_email( $user, 'password_reset' );
    }

    ml_flash_set( 'success', ml_t( 'auth.forgot.success' ) );
    wp_safe_redirect( home_url( '/forgot-password' ) );
    exit;
}

/**
 * Reset password form POST.
 */
add_action( 'admin_post_nopriv_ml_reset', 'ml_handle_reset_post' );
add_action( 'admin_post_ml_reset',        'ml_handle_reset_post' );

function ml_handle_reset_post() {
    check_admin_referer( 'ml_reset' );

    $login = sanitize_user( wp_unslash( $_POST['user_login'] ?? '' ), true );
    $key   = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
    $pass1 = (string) ( $_POST['password1'] ?? '' );
    $pass2 = (string) ( $_POST['password2'] ?? '' );

    $user = check_password_reset_key( $key, $login );
    if ( is_wp_error( $user ) ) {
        ml_flash_set( 'error', ml_t( 'auth.reset.error_token' ) );
        wp_safe_redirect( home_url( '/forgot-password' ) );
        exit;
    }
    if ( $pass1 !== $pass2 ) {
        ml_flash_set( 'error', ml_t( 'auth.reset.error_match' ) );
        wp_safe_redirect( home_url( "/reset-password/{$key}?login=" . rawurlencode( $login ) ) );
        exit;
    }
    if ( strlen( $pass1 ) < 10 ) {
        ml_flash_set( 'error', ml_t( 'auth.reset.error_short' ) );
        wp_safe_redirect( home_url( "/reset-password/{$key}?login=" . rawurlencode( $login ) ) );
        exit;
    }

    reset_password( $user, $pass1 );

    // Invalidate all sessions.
    $manager = WP_Session_Tokens::get_instance( $user->ID );
    $manager->destroy_all();

    ml_flash_set( 'success', ml_t( 'auth.reset.success' ) );
    wp_safe_redirect( home_url( '/login' ) );
    exit;
}

/**
 * Flash messages helper (cookie-based, single-read).
 */
function ml_flash_set( $key, $value ) {
    $bag = json_decode( wp_unslash( $_COOKIE['ml_flash'] ?? '[]' ), true );
    if ( ! is_array( $bag ) ) $bag = array();
    $bag[ $key ] = $value;
    setcookie( 'ml_flash', wp_json_encode( $bag ), time() + 60, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
    $_COOKIE['ml_flash'] = wp_json_encode( $bag );
}

function ml_flash_take( $key ) {
    $bag = json_decode( wp_unslash( $_COOKIE['ml_flash'] ?? '[]' ), true );
    if ( ! is_array( $bag ) ) return null;
    $value = $bag[ $key ] ?? null;
    unset( $bag[ $key ] );
    if ( empty( $bag ) ) {
        setcookie( 'ml_flash', '', time() - 3600, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
        unset( $_COOKIE['ml_flash'] );
    } else {
        setcookie( 'ml_flash', wp_json_encode( $bag ), time() + 60, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
        $_COOKIE['ml_flash'] = wp_json_encode( $bag );
    }
    return $value;
}

/**
 * Only allow same-host redirects.
 */
function ml_safe_redirect_to( $raw ) {
    $raw = (string) $raw;
    if ( $raw === '' ) return home_url( '/dashboard' );
    $host = wp_parse_url( $raw, PHP_URL_HOST );
    if ( $host && $host !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
        return home_url( '/dashboard' );
    }
    return $raw;
}
