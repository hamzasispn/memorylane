<?php
/**
 * Memory Lane — Stripe Customer Portal session + subscription cancel.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_ml_portal', 'ml_handle_portal' );

function ml_handle_portal() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( home_url( '/login' ) ); exit;
    }
    check_admin_referer( 'ml_portal' );
    $user = wp_get_current_user();
    $cust = get_user_meta( $user->ID, ML_META_STRIPE_CUSTOMER, true );
    if ( ! $cust ) {
        ml_flash_set( 'error', __( 'No Stripe customer linked to your account.', 'memorylane' ) );
        wp_safe_redirect( home_url( '/dashboard/subscription' ) ); exit;
    }
    try {
        $session = ml_stripe()->billingPortal->sessions->create( array(
            'customer'   => $cust,
            'return_url' => home_url( '/dashboard/subscription' ),
        ) );
        wp_safe_redirect( $session->url, 303 );
        exit;
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] Customer portal failed: ' . $e->getMessage() );
        ml_flash_set( 'error', __( 'Could not open Stripe portal.', 'memorylane' ) );
        wp_safe_redirect( home_url( '/dashboard/subscription' ) ); exit;
    }
}

add_action( 'admin_post_ml_sub_cancel', 'ml_handle_sub_cancel' );

function ml_handle_sub_cancel() {
    if ( ! is_user_logged_in() ) { wp_safe_redirect( home_url( '/login' ) ); exit; }
    check_admin_referer( 'ml_sub_cancel' );

    $user = wp_get_current_user();
    $row  = ml_get_subscription_row( $user->ID );
    if ( ! $row ) { wp_safe_redirect( home_url( '/dashboard/subscription' ) ); exit; }

    try {
        ml_stripe()->subscriptions->update( $row->stripe_sub_id, array(
            'cancel_at_period_end' => true,
        ) );
        ml_flash_set( 'success', __( 'Your subscription will end at the current period.', 'memorylane' ) );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] Cancel failed: ' . $e->getMessage() );
        ml_flash_set( 'error', __( 'Could not cancel. Please try again.', 'memorylane' ) );
    }
    wp_safe_redirect( home_url( '/dashboard/subscription' ) ); exit;
}

/**
 * Profile update handlers (display name, phone).
 */
add_action( 'admin_post_ml_profile', function () {
    if ( ! is_user_logged_in() ) { wp_safe_redirect( home_url( '/login' ) ); exit; }
    check_admin_referer( 'ml_profile' );
    $user_id = get_current_user_id();
    $name    = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
    $phone   = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    if ( $name ) wp_update_user( array( 'ID' => $user_id, 'display_name' => $name ) );
    update_user_meta( $user_id, ML_META_PHONE, $phone );
    ml_flash_set( 'success', __( 'Profile updated.', 'memorylane' ) );
    wp_safe_redirect( home_url( '/dashboard/settings' ) ); exit;
} );

add_action( 'admin_post_ml_change_pw', function () {
    if ( ! is_user_logged_in() ) { wp_safe_redirect( home_url( '/login' ) ); exit; }
    check_admin_referer( 'ml_change_pw' );
    $user    = wp_get_current_user();
    $current = (string) ( $_POST['current_password'] ?? '' );
    $new     = (string) ( $_POST['new_password'] ?? '' );
    $check   = wp_authenticate( $user->user_email, $current );
    if ( is_wp_error( $check ) ) {
        ml_flash_set( 'error', __( 'Current password incorrect.', 'memorylane' ) );
        wp_safe_redirect( home_url( '/dashboard/settings' ) ); exit;
    }
    if ( strlen( $new ) < 10 ) {
        ml_flash_set( 'error', ml_t( 'auth.reset.error_short' ) );
        wp_safe_redirect( home_url( '/dashboard/settings' ) ); exit;
    }
    wp_set_password( $new, $user->ID );
    ml_flash_set( 'success', ml_t( 'auth.reset.success' ) );
    wp_safe_redirect( home_url( '/login' ) ); exit;
} );

add_action( 'admin_post_ml_lang', function () {
    if ( ! is_user_logged_in() ) { wp_safe_redirect( home_url( '/login' ) ); exit; }
    check_admin_referer( 'ml_lang' );
    $lang = sanitize_text_field( wp_unslash( $_POST['lang'] ?? 'nl' ) );
    if ( ! in_array( $lang, array( 'nl', 'en' ), true ) ) $lang = 'nl';
    update_user_meta( get_current_user_id(), ML_META_LANG, $lang );
    setcookie( 'ml_lang', $lang, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
    ml_flash_set( 'success', __( 'Language updated.', 'memorylane' ) );
    wp_safe_redirect( home_url( '/dashboard/settings' ) ); exit;
} );
