<?php defined( 'ABSPATH' ) || exit;
global $wpdb;
$tab      = sanitize_key( wp_unslash( $_GET['tab'] ?? 'webhooks' ) );
$per_page = ml_ap_per_page( 25 );
$page     = ml_ap_current_page();
?>

<div class="mla-tabs">
    <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'webhooks' ), home_url( '/admin/logs' ) ) ); ?>" class="<?php echo $tab === 'webhooks' ? 'is-active' : ''; ?>">Webhooks</a>
    <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'emails'   ), home_url( '/admin/logs' ) ) ); ?>" class="<?php echo $tab === 'emails'   ? 'is-active' : ''; ?>">Emails</a>
</div>

<?php if ( $tab === 'emails' ) :
    $tbl   = ml_table( 'notifications' );
    $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$tbl}'" );
    if ( ! $exists ) {
        echo '<div class="mla-card"><p class="mla-muted">No email log table.</p></div>';
        return;
    }
    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl}" );
    list( $limit, $offset ) = ml_ap_limit_offset( $per_page, $page );
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset ) );
?>
    <div class="mla-card" style="padding:0;">
        <table class="mla-table">
            <thead><tr><th>Sent</th><th>Recipient</th><th>Template</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ( $rows as $r ) :
                $pill = $r->status === 'sent' ? 'mla-pill--success' : ( $r->status === 'failed' ? 'mla-pill--danger' : 'mla-pill--neutral' );
            ?>
                <tr>
                    <td><?php echo esc_html( ml_format_datetime( $r->created_at ?? $r->sent_at ?? null ) ); ?></td>
                    <td><?php echo esc_html( $r->recipient ?? '—' ); ?></td>
                    <td><?php echo esc_html( $r->template ?? '—' ); ?></td>
                    <td><span class="mla-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( $r->status ); ?></span></td>
                    <td style="text-align:right;">
                        <?php if ( $r->status === 'failed' ) : ?>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                <?php wp_nonce_field( 'ml_ap_email_retry' ); ?>
                                <input type="hidden" name="action" value="ml_ap_email_retry">
                                <input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">
                                <button class="mla-btn mla-btn--secondary" type="submit">Retry</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ( empty( $rows ) ) : ?>
                <tr><td colspan="5" class="mla-muted" style="text-align:center;padding:32px;">No emails logged.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php ml_ap_render_pagination( $total, $page, $per_page, add_query_arg( 'tab', 'emails', home_url( '/admin/logs' ) ) ); ?>
<?php
else :
    $tbl   = ml_table( 'webhook_events' );
    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl}" );
    list( $limit, $offset ) = ml_ap_limit_offset( $per_page, $page );
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset ) );
?>
    <div class="mla-card" style="padding:0;">
        <table class="mla-table">
            <thead><tr><th>Received</th><th>Event ID</th><th>Type</th><th>Status</th><th>Retries</th><th></th></tr></thead>
            <tbody>
            <?php foreach ( $rows as $r ) :
                $pill = $r->status === 'processed' ? 'mla-pill--success' : ( $r->status === 'failed' ? 'mla-pill--danger' : ( $r->status === 'duplicate' ? 'mla-pill--neutral' : 'mla-pill--warning' ) );
            ?>
                <tr>
                    <td><?php echo esc_html( ml_format_datetime( $r->received_at ) ); ?></td>
                    <td><code style="font-size:11px;"><?php echo esc_html( $r->event_id ); ?></code></td>
                    <td><?php echo esc_html( $r->type ); ?></td>
                    <td><span class="mla-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( $r->status ); ?></span></td>
                    <td><?php echo (int) $r->retry_count; ?></td>
                    <td style="text-align:right;">
                        <?php if ( $r->status === 'failed' ) : ?>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                <?php wp_nonce_field( 'ml_ap_webhook_retry' ); ?>
                                <input type="hidden" name="action" value="ml_ap_webhook_retry">
                                <input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">
                                <button class="mla-btn mla-btn--secondary" type="submit">Retry</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ( empty( $rows ) ) : ?>
                <tr><td colspan="6" class="mla-muted" style="text-align:center;padding:32px;">No webhook events yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php ml_ap_render_pagination( $total, $page, $per_page, add_query_arg( 'tab', 'webhooks', home_url( '/admin/logs' ) ) ); ?>
<?php endif; ?>
