<?php
/**
 * Memory Lane — access gate (3-state model).
 *
 * States a customer can be in:
 *   - no_purchase          : never paid → no portal access at all (login still works)
 *   - pending_approval     : paid setup fee, waiting for admin to approve (≤ 8h SLA)
 *                            → can: log in, see dashboard, BOOK a scan, see "pending" status
 *                            → cannot: view tours, manage subscription (no sub yet)
 *   - approved             : admin approved, Stripe subscription created with 365-day trial
 *                            → full portal access (book, view tours, manage sub)
 *   - cancelled / past_due : subscription lifecycle states (handled by ml_user_has_access)
 */
defined( 'ABSPATH' ) || exit;

/**
 * The granular state of a user's portal access.
 * Returns one of: no_purchase | pending_approval | approved | active | past_due | cancelled
 */
function ml_user_access_state( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) return 'no_purchase';

    // Admins always count as approved+active.
    if ( user_can( $user_id, ML_CAP_MANAGE ) ) return 'approved';

    $setup = get_user_meta( $user_id, ML_META_SETUP_STATE, true );
    if ( ! $setup ) return 'no_purchase';

    if ( $setup === ML_SETUP_STATE_PENDING ) return 'pending_approval';
    if ( $setup === ML_SETUP_STATE_REFUNDED ) return 'cancelled';

    // approved → check subscription status.
    $row = ml_get_subscription_row( $user_id );
    if ( ! $row ) return 'approved'; // approved but subscription create hasn't completed yet

    return (string) $row->status;
}

/**
 * Can this user view their tour iframe? (Full access required.)
 */
function ml_user_has_access( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) return false;

    if ( user_can( $user_id, ML_CAP_MANAGE ) ) return true;

    $cached = wp_cache_get( 'ml_access_' . $user_id, 'ml' );
    if ( $cached !== false ) return (bool) $cached;

    $result = ml_compute_access( $user_id );
    wp_cache_set( 'ml_access_' . $user_id, $result ? 1 : 0, 'ml', 60 );
    return $result;
}

function ml_compute_access( $user_id ) {
    // Must be approved.
    if ( get_user_meta( $user_id, ML_META_SETUP_STATE, true ) !== ML_SETUP_STATE_APPROVED ) {
        return false;
    }

    $row = ml_get_subscription_row( $user_id );
    if ( ! $row ) return false;

    if ( ! in_array( $row->status, ml_active_subscription_statuses(), true ) ) return false;

    $period_end_ts = $row->current_period_end ? strtotime( $row->current_period_end . ' UTC' ) : 0;
    if ( ! $period_end_ts ) return false;

    if ( in_array( $row->status, array( 'active', 'trialing' ), true ) ) {
        return $period_end_ts > time();
    }

    // past_due: grace window.
    return ( $period_end_ts + ml_past_due_grace_seconds() ) > time();
}

/**
 * Can the user book a scan?
 * Yes for anyone who has paid (pending_approval is fine — they need to book to schedule the scan).
 */
function ml_user_can_book( $user_id ) {
    $state = ml_user_access_state( $user_id );
    return in_array( $state, array( 'pending_approval', 'approved', 'active', 'trialing', 'past_due' ), true );
}

/**
 * Has the user paid the setup fee but not yet been approved?
 */
function ml_user_is_pending_approval( $user_id ) {
    return get_user_meta( (int) $user_id, ML_META_SETUP_STATE, true ) === ML_SETUP_STATE_PENDING;
}
