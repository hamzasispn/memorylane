<?php
/** Stripe event: invoice.paid → push a paid invoice to Teamleader. */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_invoice_paid( \Stripe\Event $event ) {
    $inv = $event->data->object;
    $customer_id = is_object( $inv->customer ?? null ) ? ( $inv->customer->id ?? '' ) : (string) ( $inv->customer ?? '' );
    if ( ! $customer_id ) return;

    // Map Stripe customer → WP user.
    $users = get_users( array( 'meta_key' => ML_META_STRIPE_CUSTOMER, 'meta_value' => $customer_id, 'number' => 1, 'fields' => 'ID' ) );
    if ( empty( $users ) ) { error_log( '[memorylane] invoice.paid: no user for customer ' . $customer_id ); return; }
    $user = get_user_by( 'id', (int) $users[0] );
    if ( ! $user ) return;

    $reason = (string) ( $inv->billing_reason ?? '' );
    $desc   = ( $reason === 'subscription_create' ) ? 'Memory Lane — Activatie' : 'Memory Lane — Jaarlijkse verlenging';
    $data = array(
        'stripe_invoice_id' => (string) ( $inv->id ?? '' ),
        'amount_cents'      => (int) ( $inv->amount_paid ?? 0 ),
        'currency'          => (string) ( $inv->currency ?? 'eur' ),
        'description'       => $desc,
        'name'              => $user->display_name,
    );

    try {
        ml_tl_push_invoice( $user, $data );
    } catch ( \Throwable $e ) {
        // Swallow into the durable queue so the webhook returns 200 (the receiver's
        // event-id dedup would otherwise block Stripe re-delivery). ml_cron_tl_retry drains it.
        error_log( '[memorylane] invoice.paid TL push failed, queued: ' . $e->getMessage() );
        ml_tl_invoice_enqueue( $user->ID, $data );
    }
}
