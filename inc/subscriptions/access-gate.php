<?php
/**
 * Memory Lane — access gate.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Returns true if the user currently has access (active subscription incl. grace).
 */
function ml_user_has_access( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) return false;

    // Admins always have access (for testing / preview).
    if ( user_can( $user_id, ML_CAP_MANAGE ) ) return true;

    $cached = wp_cache_get( 'ml_access_' . $user_id, 'ml' );
    if ( $cached !== false ) return (bool) $cached;

    $result = ml_compute_access( $user_id );
    wp_cache_set( 'ml_access_' . $user_id, $result ? 1 : 0, 'ml', 60 );
    return $result;
}

function ml_compute_access( $user_id ) {
    $row = ml_get_subscription_row( $user_id );
    if ( ! $row ) return false;

    if ( ! in_array( $row->status, ml_active_subscription_statuses(), true ) ) return false;

    $period_end_ts = $row->current_period_end ? strtotime( $row->current_period_end . ' UTC' ) : 0;
    if ( ! $period_end_ts ) return false;

    // For active/trialing, just check period end > now.
    if ( in_array( $row->status, array( 'active', 'trialing' ), true ) ) {
        return $period_end_ts > time();
    }

    // For past_due, allow grace.
    return ( $period_end_ts + ml_past_due_grace_seconds() ) > time();
}
