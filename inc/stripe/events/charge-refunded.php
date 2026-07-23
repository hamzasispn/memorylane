<?php
/** Stripe event: charge.refunded → Teamleader credit note. */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_charge_refunded( \Stripe\Event $event ) {
    $charge = $event->data->object;
    $customer_id = is_object( $charge->customer ?? null ) ? ( $charge->customer->id ?? '' ) : (string) ( $charge->customer ?? '' );
    if ( ! $customer_id ) return;
    $users = get_users( array( 'meta_key' => ML_META_STRIPE_CUSTOMER, 'meta_value' => $customer_id, 'number' => 1, 'fields' => 'ID' ) );
    if ( empty( $users ) ) return;
    $user = get_user_by( 'id', (int) $users[0] );
    if ( ! $user ) return;

    $invoice_id = (string) ( $charge->invoice ?? '' );
    if ( ! $invoice_id ) return; // only invoice-linked charges map to a TL invoice
    $refunded = (int) ( $charge->amount_refunded ?? 0 );
    if ( $refunded <= 0 ) return;

    if ( function_exists( 'ml_tl_push_credit_note' ) ) {
        try { ml_tl_push_credit_note( $user, $invoice_id, (string) ( $charge->id ?? '' ), $refunded, (string) ( $charge->currency ?? 'eur' ) ); }
        catch ( \Throwable $e ) { error_log( '[memorylane] credit note failed: ' . $e->getMessage() ); }
    }
}
