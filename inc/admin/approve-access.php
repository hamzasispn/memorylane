<?php
/**
 * Memory Lane — admin "Approve & activate access" action.
 *
 * Triggered from the Customers list. Creates the Stripe Subscription
 * (with 365-day trial) and flips the user's setup state to approved.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_ml_approve_access', 'ml_handle_approve_access' );

function ml_handle_approve_access() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    check_admin_referer( 'ml_approve_access' );

    $user_id = (int) ( $_POST['user_id'] ?? 0 );
    $user    = $user_id ? get_user_by( 'id', $user_id ) : null;

    $redirect = wp_get_referer() ?: admin_url( 'admin.php?page=memorylane-customers' );

    if ( ! $user ) {
        wp_safe_redirect( add_query_arg( 'ml_err', rawurlencode( __( 'User not found.', 'memorylane' ) ), $redirect ) );
        exit;
    }

    $state = get_user_meta( $user_id, ML_META_SETUP_STATE, true );
    if ( $state === ML_SETUP_STATE_APPROVED ) {
        wp_safe_redirect( add_query_arg( 'ml_msg', rawurlencode( __( 'Already approved.', 'memorylane' ) ), $redirect ) );
        exit;
    }
    if ( $state !== ML_SETUP_STATE_PENDING ) {
        wp_safe_redirect( add_query_arg( 'ml_err', rawurlencode( __( 'User is not in pending state.', 'memorylane' ) ), $redirect ) );
        exit;
    }

    $result = ml_create_subscription_on_approval( $user_id );
    if ( ! $result['ok'] ) {
        wp_safe_redirect( add_query_arg( 'ml_err', rawurlencode( $result['error'] ), $redirect ) );
        exit;
    }

    update_user_meta( $user_id, ML_META_SETUP_STATE,       ML_SETUP_STATE_APPROVED );
    update_user_meta( $user_id, ML_META_SETUP_APPROVED_AT, current_time( 'mysql', true ) );
    update_user_meta( $user_id, ML_META_SETUP_APPROVED_BY, get_current_user_id() );
    wp_cache_delete( 'ml_access_' . $user_id, 'ml' );

    // Audit log.
    global $wpdb;
    $wpdb->insert( ml_table( 'admin_actions_log' ), array(
        'admin_id'       => get_current_user_id(),
        'target_user_id' => $user_id,
        'action'         => 'approve_access',
        'before_state'   => ML_SETUP_STATE_PENDING,
        'after_state'    => ML_SETUP_STATE_APPROVED,
        'reason'         => sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ),
        'created_at'     => current_time( 'mysql', true ),
    ) );

    // Customer email: "Your access is now active".
    ml_mail_send( $user->user_email, 'access_approved', array(
        'user' => $user,
    ), $user_id );

    wp_safe_redirect( add_query_arg( 'ml_msg', rawurlencode( __( 'Access approved. Subscription created in Stripe with 365-day trial.', 'memorylane' ) ), $redirect ) );
    exit;
}
