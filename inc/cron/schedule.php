<?php
/**
 * Memory Lane — cron schedule registration.
 * Note: WP-cron is page-visit-driven. For production reliability:
 *   define( 'DISABLE_WP_CRON', true ) in wp-config.php
 *   + OS cron hitting https://site/wp-cron.php?doing_wp_cron every 5 min.
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'cron_schedules', function ( $schedules ) {
    if ( ! isset( $schedules['ml_quarter_hour'] ) ) {
        $schedules['ml_quarter_hour'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => 'Every 15 minutes',
        );
    }
    return $schedules;
} );

/**
 * Schedule jobs on theme activation.
 */
add_action( 'after_switch_theme', 'ml_cron_schedule_all' );

/**
 * Also self-heal on every admin_init in case schedule got cleared.
 */
add_action( 'admin_init', 'ml_cron_schedule_all' );

function ml_cron_schedule_all() {
    $jobs = array(
        'ml_cron_check_expirations'         => 'hourly',
        'ml_cron_renewal_warnings'          => 'daily',
        'ml_cron_retry_webhooks'            => 'ml_quarter_hour',
        'ml_cron_retry_emails'              => 'ml_quarter_hour',
        'ml_cron_orphan_payment_check'      => 'daily',
        'ml_cron_booking_reminders'         => 'hourly',
        'ml_cron_overdue_tour_archive'      => 'daily',
        'ml_cron_finalize_schedules'        => 'hourly',
        'ml_cron_pending_approval_reminder' => 'hourly',
        'ml_cron_reactivation_overdue'      => 'hourly',
        'ml_cron_release_stale_holds'       => 'hourly',
        'ml_cron_revoke_overdue'            => 'hourly',
    );
    foreach ( $jobs as $hook => $recur ) {
        if ( ! wp_next_scheduled( $hook ) ) {
            wp_schedule_event( time() + 60, $recur, $hook );
        }
    }
}
