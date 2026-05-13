<?php
/**
 * Memory Lane — cron: retry failed webhooks.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'ml_cron_retry_webhooks', 'ml_cron_run_retry_webhooks' );

function ml_cron_run_retry_webhooks() {
    global $wpdb;
    $tbl  = ml_table( 'webhook_events' );
    $rows = $wpdb->get_results(
        "SELECT * FROM {$tbl}
         WHERE status='failed' AND retry_count < 5
         ORDER BY id ASC
         LIMIT 20"
    );
    foreach ( $rows as $row ) {
        try {
            $arr   = json_decode( $row->payload, true );
            if ( ! $arr ) continue;
            $event = \Stripe\Event::constructFrom( $arr );
            ml_stripe_dispatch_event( $event );
            $wpdb->update( $tbl, array(
                'status'       => 'processed',
                'processed_at' => current_time( 'mysql', true ),
                'error_msg'    => null,
            ), array( 'id' => $row->id ) );
        } catch ( \Throwable $e ) {
            $wpdb->update( $tbl, array(
                'retry_count' => $row->retry_count + 1,
                'error_msg'   => substr( $e->getMessage(), 0, 1000 ),
            ), array( 'id' => $row->id ) );
        }
    }
}

add_action( 'ml_cron_retry_emails', 'ml_cron_run_retry_emails' );

function ml_cron_run_retry_emails() {
    global $wpdb;
    $tbl  = ml_table( 'email_log' );
    $rows = $wpdb->get_results(
        "SELECT * FROM {$tbl}
         WHERE status='failed' AND retry_count < 3
         ORDER BY id ASC
         LIMIT 20"
    );
    foreach ( $rows as $row ) {
        ml_mail_retry( $row->id );
    }
}
