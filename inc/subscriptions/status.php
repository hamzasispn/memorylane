<?php
/**
 * Memory Lane — subscription read helpers.
 */
defined( 'ABSPATH' ) || exit;

function ml_get_subscription_row( $user_id ) {
    global $wpdb;
    $tbl = ml_table( 'subscriptions' );
    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$tbl} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
        (int) $user_id
    ) );
}

function ml_user_id_by_stripe_customer( $stripe_customer_id ) {
    global $wpdb;
    $users = get_users( array(
        'meta_key'   => ML_META_STRIPE_CUSTOMER,
        'meta_value' => $stripe_customer_id,
        'number'     => 1,
        'fields'     => 'ID',
    ) );
    if ( ! empty( $users ) ) return (int) $users[0];

    // Fallback: cross-ref subscriptions table.
    $tbl = ml_table( 'subscriptions' );
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT user_id FROM {$tbl} WHERE stripe_customer_id=%s LIMIT 1", $stripe_customer_id ) );
    return $row ? (int) $row->user_id : 0;
}

function ml_subscription_phase( $row ) {
    if ( ! $row || empty( $row->year_one_end_date ) ) return 'monthly';
    return strtotime( $row->year_one_end_date . ' UTC' ) > time() ? 'year_one' : 'monthly';
}

function ml_subscription_status_label( $row ) {
    if ( ! $row ) return ml_t( 'sub.status.cancelled' );
    if ( $row->cancel_at_period_end && in_array( $row->status, array( 'active', 'trialing' ), true ) ) {
        return ml_t( 'sub.status.canceling' );
    }
    $key = 'sub.status.' . $row->status;
    return ml_t( $key, ucfirst( $row->status ) );
}

function ml_subscription_status_pill_class( $row ) {
    if ( ! $row ) return 'ml-pill--neutral';
    if ( $row->cancel_at_period_end ) return 'ml-pill--warning';
    switch ( $row->status ) {
        case 'active':
        case 'trialing':   return 'ml-pill--success';
        case 'past_due':   return 'ml-pill--warning';
        case 'cancelled':
        case 'canceled':
        case 'unpaid':
        case 'incomplete_expired': return 'ml-pill--danger';
        default: return 'ml-pill--neutral';
    }
}
