<?php
/**
 * Memory Lane — admin manual action handlers.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_ml_admin_resend_welcome', function () {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    check_admin_referer( 'ml_admin_resend_welcome' );
    $user_id = (int) ( $_POST['user_id'] ?? 0 );
    $user    = get_user_by( 'id', $user_id );
    if ( $user ) ml_send_reset_email( $user, 'welcome_set_password' );

    global $wpdb;
    $wpdb->insert( ml_table( 'admin_actions_log' ), array(
        'admin_id'       => get_current_user_id(),
        'target_user_id' => $user_id,
        'action'         => 'resend_welcome',
        'created_at'     => current_time( 'mysql', true ),
    ) );
    wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=memorylane-customers' ) );
    exit;
} );
