<?php
/**
 * Memory Lane — Stripe event branch: reactivation checkout completed.
 *
 * Triggered from the main checkout.session.completed handler when
 * mode=subscription AND metadata.ml_intent='reactivation'.
 *
 * Records the cycle, flips tours to pending_reactivation, notifies customer + admin.
 * Idempotent via UNIQUE(stripe_checkout_session_id) on wp_ml_reactivations.
 */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_reactivation_completed( $session ) {
    // Always re-derive user from Stripe customer — never trust client metadata.
    $cust_id = is_object( $session->customer ) ? $session->customer->id : (string) $session->customer;
    $user_id = ml_user_id_by_stripe_customer( $cust_id );
    if ( ! $user_id ) {
        // Orphan — leave for daily orphan-payment-check cron.
        error_log( '[memorylane] reactivation completed but no matching user for customer ' . $cust_id );
        return;
    }

    ml_record_reactivation_payment( $session, $user_id );
}

/**
 * charge.refunded handler. If the refunded charge corresponds to a reactivation
 * payment intent, treat it as reactivation refund.
 */
function ml_stripe_event_charge_refunded( \Stripe\Event $event ) {
    $charge = $event->data->object;
    $pi_id  = is_object( $charge->payment_intent ) ? $charge->payment_intent->id : (string) ( $charge->payment_intent ?? '' );
    if ( ! $pi_id ) return;

    ml_mark_reactivation_refunded( $pi_id );
}
