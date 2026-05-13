<?php
/**
 * Memory Lane — Stripe Checkout session creation.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Handle GET/POST to /checkout/start — creates a Checkout Session and redirects.
 */
function ml_handle_checkout_start() {
    if ( ! ml_stripe_is_configured() ) {
        wp_die( esc_html__( 'Payments are not configured yet. Please contact us.', 'memorylane' ), 500 );
    }
    $stripe = ml_stripe();

    try {
        // mode=payment: customer pays the one-time setup fee (which covers Year 1 of access).
        // The recurring monthly subscription is created later, after admin approves
        // (post-scan / Matterport processing — up to 8h SLA).
        $session = $stripe->checkout->sessions->create( array(
            'mode'      => 'payment',
            'line_items' => array( array(
                'price'    => ml_stripe_setup_price_id(),
                'quantity' => 1,
            ) ),
            'customer_creation'          => 'always',
            'billing_address_collection' => 'required',
            'phone_number_collection'    => array( 'enabled' => true ),
            'locale'                     => ml_current_lang() === 'en' ? 'en' : 'nl',
            'success_url'                => home_url( '/checkout/success?session_id={CHECKOUT_SESSION_ID}' ),
            'cancel_url'                 => home_url( '/checkout/cancel' ),
            'payment_intent_data'        => array(
                'metadata' => array(
                    'ml_intent' => 'memory_lane_setup_year_one',
                ),
            ),
            'metadata' => array(
                'ml_intent' => 'initial_purchase',
                'ml_lang'   => ml_current_lang(),
            ),
            'allow_promotion_codes' => true,
        ) );

        wp_safe_redirect( $session->url, 303 );
        exit;
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] Checkout session creation failed: ' . $e->getMessage() );
        wp_die( esc_html__( 'We could not start the checkout. Please try again or contact us.', 'memorylane' ), 500 );
    }
}

/**
 * REST endpoint variant (used by AJAX on /tarieven Start button).
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'memorylane/v1', '/checkout', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function () {
            if ( ! ml_stripe_is_configured() ) {
                return new WP_REST_Response( array( 'ok' => false, 'error' => 'not_configured' ), 503 );
            }
            $stripe = ml_stripe();
            try {
                $session = $stripe->checkout->sessions->create( array(
                    'mode' => 'payment',
                    'line_items' => array( array( 'price' => ml_stripe_setup_price_id(), 'quantity' => 1 ) ),
                    'customer_creation' => 'always',
                    'billing_address_collection' => 'required',
                    'phone_number_collection' => array( 'enabled' => true ),
                    'locale' => ml_current_lang() === 'en' ? 'en' : 'nl',
                    'success_url' => home_url( '/checkout/success?session_id={CHECKOUT_SESSION_ID}' ),
                    'cancel_url'  => home_url( '/checkout/cancel' ),
                    'payment_intent_data' => array( 'metadata' => array( 'ml_intent' => 'memory_lane_setup_year_one' ) ),
                    'metadata' => array( 'ml_intent' => 'initial_purchase', 'ml_lang' => ml_current_lang() ),
                    'allow_promotion_codes' => true,
                ) );
                return array( 'ok' => true, 'url' => $session->url );
            } catch ( \Throwable $e ) {
                error_log( '[memorylane] Checkout REST failed: ' . $e->getMessage() );
                return new WP_REST_Response( array( 'ok' => false, 'error' => 'stripe_error' ), 500 );
            }
        },
    ) );
} );
