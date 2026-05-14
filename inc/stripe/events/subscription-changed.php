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

    $fields = array(
        'stripe_customer_id'   => $sub->customer,
        'stripe_sub_id'        => $sub->id,
        'status'               => $sub->status,
        'current_period_end'   => $sub->current_period_end ? gmdate( 'Y-m-d H:i:s', $sub->current_period_end ) : null,
        'cancel_at_period_end' => $sub->cancel_at_period_end ? 1 : 0,
        'raw_json'             => wp_json_encode( $sub->toArray() ),
    );

    // V2-5: track when Stripe gave up retrying so the grace-period cron can
    // archive after grace_days. Clear on any return to a healthy status.
    if ( $sub->status === 'unpaid' ) {
        // Only set on first transition to unpaid (preserve original timestamp).
        global $wpdb;
        $tbl  = ml_table( 'subscriptions' );
        $prev = $wpdb->get_var( $wpdb->prepare(
            "SELECT payment_failed_at FROM {$tbl} WHERE stripe_sub_id=%s LIMIT 1",
            $sub->id
        ) );
        if ( empty( $prev ) ) {
            $fields['payment_failed_at'] = current_time( 'mysql', true );
        }
    } elseif ( in_array( $sub->status, array( 'active', 'trialing' ), true ) ) {
        $fields['payment_failed_at'] = null;
    }

    ml_upsert_subscription( $user_id, $fields );

    wp_cache_delete( 'ml_access_' . $user_id, 'ml' );
}
