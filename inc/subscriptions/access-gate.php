<?php
/**
 * Memory Lane — access gate (booking-only model).
 *
 * Payments + subscriptions were removed. Access is now simply: any logged-in
 * customer (or admin) may use the dashboard, view their tours, and book.
 * The function names are kept so the dashboard/booking callers need no changes.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Is this a logged-in user that should have portal access?
 */
function ml_user_has_access( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) return false;
    if ( user_can( $user_id, ML_CAP_MANAGE ) ) return true;
    return in_array( ML_ROLE_CUSTOMER, (array) get_userdata( $user_id )->roles ?? array(), true )
        || user_can( $user_id, 'read' );
}

/**
 * Coarse access state, kept for callers that still read it.
 * Returns 'approved' for admins, 'active' for customers, else 'no_purchase'.
 */
function ml_user_access_state( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) return 'no_purchase';
    if ( user_can( $user_id, ML_CAP_MANAGE ) ) return 'approved';
    return ml_user_has_access( $user_id ) ? 'active' : 'no_purchase';
}

/**
 * Any logged-in customer may book.
 */
function ml_user_can_book( $user_id ) {
    return ml_user_has_access( $user_id );
}

/**
 * No approval queue anymore.
 */
function ml_user_is_pending_approval( $user_id ) {
    return false;
}
