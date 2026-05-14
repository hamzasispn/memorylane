<?php defined( 'ABSPATH' ) || exit;
global $wpdb;
$sub_tbl  = ml_table( 'subscriptions' );
$book_tbl = ml_table( 'bookings' );
$wh_tbl   = ml_table( 'webhook_events' );

$kpi = array(
    'active'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sub_tbl} WHERE status IN ('active','trialing')" ),
    'pending'  => count( get_users( array( 'meta_key' => ML_META_SETUP_STATE, 'meta_value' => ML_SETUP_STATE_PENDING, 'fields' => 'ID' ) ) ),
    'past_due' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sub_tbl} WHERE status IN ('past_due','unpaid')" ),
    'wh_fail'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wh_tbl} WHERE status='failed' AND received_at >= %s", gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) ) ),
);

$today_local_start = wp_date( 'Y-m-d 00:00:00' );
$today_local_end   = wp_date( 'Y-m-d 23:59:59' );
$today_utc_start   = ( new DateTime( $today_local_start, wp_timezone() ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
$today_utc_end     = ( new DateTime( $today_local_end,   wp_timezone() ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );

$todays = $wpdb->get_results( $wpdb->prepare(
    "SELECT b.*, u.user_email, u.display_name
       FROM {$book_tbl} b
       LEFT JOIN {$wpdb->users} u ON u.ID = b.user_id
      WHERE b.scheduled_for BETWEEN %s AND %s
        AND b.status IN ('requested','confirmed')
      ORDER BY b.scheduled_for ASC LIMIT 20",
    $today_utc_start, $today_utc_end
) );
?>

<div class="mla-grid mla-grid--4">
    <div class="mla-kpi"><p class="mla-kpi__label">Active subs</p><p class="mla-kpi__value"><?php echo (int) $kpi['active']; ?></p></div>
    <div class="mla-kpi"><p class="mla-kpi__label">Pending approval</p><p class="mla-kpi__value"><?php echo (int) $kpi['pending']; ?></p></div>
    <div class="mla-kpi"><p class="mla-kpi__label">Past due</p><p class="mla-kpi__value"><?php echo (int) $kpi['past_due']; ?></p></div>
    <div class="mla-kpi"><p class="mla-kpi__label">Webhook failures (24h)</p><p class="mla-kpi__value"><?php echo (int) $kpi['wh_fail']; ?></p></div>
</div>

<div class="mla-card" style="margin-top:24px;">
    <h2>Today's bookings</h2>
    <?php if ( empty( $todays ) ) : ?>
        <p class="mla-muted">No bookings today.</p>
    <?php else : ?>
        <table class="mla-table">
            <thead><tr><th>Time</th><th>Customer</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ( $todays as $b ) :
                $pill = array( 'requested' => 'mla-pill--warning', 'confirmed' => 'mla-pill--success' )[ $b->status ] ?? 'mla-pill--neutral';
            ?>
                <tr>
                    <td><?php echo esc_html( ml_format_datetime( $b->scheduled_for ) ); ?></td>
                    <td><?php echo esc_html( ( $b->display_name ?: $b->user_email ) ?? '(deleted)' ); ?></td>
                    <td><span class="mla-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( $b->status ); ?></span></td>
                    <td style="text-align:right;"><a class="mla-btn mla-btn--ghost" href="<?php echo esc_url( home_url( '/admin/bookings' ) ); ?>">Open →</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
