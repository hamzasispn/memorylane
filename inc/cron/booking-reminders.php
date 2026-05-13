<?php
/**
 * Memory Lane — cron: send 24h booking reminders.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'ml_cron_booking_reminders', 'ml_cron_run_booking_reminders' );

function ml_cron_run_booking_reminders() {
    global $wpdb;
    $tbl = ml_table( 'bookings' );
    $log = ml_table( 'email_log' );

    // Bookings starting in 22-26h, not yet reminded.
    $rows = $wpdb->get_results(
        "SELECT * FROM {$tbl}
         WHERE status='confirmed'
           AND scheduled_for BETWEEN DATE_ADD(UTC_TIMESTAMP(), INTERVAL 22 HOUR) AND DATE_ADD(UTC_TIMESTAMP(), INTERVAL 26 HOUR)"
    );
    foreach ( $rows as $b ) {
        $sent = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$log} WHERE user_id=%d AND template='booking_reminder' AND created_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY) LIMIT 1",
            $b->user_id
        ) );
        if ( $sent ) continue;
        $user = get_user_by( 'id', $b->user_id );
        if ( ! $user ) continue;
        ml_mail_send( $user->user_email, 'booking_reminder', array(
            'user'    => $user,
            'booking' => $b,
        ), $user->ID );
    }
}
