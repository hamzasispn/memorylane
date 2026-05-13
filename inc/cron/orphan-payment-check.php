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

    global $wpdb;
    $react_tbl = ml_table( 'reactivations' );

    $orphans = array();
    foreach ( $sessions->data as $s ) {
        if ( $s->status !== 'complete' && $s->payment_status !== 'paid' ) continue;
        if ( empty( $s->customer ) ) continue;
        $cust_id = is_object( $s->customer ) ? $s->customer->id : (string) $s->customer;
        $intent  = $s->metadata->ml_intent ?? '';

        if ( $intent === 'reactivation' || $s->mode === 'subscription' ) {
            // Reactivation orphan: paid Checkout with no matching wp_ml_reactivations row.
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$react_tbl} WHERE stripe_checkout_session_id=%s", $s->id ) );
            if ( ! $exists ) {
                $orphans[] = array(
                    'session' => $s->id,
                    'customer'=> $cust_id,
                    'email'   => $s->customer_details->email ?? '',
                    'amount'  => $s->amount_total,
                    'intent'  => 'reactivation',
                );
            }
            continue;
        }

        $uid = ml_user_id_by_stripe_customer( $cust_id );
        if ( ! $uid ) {
            $orphans[] = array(
                'session' => $s->id,
                'customer'=> $cust_id,
                'email'   => $s->customer_details->email ?? '',
                'amount'  => $s->amount_total,
                'intent'  => 'initial_purchase',
            );
        }
    }

    if ( empty( $orphans ) ) return;

    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_orphan_payments', array( 'orphans' => $orphans ) );
    }
}
