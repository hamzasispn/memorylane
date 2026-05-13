<?php
/**
 * Memory Lane — cron: nag admin about customers waiting > SLA for approval.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'ml_cron_pending_approval_reminder', 'ml_cron_run_pending_approval_reminder' );

function ml_cron_run_pending_approval_reminder() {
    $users = get_users( array(
        'meta_key'   => ML_META_SETUP_STATE,
        'meta_value' => ML_SETUP_STATE_PENDING,
        'number'     => 200,
        'fields'     => array( 'ID', 'user_email' ),
    ) );
    if ( empty( $users ) ) return;

    $now      = time();
    $overdue  = array();
    $sla_secs = ML_APPROVAL_SLA_HOURS * HOUR_IN_SECONDS;

    foreach ( $users as $u ) {
        $paid_at_str = get_user_meta( $u->ID, ML_META_SETUP_PAID_AT, true );
        if ( ! $paid_at_str ) continue;
        $paid_ts = strtotime( $paid_at_str . ' UTC' );
        if ( ! $paid_ts ) continue;
        $age = $now - $paid_ts;
        if ( $age < $sla_secs ) continue;

        $amount   = (int) get_user_meta( $u->ID, ML_META_SETUP_AMOUNT, true );
        $currency = strtoupper( (string) get_user_meta( $u->ID, ML_META_SETUP_CURRENCY, true ) );
        $overdue[] = array(
            'email'   => $u->user_email,
            'paid_at' => $paid_at_str,
            'hours'   => round( $age / HOUR_IN_SECONDS, 1 ),
            'amount'  => $amount ? ( $currency . ' ' . number_format( $amount / 100, 2 ) ) : '—',
        );
    }

    if ( empty( $overdue ) ) return;

    // Don't spam: only send once per 12h.
    if ( (int) get_option( 'ml_last_pending_reminder' ) > ( $now - 12 * HOUR_IN_SECONDS ) ) return;
    update_option( 'ml_last_pending_reminder', $now, false );

    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_pending_overdue', array( 'pending' => $overdue ) );
    }
}
