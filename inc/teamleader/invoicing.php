<?php
/**
 * Memory Lane — Teamleader invoicing on Stripe invoice.paid.
 * Creates a booked, paid invoice in Teamleader (single source of truth; Exact
 * sync is Teamleader's own integration). Idempotent per Stripe invoice id.
 */
defined( 'ABSPATH' ) || exit;

const ML_TL_OPT_INVOICE_QUEUE = 'ml_tl_invoice_queue';

/**
 * Build the invoices.create body (pure; VAT-inclusive amount).
 */
function ml_tl_build_invoice_body( $contact_id, $department_id, $tax_rate_id, $description, $amount_cents, $currency ) {
    return array(
        'invoicee'      => array( 'customer' => array( 'type' => 'contact', 'id' => (string) $contact_id ) ),
        'department_id' => (string) $department_id,
        'payment_term'  => array( 'type' => 'cash' ),
        'grouped_lines' => array( array(
            'line_items' => array( array(
                'quantity'    => 1,
                'description' => (string) $description,
                'unit_price'  => array(
                    'amount'   => number_format( ( (int) $amount_cents ) / 100, 2, '.', '' ),
                    'currency' => strtoupper( (string) $currency ),
                    'tax'      => 'including',
                ),
                'tax_rate_id' => (string) $tax_rate_id,
            ) ),
        ) ),
    );
}

/** Idempotency: user-meta key recording the TL invoice id for a Stripe invoice. */
function ml_tl_invoice_done_key( $stripe_invoice_id ) {
    return '_ml_tl_inv_' . preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $stripe_invoice_id );
}

/**
 * Push one paid invoice to Teamleader. $data keys:
 *   stripe_invoice_id, amount_cents, currency, description, name
 * Throws on API failure (caller decides retry).
 */
function ml_tl_push_invoice( $user, array $data ) {
    $sid = (string) ( $data['stripe_invoice_id'] ?? '' );
    if ( $sid && get_user_meta( $user->ID, ml_tl_invoice_done_key( $sid ), true ) ) {
        return; // already invoiced — idempotent no-op
    }
    if ( ! function_exists( 'ml_tl_invoicing_ready' ) || ! ml_tl_invoicing_ready() ) {
        throw new \RuntimeException( 'Teamleader invoicing not configured (department/VAT).' );
    }

    $contact_id = ml_tl_resolve_contact_id( $user, $data );
    if ( ! $contact_id ) throw new \RuntimeException( 'No Teamleader contact for invoice.' );

    $body = ml_tl_build_invoice_body(
        $contact_id, ml_tl_department_id(), ml_tl_tax_rate_id(),
        (string) ( $data['description'] ?? 'Memory Lane' ),
        (int) ( $data['amount_cents'] ?? 0 ),
        (string) ( $data['currency'] ?? 'eur' )
    );

    $created = ml_tl_request( 'invoices.create', $body );
    $inv_id  = $created['id'] ?? '';
    if ( ! $inv_id ) throw new \RuntimeException( 'invoices.create returned no id.' );

    ml_tl_request( 'invoices.book', array( 'id' => $inv_id, 'on' => gmdate( 'Y-m-d' ) ) );
    ml_tl_request( 'invoices.registerPayment', array(
        'id'      => $inv_id,
        'payment' => array(
            'amount'  => array(
                'amount'   => number_format( ( (int) ( $data['amount_cents'] ?? 0 ) ) / 100, 2, '.', '' ),
                'currency' => strtoupper( (string) ( $data['currency'] ?? 'eur' ) ),
            ),
            'paid_at' => gmdate( 'Y-m-d\TH:i:sP' ),
        ),
    ) );

    if ( $sid ) update_user_meta( $user->ID, ml_tl_invoice_done_key( $sid ), $inv_id );
}

/** Reuse the stored contact id, else find/create by email. */
function ml_tl_resolve_contact_id( $user, array $data ) {
    $cid = (string) get_user_meta( $user->ID, '_ml_tl_contact_id', true );
    if ( $cid ) return $cid;
    try {
        $found = ml_tl_request( 'contacts.list', array(
            'filter' => array( 'email' => array( 'type' => 'primary', 'email' => $user->user_email ) ),
            'page'   => array( 'size' => 1 ),
        ) );
        if ( ! empty( $found[0]['id'] ) ) {
            update_user_meta( $user->ID, '_ml_tl_contact_id', $found[0]['id'] );
            return $found[0]['id'];
        }
    } catch ( \Throwable $e ) {}
    $parts   = preg_split( '/\s+/', trim( (string) ( $data['name'] ?? $user->display_name ) ), 2 );
    $created = ml_tl_request( 'contacts.add', array(
        'first_name' => $parts[0] ?? $user->display_name,
        'last_name'  => $parts[1] ?? '',
        'emails'     => array( array( 'type' => 'primary', 'email' => $user->user_email ) ),
    ) );
    $cid = $created['id'] ?? '';
    if ( $cid ) update_user_meta( $user->ID, '_ml_tl_contact_id', $cid );
    return $cid;
}

/* ---- durable retry queue (drained by ml_cron_tl_retry) ---- */
function ml_tl_invoice_queue() {
    $q = get_option( ML_TL_OPT_INVOICE_QUEUE, array() );
    return is_array( $q ) ? $q : array();
}

function ml_tl_invoice_enqueue( $user_id, array $data ) {
    $q = ml_tl_invoice_queue();
    $q[] = array( 'user_id' => (int) $user_id, 'data' => $data, 'attempts' => 0, 'queued_at' => time() );
    if ( count( $q ) > 500 ) $q = array_slice( $q, -500 );
    update_option( ML_TL_OPT_INVOICE_QUEUE, $q, false );
}

function ml_tl_process_invoice_queue() {
    $q = ml_tl_invoice_queue();
    if ( empty( $q ) ) return;
    $remaining = array();
    foreach ( $q as $item ) {
        $user = get_user_by( 'id', (int) ( $item['user_id'] ?? 0 ) );
        if ( ! $user ) continue;
        try {
            ml_tl_push_invoice( $user, (array) ( $item['data'] ?? array() ) );
        } catch ( \Throwable $e ) {
            $item['attempts'] = (int) ( $item['attempts'] ?? 0 ) + 1;
            if ( $item['attempts'] < 8 ) $remaining[] = $item;
            else error_log( '[memorylane] TL invoice dropped after max attempts: ' . $e->getMessage() );
        }
    }
    update_option( ML_TL_OPT_INVOICE_QUEUE, $remaining, false );
}
add_action( 'ml_cron_tl_retry', 'ml_tl_process_invoice_queue' );
