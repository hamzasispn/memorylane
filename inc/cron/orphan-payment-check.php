<?php
/**
 * Memory Lane — cron: orphan payment check.
 * Finds Stripe checkout sessions from the last 24h that did NOT create a WP user.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'ml_cron_orphan_payment_check', 'ml_cron_run_orphan_payment_check' );

function ml_cron_run_orphan_payment_check() {
    if ( ! ml_stripe_is_configured() ) return;
    $stripe = ml_stripe();

    try {
        $sessions = $stripe->checkout->sessions->all( array(
            'limit'   => 100,
            'created' => array( 'gte' => time() - DAY_IN_SECONDS ),
        ) );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] orphan_payment_check: ' . $e->getMessage() );
        return;
    }

    $orphans = array();
    foreach ( $sessions->data as $s ) {
        if ( $s->payment_status !== 'paid' && $s->status !== 'complete' ) continue;
        if ( empty( $s->customer ) ) continue;
        $uid = ml_user_id_by_stripe_customer( $s->customer );
        if ( ! $uid ) {
            $orphans[] = array(
                'session' => $s->id,
                'customer'=> $s->customer,
                'email'   => $s->customer_details->email ?? '',
                'amount'  => $s->amount_total,
            );
        }
    }

    if ( empty( $orphans ) ) return;

    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_orphan_payments', array( 'orphans' => $orphans ) );
    }
}
