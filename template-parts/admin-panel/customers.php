<?php defined( 'ABSPATH' ) || exit;
// List + detail dispatcher.
$id = isset( $id ) ? trim( (string) $id ) : '';
if ( ctype_digit( $id ) ) {
    $detail_id = (int) $id;
    include ML_PATH . 'template-parts/admin-panel/customers-detail.php';
    return;
}

global $wpdb;
$per_page = ml_ap_per_page( 25 );
$page     = ml_ap_current_page();
$q        = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
$role     = ML_ROLE_CUSTOMER;

$where = "WHERE u.ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='{$wpdb->prefix}capabilities' AND meta_value LIKE %s)";
$params = array( '%' . $wpdb->esc_like( $role ) . '%' );
if ( $q ) {
    $where  .= " AND (u.user_email LIKE %s OR u.display_name LIKE %s)";
    $params[] = '%' . $wpdb->esc_like( $q ) . '%';
    $params[] = '%' . $wpdb->esc_like( $q ) . '%';
}

$total_sql = "SELECT COUNT(*) FROM {$wpdb->users} u $where";
$total     = (int) $wpdb->get_var( $wpdb->prepare( $total_sql, ...$params ) );

list( $limit, $offset ) = ml_ap_limit_offset( $per_page, $page );
$list_params = array_merge( $params, array( $limit, $offset ) );
$rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT u.ID, u.user_email, u.display_name, u.user_registered FROM {$wpdb->users} u $where ORDER BY u.user_registered DESC LIMIT %d OFFSET %d",
    ...$list_params
) );

$sub_tbl  = ml_table( 'subscriptions' );
$tour_pt  = ML_CPT_TOUR;
?>

<div class="mla-toolbar">
    <form method="get" style="display:flex;gap:8px;">
        <input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Search by email or name…">
        <button type="submit" class="mla-btn mla-btn--secondary">Search</button>
        <?php if ( $q ) : ?>
            <a class="mla-btn mla-btn--ghost" href="<?php echo esc_url( home_url( '/admin/customers' ) ); ?>">Clear</a>
        <?php endif; ?>
    </form>
    <span class="mla-muted" style="margin-left:auto;"><?php echo (int) $total; ?> customers</span>
</div>

<div class="mla-card" style="padding:0;">
    <table class="mla-table">
        <thead><tr><th>Email</th><th>Name</th><th>Joined</th><th>Status</th><th>Tours</th><th></th></tr></thead>
        <tbody>
        <?php foreach ( $rows as $u ) :
            $setup = (string) get_user_meta( $u->ID, ML_META_SETUP_STATE, true );
            $sub   = $wpdb->get_row( $wpdb->prepare( "SELECT status, current_period_end FROM {$sub_tbl} WHERE user_id=%d ORDER BY id DESC LIMIT 1", $u->ID ) );
            $tours = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(p.ID) FROM {$wpdb->posts} p
                   INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value = %d
                  WHERE p.post_type = %s",
                ML_META_TOUR_USER, $u->ID, $tour_pt
            ) );
            $status_label = $sub ? $sub->status : ( $setup === ML_SETUP_STATE_PENDING ? 'pending_approval' : '—' );
            $pill = 'mla-pill--neutral';
            if ( $sub && in_array( $sub->status, array( 'active', 'trialing' ), true ) )       $pill = 'mla-pill--success';
            elseif ( $sub && in_array( $sub->status, array( 'past_due', 'unpaid' ), true ) )    $pill = 'mla-pill--warning';
            elseif ( $sub && in_array( $sub->status, array( 'canceled', 'cancelled' ), true ) ) $pill = 'mla-pill--danger';
            elseif ( $setup === ML_SETUP_STATE_PENDING )                                        $pill = 'mla-pill--warning';
        ?>
            <tr>
                <td><a href="<?php echo esc_url( home_url( '/admin/customers/' . $u->ID ) ); ?>"><?php echo esc_html( $u->user_email ); ?></a></td>
                <td><?php echo esc_html( $u->display_name ?: '—' ); ?></td>
                <td><?php echo esc_html( ml_format_date( $u->user_registered ) ); ?></td>
                <td><span class="mla-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
                <td><?php echo (int) $tours; ?></td>
                <td style="text-align:right;"><a class="mla-btn mla-btn--ghost" href="<?php echo esc_url( home_url( '/admin/customers/' . $u->ID ) ); ?>">Open →</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if ( empty( $rows ) ) : ?>
            <tr><td colspan="6" class="mla-muted" style="text-align:center;padding:32px;">No customers found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php ml_ap_render_pagination( $total, $page, $per_page, home_url( '/admin/customers' ) . ( $q ? '?q=' . rawurlencode( $q ) : '' ) ); ?>
