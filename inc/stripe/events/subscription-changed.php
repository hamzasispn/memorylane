<?php
/** Stripe events: customer.subscription.updated / .deleted → mirror status. */
defined( 'ABSPATH' ) || exit;

function ml_stripe_sub_user_id( $customer_id, $sub_id ) {
    // Prefer the meta lookup, fall back to the subscriptions table.
    $users = get_users( array( 'meta_key' => ML_META_STRIPE_CUSTOMER, 'meta_value' => $customer_id, 'number' => 1, 'fields' => 'ID' ) );
    if ( ! empty( $users ) ) return (int) $users[0];
    global $wpdb;
    $tbl = ml_table( 'subscriptions' );
    $uid = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$tbl} WHERE stripe_sub_id=%s LIMIT 1", $sub_id ) );
    return (int) $uid;
}

function ml_stripe_event_customer_subscription_changed( \Stripe\Event $event ) {
    $sub    = $event->data->object;
    $fields = ml_sub_fields_from_stripe( $sub );
    $uid    = ml_stripe_sub_user_id( $fields['stripe_customer_id'], $fields['stripe_sub_id'] );
    if ( $uid ) ml_upsert_subscription( $uid, $fields );
}

function ml_stripe_event_customer_subscription_deleted( \Stripe\Event $event ) {
    $sub    = $event->data->object;
    $fields = ml_sub_fields_from_stripe( $sub );
    $fields['status'] = 'cancelled';
    $uid    = ml_stripe_sub_user_id( $fields['stripe_customer_id'], $fields['stripe_sub_id'] );
    if ( $uid ) ml_upsert_subscription( $uid, $fields );
}
