<?php
/**
 * Memory Lane — cron: finalize Stripe schedules that failed during checkout.
 * Picks rows where status='schedule_pending' and retries conversion.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'ml_cron_finalize_schedules', 'ml_cron_run_finalize_schedules' );
add_action( 'ml_cron_overdue_tour_archive', 'ml_cron_run_overdue_tour_archive' );

function ml_cron_run_finalize_schedules() {
    if ( ! ml_stripe_is_configured() ) return;
    global $wpdb;
    $tbl = ml_table( 'subscriptions' );
    $rows = $wpdb->get_results(
        "SELECT * FROM {$tbl} WHERE status='schedule_pending' AND stripe_sub_id IS NOT NULL LIMIT 20"
    );
    foreach ( $rows as $r ) {
        try {
            $sub = ml_stripe()->subscriptions->retrieve( $r->stripe_sub_id );
            $year_end = $sub->current_period_end;
            $schedule_id = ml_stripe_convert_to_schedule( $sub->id, $year_end );

            $wpdb->update( $tbl, array(
                'stripe_schedule_id' => $schedule_id,
                'status'             => $sub->status,
                'updated_at'         => current_time( 'mysql', true ),
            ), array( 'id' => $r->id ) );
        } catch ( \Throwable $e ) {
            error_log( '[memorylane] finalize_schedule failed: ' . $e->getMessage() );
        }
    }
}

function ml_cron_run_overdue_tour_archive() {
    global $wpdb;
    // Tours flagged pending_archive > 7 days.
    $cutoff = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
    $tour_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT p.ID FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key=%s AND pm.meta_value=%s
         WHERE p.post_type=%s AND p.post_modified_gmt < %s",
        ML_META_TOUR_STATUS, ML_TOUR_STATUS_PENDING_ARCHIVE, ML_CPT_TOUR, $cutoff
    ) );
    if ( empty( $tour_ids ) ) return;

    $list = array();
    foreach ( $tour_ids as $id ) {
        $list[] = array(
            'address' => get_post_meta( $id, ML_META_TOUR_ADDRESS, true ),
            'url'     => get_post_meta( $id, ML_META_TOUR_URL, true ),
        );
    }

    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_tour_pending_archive', array( 'tours' => $list ) );
    }
}
