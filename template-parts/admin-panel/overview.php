<?php defined( 'ABSPATH' ) || exit;
global $wpdb;
$book_tbl = ml_table( 'bookings' );

$today_local_start = wp_date( 'Y-m-d 00:00:00' );
$today_local_end   = wp_date( 'Y-m-d 23:59:59' );
$today_utc_start   = ( new DateTime( $today_local_start, wp_timezone() ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
$today_utc_end     = ( new DateTime( $today_local_end,   wp_timezone() ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );

$kpi = array(
    'today'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$book_tbl} WHERE scheduled_for BETWEEN %s AND %s AND status IN ('requested','confirmed')", $today_utc_start, $today_utc_end ) ),
    'upcoming' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$book_tbl} WHERE scheduled_for > UTC_TIMESTAMP() AND status IN ('requested','confirmed')" ),
    'pending'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$book_tbl} WHERE status = 'requested'" ),
    'total'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$book_tbl}" ),
);

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
    <div class="mla-kpi"><p class="mla-kpi__label">Today's bookings</p><p class="mla-kpi__value"><?php echo (int) $kpi['today']; ?></p></div>
    <div class="mla-kpi"><p class="mla-kpi__label">Upcoming</p><p class="mla-kpi__value"><?php echo (int) $kpi['upcoming']; ?></p></div>
    <div class="mla-kpi"><p class="mla-kpi__label">Awaiting confirmation</p><p class="mla-kpi__value"><?php echo (int) $kpi['pending']; ?></p></div>
    <div class="mla-kpi"><p class="mla-kpi__label">Total bookings</p><p class="mla-kpi__value"><?php echo (int) $kpi['total']; ?></p></div>
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
