<?php defined( 'ABSPATH' ) || exit;
global $wpdb;
$tbl      = ml_table( 'subscriptions' );
$status   = sanitize_key( wp_unslash( $_GET['status'] ?? '' ) );
$per_page = ml_ap_per_page( 25 );
$page     = ml_ap_current_page();

$where  = "WHERE 1=1";
$params = array();
if ( $status ) { $where .= " AND s.status = %s"; $params[] = $status; }

$count_sql = "SELECT COUNT(*) FROM {$tbl} s {$where}";
$total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) : $wpdb->get_var( $count_sql ) );

list( $limit, $offset ) = ml_ap_limit_offset( $per_page, $page );
$list_sql = "SELECT s.*, u.user_email FROM {$tbl} s LEFT JOIN {$wpdb->users} u ON u.ID = s.user_id {$where} ORDER BY s.id DESC LIMIT %d OFFSET %d";
$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...array_merge( $params, array( $limit, $offset ) ) ) );

$base    = home_url( '/admin/subscriptions' ) . ( $status ? '?status=' . $status : '' );
$stripe_url_base = 'https://dashboard.stripe.com/' . ( ml_stripe_mode() === 'live' ? '' : 'test/' );
?>

<div class="mla-toolbar">
    <select onchange="window.location='<?php echo esc_url( home_url( '/admin/subscriptions' ) ); ?>'+(this.value?('?status='+this.value):'')">
        <option value="">All statuses</option>
        <?php foreach ( array( 'active', 'trialing', 'past_due', 'unpaid', 'canceled', 'cancelled', 'pending_reactivation' ) as $s ) : ?>
            <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( $s ); ?></option>
        <?php endforeach; ?>
    </select>
    <span class="mla-muted" style="margin-left:auto;"><?php echo (int) $total; ?> subscriptions</span>
</div>

<div class="mla-card" style="padding:0;">
    <table class="mla-table">
        <thead><tr><th>Customer</th><th>Status</th><th>Next billing</th><th>Cancels at end?</th><th>Payment failed</th><th></th></tr></thead>
        <tbody>
        <?php foreach ( $rows as $r ) :
            $pill = 'mla-pill--neutral';
            if ( in_array( $r->status, array( 'active', 'trialing' ), true ) ) $pill = 'mla-pill--success';
            elseif ( in_array( $r->status, array( 'past_due', 'unpaid' ), true ) ) $pill = 'mla-pill--warning';
            elseif ( in_array( $r->status, array( 'canceled', 'cancelled' ), true ) ) $pill = 'mla-pill--danger';
        ?>
            <tr>
                <td><?php echo esc_html( $r->user_email ?? '(deleted)' ); ?></td>
                <td><span class="mla-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( $r->status ); ?></span></td>
                <td><?php echo esc_html( $r->current_period_end ? ml_format_date( $r->current_period_end ) : '—' ); ?></td>
                <td><?php echo $r->cancel_at_period_end ? 'Yes' : '—'; ?></td>
                <td><?php echo $r->payment_failed_at ? esc_html( ml_format_date( $r->payment_failed_at ) ) : '—'; ?></td>
                <td style="text-align:right;">
                    <?php if ( $r->stripe_sub_id ) : ?>
                        <a class="mla-btn mla-btn--ghost" target="_blank" rel="noopener" href="<?php echo esc_url( $stripe_url_base . 'subscriptions/' . $r->stripe_sub_id ); ?>">Open in Stripe ↗</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ( empty( $rows ) ) : ?>
            <tr><td colspan="6" class="mla-muted" style="text-align:center;padding:32px;">No subscriptions.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php ml_ap_render_pagination( $total, $page, $per_page, $base ); ?>
