<?php
/**
 * Memory Lane — access gate (subscription model).
 * A logged-in customer has portal access while their subscription is active /
 * trialing, or past_due within the configured grace window. Admins always pass.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Pure decision: does this subscription row grant access right now? (testable)
 *
 * @param object|null $row  ml_subscriptions row (needs ->status, ->current_period_end).
 * @param int $grace_seconds  past_due grace window.
 * @param int $now_ts         current unix time.
 */
function ml_access_from_sub_row( $row, $grace_seconds, $now_ts ) {
    if ( ! $row || empty( $row->status ) ) return false;
    if ( in_array( $row->status, array( 'active', 'trialing' ), true ) ) return true;
    if ( $row->status === 'past_due' ) {
        $cpe = ! empty( $row->current_period_end ) ? strtotime( $row->current_period_end . ' UTC' ) : 0;
        return $cpe > 0 && ( $now_ts - $cpe ) <= $grace_seconds;
    }
    return false;
}

/**
 * Is this a logged-in user that should have portal access?
 */
function ml_user_has_access( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) return false;
    if ( user_can( $user_id, ML_CAP_MANAGE ) ) return true;

    // Inert/back-compat: until Stripe is live, any logged-in customer keeps
    // access (matches the booking-only model, so existing customers aren't
    // locked out). Once payments are configured, gate on subscription status.
    if ( ! function_exists( 'ml_stripe_is_configured' ) || ! ml_stripe_is_configured() ) {
        return user_can( $user_id, 'read' );
    }

    if ( function_exists( 'ml_get_subscription_row' ) ) {
        $row = ml_get_subscription_row( $user_id );
        return ml_access_from_sub_row( $row, ml_past_due_grace(), time() );
    }
    return false;
}

/**
 * Coarse access state, kept for callers that still read it.
 */
function ml_user_access_state( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) return 'no_purchase';
    if ( user_can( $user_id, ML_CAP_MANAGE ) ) return 'approved';
    return ml_user_has_access( $user_id ) ? 'active' : 'no_purchase';
}

/**
 * Any logged-in visitor may start a booking; access is granted once paid.
 */
function ml_user_can_book( $user_id ) {
    return (int) $user_id > 0;
}

/**
 * No approval queue in the autonomous model.
 */
function ml_user_is_pending_approval( $user_id ) {
    return false;
}
