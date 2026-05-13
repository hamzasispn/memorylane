<?php
/**
 * Memory Lane — Stripe client initialization.
 */
defined( 'ABSPATH' ) || exit;

function ml_stripe_secret() {
    return (string) ml_stripe_opt( 'secret_key' );
}

function ml_stripe_publishable() {
    return (string) ml_stripe_opt( 'publishable_key' );
}

function ml_stripe_webhook_secret() {
    return (string) ml_stripe_opt( 'webhook_secret' );
}

function ml_stripe_setup_price_id() {
    return (string) ml_stripe_opt( 'setup_price_id' );
}

function ml_stripe_monthly_price_id() {
    return (string) ml_stripe_opt( 'monthly_price_id' );
}

function ml_stripe_reactivation_price_id() {
    return (string) ml_stripe_opt( 'reactivation_price_id' );
}

function ml_stripe_annual_price_id() {
    return (string) ml_stripe_opt( 'annual_price_id' );
}

/**
 * Get a Stripe client. Returns null if not configured. Throws no exception.
 */
function ml_stripe() {
    if ( ! class_exists( '\Stripe\StripeClient' ) ) return null;
    $secret = ml_stripe_secret();
    if ( ! $secret ) return null;
    return new \Stripe\StripeClient( array(
        'api_key'       => $secret,
        'stripe_version' => '2024-12-18.acacia',
    ) );
}

/**
 * True if all required Stripe options are filled for the active mode.
 */
function ml_stripe_is_configured() {
    return (bool) (
        ml_stripe_secret()
        && ml_stripe_publishable()
        && ml_stripe_setup_price_id()
        && ml_stripe_monthly_price_id()
        && ml_stripe_reactivation_price_id()
    );
}

function ml_stripe_is_connected() {
    return ml_stripe_is_configured() && (bool) get_option( ML_OPT_STRIPE_CONNECTED_AT );
}
