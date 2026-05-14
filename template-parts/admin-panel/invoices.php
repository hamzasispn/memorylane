<?php defined( 'ABSPATH' ) || exit;
// Pulls last N invoices live from Stripe (cached). Filterable by customer email.
$customer_q = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
$page       = ml_ap_current_page();
$per_page   = ml_ap_per_page( 25 );

if ( ! ml_stripe_is_configured() ) {
    echo '<div class="mla-card"><p class="mla-muted">Stripe is not configured. Open <a href="' . esc_url( home_url( '/admin/settings' ) ) . '">Settings</a>.</p></div>';
    return;
}

// Resolve customer ID if a search query was given.
$customer_id = '';
if ( $customer_q ) {
    $u = get_user_by( 'email', $customer_q );
    if ( $u ) {
        $customer_id = (string) get_user_meta( $u->ID, ML_META_STRIPE_CUSTOMER, true );
    }
}

$cache_key = 'mla_invoices_' . md5( $customer_id );
$all       = get_transient( $cache_key );
if ( ! is_array( $all ) ) {
    try {
        $args = array( 'limit' => 100 );
        if ( $customer_id ) $args['customer'] = $customer_id;
        $resp = ml_stripe()->invoices->all( $args );
        $all  = array();
        foreach ( $resp->data as $inv ) {
            $all[] = array(
                'id'          => $inv->id,
                'number'      => $inv->number,
                'created'     => (int) $inv->created,
                'status'      => (string) $inv->status,
                'currency'    => strtoupper( (string) $inv->currency ),
                'amount'      => (int) ( $inv->amount_paid > 0 ? $inv->amount_paid : $inv->amount_due ),
                'customer'    => (string) $inv->customer,
                'customer_email' => (string) ( $inv->customer_email ?? '' ),
                'invoice_pdf' => (string) ( $inv->invoice_pdf ?? '' ),
                'hosted'      => (string) ( $inv->hosted_invoice_url ?? '' ),
            );
        }
        set_transient( $cache_key, $all, 300 );
    } catch ( \Throwable $e ) {
        echo '<div class="mla-banner is-danger">Stripe error: ' . esc_html( $e->getMessage() ) . '</div>';
        $all = array();
    }
}

$total  = count( $all );
$offset = ( $page - 1 ) * $per_page;
$rows   = array_slice( $all, $offset, $per_page );

$base = home_url( '/admin/invoices' ) . ( $customer_q ? '?q=' . rawurlencode( $customer_q ) : '' );
$stripe_url_base = 'https://dashboard.stripe.com/' . ( ml_stripe_mode() === 'live' ? '' : 'test/' );
?>

<div class="mla-toolbar">
    <form method="get" style="display:flex;gap:8px;">
        <input type="search" name="q" value="<?php echo esc_attr( $customer_q ); ?>" placeholder="Customer email…">
        <button class="mla-btn mla-btn--secondary" type="submit">Filter</button>
        <?php if ( $customer_q ) : ?>
            <a class="mla-btn mla-btn--ghost" href="<?php echo esc_url( home_url( '/admin/invoices' ) ); ?>">Clear</a>
        <?php endif; ?>
    </form>
    <span class="mla-muted" style="margin-left:auto;"><?php echo (int) $total; ?> invoices (cached 5 min)</span>
</div>

<div class="mla-card" style="padding:0;">
    <table class="mla-table">
        <thead><tr><th>Date</th><th>Number</th><th>Customer</th><th>Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ( $rows as $inv ) :
            $pill = array( 'paid' => 'mla-pill--success', 'open' => 'mla-pill--warning', 'uncollectible' => 'mla-pill--danger', 'void' => 'mla-pill--neutral', 'draft' => 'mla-pill--neutral' )[ $inv['status'] ] ?? 'mla-pill--neutral';
        ?>
            <tr>
                <td><?php echo esc_html( ml_format_date( gmdate( 'Y-m-d H:i:s', $inv['created'] ) ) ); ?></td>
                <td><?php echo esc_html( $inv['number'] ?: $inv['id'] ); ?></td>
                <td><?php echo esc_html( $inv['customer_email'] ?: $inv['customer'] ); ?></td>
                <td><?php echo esc_html( $inv['currency'] . ' ' . number_format( $inv['amount'] / 100, 2 ) ); ?></td>
                <td><span class="mla-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( $inv['status'] ); ?></span></td>
                <td style="text-align:right;white-space:nowrap;">
                    <?php if ( $inv['invoice_pdf'] ) : ?>
                        <a class="mla-btn mla-btn--ghost" target="_blank" rel="noopener" href="<?php echo esc_url( $inv['invoice_pdf'] ); ?>">PDF</a>
                    <?php endif; ?>
                    <a class="mla-btn mla-btn--ghost" target="_blank" rel="noopener" href="<?php echo esc_url( $stripe_url_base . 'invoices/' . $inv['id'] ); ?>">Stripe ↗</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ( empty( $rows ) ) : ?>
            <tr><td colspan="6" class="mla-muted" style="text-align:center;padding:32px;">No invoices found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php ml_ap_render_pagination( $total, $page, $per_page, $base ); ?>
