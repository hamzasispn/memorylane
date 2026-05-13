<?php
/**
 * Memory Lane — cron: send renewal warnings 7 days before year-one ends.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'ml_cron_renewal_warnings', 'ml_cron_run_renewal_warnings' );

function ml_cron_run_renewal_warnings() {
    global $wpdb;
    $tbl = ml_table( 'subscriptions' );
    $log = ml_table( 'email_log' );

    // Subscriptions where year_one_end_date is between 6.5 and 7.5 days away.
    $rows = $wpdb->get_results(
        "SELECT * FROM {$tbl}
         WHERE status IN ('active','trialing')
           AND year_one_end_date IS NOT NULL
           AND year_one_end_date BETWEEN DATE_ADD(UTC_TIMESTAMP(), INTERVAL 6 DAY) AND DATE_ADD(UTC_TIMESTAMP(), INTERVAL 8 DAY)"
    );

    foreach ( $rows as $row ) {
        // Skip if already sent for this subscription.
        $already = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$log} WHERE user_id=%d AND template='subscription_renewal_warning' AND created_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 14 DAY) LIMIT 1",
            $row->user_id
        ) );
        if ( $already ) continue;

        $user = get_user_by( 'id', $row->user_id );
        if ( ! $user ) continue;

        ml_mail_send( $user->user_email, 'subscription_renewal_warning', array(
            'user'       => $user,
            'period_end' => strtotime( $row->year_one_end_date . ' UTC' ),
        ), $user->ID );
    }
}
