<?php
/** Memory Lane admin — read-only subscriptions list. */
defined( 'ABSPATH' ) || exit;
global $wpdb;
$tbl  = ml_table( 'subscriptions' );
$rows = $wpdb->get_results( "SELECT * FROM {$tbl} ORDER BY updated_at DESC LIMIT 200" );
?>
<div class="mla-toolbar">
    <span class="mla-muted" style="margin-left:auto;"><?php echo (int) count( (array) $rows ); ?> shown (max 200)</span>
</div>
<div class="mla-card" style="padding:0;">
    <table class="mla-table">
        <thead><tr><th>Customer</th><th>Status</th><th>Renews</th><th>Cancels?</th><th>Stripe sub</th></tr></thead>
        <tbody>
        <?php if ( $rows ) : foreach ( $rows as $r ) :
            $u = get_user_by( 'id', (int) $r->user_id );
            $status_class = in_array( $r->status, array( 'active', 'trialing' ), true ) ? 'mla-pill--success' : ( $r->status === 'past_due' ? 'mla-pill--warning' : 'mla-pill--danger' );
        ?>
            <tr>
                <td><?php echo esc_html( $u ? ( $u->display_name . ' · ' . $u->user_email ) : ( '#' . $r->user_id ) ); ?></td>
                <td><span class="mla-pill <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $r->status ); ?></span></td>
                <td><?php echo esc_html( $r->current_period_end ?: '—' ); ?></td>
                <td><?php echo ! empty( $r->cancel_at_period_end ) ? 'yes' : '—'; ?></td>
                <td><code style="font-size:12px;"><?php echo esc_html( $r->stripe_sub_id ); ?></code></td>
            </tr>
        <?php endforeach; else : ?>
            <tr><td colspan="5" class="mla-muted" style="text-align:center;padding:32px;">No subscriptions yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
