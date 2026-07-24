<?php
/** Memory Lane admin — read-only diagnostics: Stripe webhooks, emails, Teamleader queues. */
defined( 'ABSPATH' ) || exit;
global $wpdb;

$wh_tbl  = ml_table( 'webhook_events' );
$em_tbl  = ml_table( 'email_log' );

$wh_count   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wh_tbl}" );
$wh_recent  = $wpdb->get_results( "SELECT event_id, type, status, received_at FROM {$wh_tbl} ORDER BY id DESC LIMIT 15" );
$em_recent  = $wpdb->get_results( "SELECT to_email, template, status, error_msg, created_at FROM {$em_tbl} ORDER BY id DESC LIMIT 25" );
$em_counts  = array();
foreach ( (array) $wpdb->get_results( "SELECT status, COUNT(*) c FROM {$em_tbl} GROUP BY status" ) as $r ) { $em_counts[ $r->status ] = (int) $r->c; }

$tl_deal_q  = function_exists( 'ml_tl_queue_count' ) ? ml_tl_queue_count() : 0;
$tl_inv_q   = is_array( get_option( 'ml_tl_invoice_queue', array() ) ) ? count( get_option( 'ml_tl_invoice_queue', array() ) ) : 0;
$tl_ok      = function_exists( 'ml_tl_is_connected' ) && ml_tl_is_connected();
$stripe_ok  = function_exists( 'ml_stripe_is_configured' ) && ml_stripe_is_configured();
?>
<div class="mla-grid mla-grid--4" style="margin-bottom:16px;">
    <div class="mla-kpi"><p class="mla-kpi__label">Stripe webhooks received</p><p class="mla-kpi__value"><?php echo $wh_count; ?></p></div>
    <div class="mla-kpi"><p class="mla-kpi__label">Emails sent</p><p class="mla-kpi__value"><?php echo (int) ( $em_counts['sent'] ?? 0 ); ?></p></div>
    <div class="mla-kpi"><p class="mla-kpi__label">Emails failed</p><p class="mla-kpi__value"><?php echo (int) ( $em_counts['failed'] ?? 0 ); ?></p></div>
    <div class="mla-kpi"><p class="mla-kpi__label">Teamleader deal queue</p><p class="mla-kpi__value"><?php echo (int) $tl_deal_q; ?></p></div>
</div>

<div class="mla-card">
    <h2>What this tells you</h2>
    <ul style="margin:0;padding-left:18px;line-height:1.8;">
        <li><strong>Stripe configured:</strong> <?php echo $stripe_ok ? '✅ yes' : '❌ no (enter keys + Sync plan in Billing)'; ?></li>
        <li><strong>Teamleader connected:</strong> <?php echo $tl_ok ? '✅ yes' : '❌ no — deals queue until you connect (Settings → Teamleader)'; ?></li>
        <li><strong>Webhooks received = 0</strong> → Stripe isn't calling your site. Add the endpoint <code><?php echo esc_html( home_url( '/wp-json/memorylane/v1/stripe-webhook' ) ); ?></code> in Stripe → Developers → Webhooks, and paste its signing secret into Billing.</li>
        <li><strong>Emails show "sent" but not received</strong> → delivery/spam problem → configure SMTP.</li>
        <li><strong>No email rows at all</strong> → the flow never ran (webhook not firing, above).</li>
    </ul>
</div>

<div class="mla-card" style="padding:0;">
    <div style="padding:16px 20px;"><strong>Recent Stripe webhook events</strong></div>
    <table class="mla-table">
        <thead><tr><th>Type</th><th>Status</th><th>Received</th></tr></thead>
        <tbody>
        <?php if ( $wh_recent ) : foreach ( $wh_recent as $w ) : ?>
            <tr><td><code><?php echo esc_html( $w->type ); ?></code></td>
                <td><span class="mla-pill <?php echo $w->status === 'processed' ? 'mla-pill--success' : ( $w->status === 'failed' ? 'mla-pill--danger' : 'mla-pill--warning' ); ?>"><?php echo esc_html( $w->status ); ?></span></td>
                <td><?php echo esc_html( $w->received_at ); ?></td></tr>
        <?php endforeach; else : ?>
            <tr><td colspan="3" class="mla-muted" style="text-align:center;padding:24px;">No webhook events yet — Stripe hasn't called the site.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mla-card" style="padding:0;margin-top:16px;">
    <div style="padding:16px 20px;"><strong>Recent emails</strong></div>
    <table class="mla-table">
        <thead><tr><th>To</th><th>Template</th><th>Status</th><th>When</th><th>Error</th></tr></thead>
        <tbody>
        <?php if ( $em_recent ) : foreach ( $em_recent as $e ) :
            $cls = $e->status === 'sent' ? 'mla-pill--success' : ( $e->status === 'failed' ? 'mla-pill--danger' : 'mla-pill--warning' );
        ?>
            <tr><td><?php echo esc_html( $e->to_email ); ?></td>
                <td><code><?php echo esc_html( $e->template ); ?></code></td>
                <td><span class="mla-pill <?php echo $cls; ?>"><?php echo esc_html( $e->status ); ?></span></td>
                <td><?php echo esc_html( $e->created_at ); ?></td>
                <td class="mla-muted" style="font-size:12px;"><?php echo esc_html( (string) $e->error_msg ); ?></td></tr>
        <?php endforeach; else : ?>
            <tr><td colspan="5" class="mla-muted" style="text-align:center;padding:24px;">No emails logged yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
