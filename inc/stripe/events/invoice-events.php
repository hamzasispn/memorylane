<?php
/**
 * Memory Lane — Stripe invoice events.
 */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_invoice_payment_succeeded( \Stripe\Event $event ) {
    $invoice = $event->data->object;
    $user_id = ml_user_id_by_stripe_customer( $invoice->customer );
    if ( ! $user_id ) return;

    // Refresh sub state by fetching latest subscription.
    if ( $invoice->subscription ) {
        try {
            $sub = ml_stripe()->subscriptions->retrieve( $invoice->subscription );
            ml_upsert_subscription( $user_id, array(
                'stripe_customer_id'   => $sub->customer,
                'stripe_sub_id'        => $sub->id,
                'status'               => $sub->status,
                'current_period_end'   => $sub->current_period_end ? gmdate( 'Y-m-d H:i:s', $sub->current_period_end ) : null,
                'cancel_at_period_end' => $sub->cancel_at_period_end ? 1 : 0,
                'raw_json'             => wp_json_encode( $sub->toArray() ),
            ) );
        } catch ( \Throwable $e ) {
            error_log( '[memorylane] invoice.payment_succeeded sub fetch failed: ' . $e->getMessage() );
        }
    }

    // Only email a receipt for the monthly phase — skip the initial setup invoice (handled by purchase_confirmation).
    $billing_reason = $invoice->billing_reason ?? '';
    if ( $billing_reason === 'subscription_cycle' ) {
        $user = get_user_by( 'id', $user_id );
        if ( $user ) {
            ml_mail_send( $user->user_email, 'monthly_payment_receipt', array(
                'user'        => $user,
                'amount_paid' => $invoice->amount_paid,
                'currency'    => $invoice->currency,
                'invoice_url' => $invoice->hosted_invoice_url ?? '',
            ), $user_id );
        }
    }

    wp_cache_delete( 'ml_access_' . $user_id, 'ml' );
}

function ml_stripe_event_invoice_payment_failed( \Stripe\Event $event ) {
    $invoice = $event->data->object;
    $user_id = ml_user_id_by_stripe_customer( $invoice->customer );
    if ( ! $user_id ) return;

    $user = get_user_by( 'id', $user_id );
    if ( $user ) {
        ml_mail_send( $user->user_email, 'payment_failed', array(
            'user'         => $user,
            'invoice_url'  => $invoice->hosted_invoice_url ?? '',
            'next_attempt' => $invoice->next_payment_attempt ?? null,
        ), $user_id );
    }

    // If this is the final attempt, alert admin.
    if ( empty( $invoice->next_payment_attempt ) ) {
        foreach ( ml_admin_recipients() as $to ) {
            ml_mail_send( $to, 'admin_payment_failed_final', array(
                'user'    => $user,
                'invoice' => $invoice,
            ) );
        }
    }
}

function ml_stripe_event_invoice_upcoming( \Stripe\Event $event ) {
    $invoice = $event->data->object;
    $user_id = ml_user_id_by_stripe_customer( $invoice->customer );
    if ( ! $user_id ) return;
    $user = get_user_by( 'id', $user_id );
    if ( ! $user ) return;
    ml_mail_send( $user->user_email, 'subscription_renewal_warning', array(
        'user'        => $user,
        'amount_due'  => $invoice->amount_due,
        'currency'    => $invoice->currency,
        'period_end'  => $invoice->period_end ?? null,
    ), $user_id );
}
