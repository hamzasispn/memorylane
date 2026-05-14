<?php
/**
 * Memory Lane — cron: archive tours after the past-due grace period (V2-5).
 *
 * When Stripe exhausts payment retries the subscription flips to `unpaid`
 * and we stamp ml_subscriptions.payment_failed_at. After
 * ml_past_due_grace_seconds (default 7 days), this cron archives the
 * customer's tours and notifies them + admin.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'ml_cron_revoke_overdue', 'ml_cron_run_revoke_overdue' );

function ml_cron_run_revoke_overdue() {
    global $wpdb;
    $sub_tbl = ml_table( 'subscriptions' );

    $grace_seconds = ml_past_due_grace_seconds();
    $cutoff_utc    = gmdate( 'Y-m-d H:i:s', time() - $grace_seconds );

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, user_id, payment_failed_at, status
           FROM {$sub_tbl}
          WHERE payment_failed_at IS NOT NULL
            AND payment_failed_at <= %s
            AND status NOT IN ('active','trialing','canceled','cancelled','incomplete_expired')
          LIMIT 200",
        $cutoff_utc
    ) );

    if ( empty( $rows ) ) return;

    foreach ( $rows as $r ) {
        ml_revoke_overdue_user( (int) $r->user_id );

        // Clear payment_failed_at so we don't fire again on next cron tick.
        $wpdb->update(
            $sub_tbl,
            array( 'payment_failed_at' => null, 'updated_at' => current_time( 'mysql', true ) ),
            array( 'id' => (int) $r->id )
        );
    }
}

/**
 * Archive all of a user's active tours, email them + admin.
 */
function ml_revoke_overdue_user( $user_id ) {
    $tour_ids = get_posts( array(
        'post_type'   => ML_CPT_TOUR,
        'post_status' => 'any',
        'numberposts' => -1,
        'fields'      => 'ids',
        'meta_query'  => array(
            array( 'key' => ML_META_TOUR_USER,   'value' => (int) $user_id ),
            array( 'key' => ML_META_TOUR_STATUS, 'value' => ML_TOUR_STATUS_ACTIVE ),
        ),
    ) );

    foreach ( $tour_ids as $tid ) {
        update_post_meta( $tid, ML_META_TOUR_STATUS, ML_TOUR_STATUS_ARCHIVED );
    }

    $user = get_userdata( $user_id );
    if ( $user ) {
        ml_mail_send( $user->user_email, 'subscription_cancelled', array(
            'user' => $user,
        ), $user_id );

        foreach ( ml_admin_recipients() as $to ) {
            ml_mail_send( $to, 'admin_subscription_cancelled', array(
                'user'   => $user,
                'reason' => 'past_due_grace_expired',
            ) );
        }
    }

    wp_cache_delete( 'ml_access_' . (int) $user_id, 'ml' );
}
