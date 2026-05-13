<?php
/**
 * Memory Lane — Stripe events: customer.subscription.created / updated.
 */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_customer_subscription_changed( \Stripe\Event $event ) {
    $sub = $event->data->object; // \Stripe\Subscription

    $user_id = ml_user_id_by_stripe_customer( $sub->customer );
    if ( ! $user_id ) {
        // Orphan — leave for the daily orphan-payment-check cron.
        return;
    }

    ml_upsert_subscription( $user_id, array(
        'stripe_customer_id'   => $sub->customer,
        'stripe_sub_id'        => $sub->id,
        'status'               => $sub->status,
        'current_period_end'   => $sub->current_period_end ? gmdate( 'Y-m-d H:i:s', $sub->current_period_end ) : null,
        'cancel_at_period_end' => $sub->cancel_at_period_end ? 1 : 0,
        'raw_json'             => wp_json_encode( $sub->toArray() ),
    ) );

    // Invalidate access cache.
    wp_cache_delete( 'ml_access_' . $user_id, 'ml' );
}
