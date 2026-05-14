<?php defined( 'ABSPATH' ) || exit;
global $wpdb;
$tbl      = ml_table( 'bookings' );
$status   = sanitize_key( wp_unslash( $_GET['status'] ?? '' ) );
$per_page = ml_ap_per_page( 25 );
$page     = ml_ap_current_page();

$where  = "WHERE 1=1";
$params = array();
if ( in_array( $status, array( 'requested', 'confirmed', 'completed', 'cancelled' ), true ) ) {
    $where   .= " AND status = %s";
    $params[] = $status;
}

$count_sql = "SELECT COUNT(*) FROM {$tbl} {$where}";
$total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) : $wpdb->get_var( $count_sql ) );

list( $limit, $offset ) = ml_ap_limit_offset( $per_page, $page );
$list_sql = "SELECT b.*, u.user_email FROM {$tbl} b LEFT JOIN {$wpdb->users} u ON u.ID = b.user_id {$where} ORDER BY b.scheduled_for DESC LIMIT %d OFFSET %d";
$list_params = array_merge( $params, array( $limit, $offset ) );
$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ) );

$base = home_url( '/admin/bookings' ) . ( $status ? '?status=' . $status : '' );
?>

<div class="mla-toolbar">
    <select onchange="window.location='<?php echo esc_url( home_url( '/admin/bookings' ) ); ?>'+(this.value?('?status='+this.value):'')">
        <option value="">All statuses</option>
        <?php foreach ( array( 'requested', 'confirmed', 'completed', 'cancelled' ) as $s ) : ?>
            <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
        <?php endforeach; ?>
    </select>
    <span class="mla-muted" style="margin-left:auto;"><?php echo (int) $total; ?> bookings</span>
</div>

<div class="mla-card" style="padding:0;">
    <table class="mla-table">
        <thead><tr><th>Scheduled</th><th>Customer</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ( $rows as $b ) :
            $pill = array( 'requested' => 'mla-pill--warning', 'confirmed' => 'mla-pill--success', 'completed' => 'mla-pill--neutral', 'cancelled' => 'mla-pill--danger' )[ $b->status ] ?? 'mla-pill--neutral';
        ?>
            <tr>
                <td><?php echo esc_html( ml_format_datetime( $b->scheduled_for ) ); ?></td>
                <td><?php echo esc_html( $b->user_email ?? '(deleted)' ); ?></td>
                <td><span class="mla-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( $b->status ); ?></span></td>
                <td><?php echo esc_html( ml_format_date( $b->created_at ) ); ?></td>
                <td style="text-align:right;white-space:nowrap;">
                    <?php if ( $b->status === 'requested' ) : ?>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                            <?php wp_nonce_field( 'ml_ap_booking_action' ); ?>
                            <input type="hidden" name="action" value="ml_ap_booking_action">
                            <input type="hidden" name="id" value="<?php echo (int) $b->id; ?>">
                            <input type="hidden" name="op" value="confirm">
                            <button class="mla-btn mla-btn--primary" type="submit">Confirm</button>
                        </form>
                    <?php endif; ?>
                    <?php if ( in_array( $b->status, array( 'requested', 'confirmed' ), true ) ) : ?>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                            <?php wp_nonce_field( 'ml_ap_booking_action' ); ?>
                            <input type="hidden" name="action" value="ml_ap_booking_action">
                            <input type="hidden" name="id" value="<?php echo (int) $b->id; ?>">
                            <input type="hidden" name="op" value="complete">
                            <button class="mla-btn mla-btn--secondary" type="submit">Complete</button>
                        </form>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;" onsubmit="return confirm('Cancel this booking?');">
                            <?php wp_nonce_field( 'ml_ap_booking_action' ); ?>
                            <input type="hidden" name="action" value="ml_ap_booking_action">
                            <input type="hidden" name="id" value="<?php echo (int) $b->id; ?>">
                            <input type="hidden" name="op" value="cancel">
                            <button class="mla-btn mla-btn--danger" type="submit">Cancel</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ( empty( $rows ) ) : ?>
            <tr><td colspan="5" class="mla-muted" style="text-align:center;padding:32px;">No bookings.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php ml_ap_render_pagination( $total, $page, $per_page, $base ); ?>
