<?php
/**
 * Memory Lane — Stripe webhook receiver.
 * Verifies signature, enforces idempotency, dispatches to event handlers.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
    register_rest_route( 'memorylane/v1', '/stripe-webhook', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'ml_stripe_webhook_handler',
    ) );
} );

function ml_stripe_webhook_handler( WP_REST_Request $request ) {
    $payload   = $request->get_body();
    $sig       = $request->get_header( 'stripe_signature' );
    $secret    = ml_stripe_webhook_secret();

    if ( ! $secret ) {
        return new WP_REST_Response( array( 'error' => 'webhook_not_configured' ), 503 );
    }

    try {
        $event = \Stripe\Webhook::constructEvent( $payload, $sig ?? '', $secret );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] Webhook signature verification failed: ' . $e->getMessage() );
        return new WP_REST_Response( array( 'error' => 'invalid_signature' ), 400 );
    }

    global $wpdb;
    $now    = current_time( 'mysql', true );
    $tbl    = ml_table( 'webhook_events' );

    // Insert idempotency row, ignore duplicate event IDs.
    $inserted = $wpdb->query( $wpdb->prepare(
        "INSERT IGNORE INTO {$tbl} (event_id, type, status, payload, received_at) VALUES (%s, %s, %s, %s, %s)",
        $event->id,
        $event->type,
        'pending',
        wp_json_encode( $event->toArray() ),
        $now
    ) );

    if ( ! $inserted ) {
        // Already processed.
        return new WP_REST_Response( array( 'ok' => true, 'duplicate' => true ), 200 );
    }

    try {
        ml_stripe_dispatch_event( $event );

        $wpdb->update( $tbl,
            array( 'status' => 'processed', 'processed_at' => current_time( 'mysql', true ), 'error_msg' => null ),
            array( 'event_id' => $event->id )
        );

        return new WP_REST_Response( array( 'ok' => true ), 200 );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] Webhook handler failed for ' . $event->type . ': ' . $e->getMessage() );
        $wpdb->update( $tbl,
            array( 'status' => 'failed', 'error_msg' => substr( $e->getMessage(), 0, 1000 ) ),
            array( 'event_id' => $event->id )
        );
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'handler_error' ), 500 );
    }
}

/**
 * Dispatch event to a specific handler.
 */
function ml_stripe_dispatch_event( \Stripe\Event $event ) {
    $type = $event->type;
    $map = array(
        'checkout.session.completed'      => 'ml_stripe_event_checkout_session_completed',
        'customer.subscription.created'   => 'ml_stripe_event_customer_subscription_changed',
        'customer.subscription.updated'   => 'ml_stripe_event_customer_subscription_changed',
        'customer.subscription.deleted'   => 'ml_stripe_event_customer_subscription_deleted',
        'invoice.payment_succeeded'       => 'ml_stripe_event_invoice_payment_succeeded',
        'invoice.payment_failed'          => 'ml_stripe_event_invoice_payment_failed',
        'invoice.upcoming'                => 'ml_stripe_event_invoice_upcoming',
    );
    $fn = $map[ $type ] ?? null;
    if ( $fn && function_exists( $fn ) ) {
        $fn( $event );
    }
}
