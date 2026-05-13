<?php
/**
 * Memory Lane — Stripe event: customer.subscription.deleted.
 * Marks the subscription cancelled. Tours flagged pending_archive. Customer + admin notified.
 */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_customer_subscription_deleted( \Stripe\Event $event ) {
    $sub = $event->data->object;

    $user_id = ml_user_id_by_stripe_customer( $sub->customer );
    if ( ! $user_id ) return;

    ml_upsert_subscription( $user_id, array(
        'stripe_customer_id' => $sub->customer,
        'stripe_sub_id'      => $sub->id,
        'status'             => 'cancelled',
        'current_period_end' => $sub->current_period_end ? gmdate( 'Y-m-d H:i:s', $sub->current_period_end ) : null,
        'cancel_at_period_end' => 1,
        'raw_json'           => wp_json_encode( $sub->toArray() ),
    ) );

    ml_flag_user_tours_pending_archive( $user_id );

    $user = get_user_by( 'id', $user_id );
    if ( $user ) {
        ml_mail_send( $user->user_email, 'subscription_cancelled', array(
            'user' => $user,
            'end_date' => $sub->current_period_end,
        ), $user_id );
    }

    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_subscription_cancelled', array(
            'user' => $user,
            'sub'  => $sub,
        ) );
    }

    wp_cache_delete( 'ml_access_' . $user_id, 'ml' );
}
