<?php
/**
 * Memory Lane — cron: check subscription expirations.
 * Marks subscriptions cancelled when current_period_end has passed AND
 * cancel_at_period_end is set. Revokes access; flags tours pending_archive.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'ml_cron_check_expirations', 'ml_cron_run_check_expirations' );

function ml_cron_run_check_expirations() {
    global $wpdb;
    $tbl = ml_table( 'subscriptions' );
    $rows = $wpdb->get_results(
        "SELECT * FROM {$tbl}
         WHERE cancel_at_period_end=1
           AND status IN ('active','past_due','trialing')
           AND current_period_end IS NOT NULL
           AND current_period_end < UTC_TIMESTAMP()"
    );
    foreach ( $rows as $row ) {
        $wpdb->update( $tbl, array(
            'status'     => 'cancelled',
            'updated_at' => current_time( 'mysql', true ),
        ), array( 'id' => $row->id ) );

        ml_flag_user_tours_pending_archive( $row->user_id );
        wp_cache_delete( 'ml_access_' . $row->user_id, 'ml' );

        $user = get_user_by( 'id', $row->user_id );
        if ( $user ) {
            ml_mail_send( $user->user_email, 'subscription_cancelled', array(
                'user'     => $user,
                'end_date' => strtotime( $row->current_period_end . ' UTC' ),
            ), $user->ID );
        }
    }
}
