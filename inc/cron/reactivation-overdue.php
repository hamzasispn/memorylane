<?php
/**
 * Memory Lane — cron: digest admin about reactivations waiting > SLA.
 * Mirror of pending-approval-reminder.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'ml_cron_reactivation_overdue', 'ml_cron_run_reactivation_overdue' );

function ml_cron_run_reactivation_overdue() {
    global $wpdb;
    $tbl = ml_table( 'reactivations' );

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$tbl} WHERE status = %s AND requested_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d HOUR) ORDER BY requested_at ASC",
        ML_REACTIVATION_STATUS_PENDING,
        ML_REACTIVATION_SLA_HOURS
    ) );
    if ( empty( $rows ) ) return;

    $now = time();
    if ( (int) get_option( 'ml_last_reactivation_reminder' ) > ( $now - 12 * HOUR_IN_SECONDS ) ) return;
    update_option( 'ml_last_reactivation_reminder', $now, false );

    $overdue = array();
    foreach ( $rows as $r ) {
        $u = get_user_by( 'id', $r->user_id );
        $ts = strtotime( $r->requested_at . ' UTC' );
        $overdue[] = array(
            'email'   => $u ? $u->user_email : '—',
            'cycle'   => (int) $r->cycle_number,
            'plan'    => $r->plan_chosen,
            'hours'   => $ts ? round( ( $now - $ts ) / HOUR_IN_SECONDS, 1 ) : 0,
            'tour_n'  => $u ? ml_count_user_tours( $r->user_id ) : 0,
            'row_id'  => (int) $r->id,
        );
    }

    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_reactivation_overdue', array( 'overdue' => $overdue ) );
    }
}
