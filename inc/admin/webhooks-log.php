<?php
/**
 * Memory Lane — admin Webhooks log.
 */
defined( 'ABSPATH' ) || exit;

function ml_admin_render_webhooks_log() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    global $wpdb;
    $tbl = ml_table( 'webhook_events' );

    if ( isset( $_GET['retry'], $_GET['_wpnonce'] ) && check_admin_referer( 'ml_wh_retry' ) ) {
        $event_id = sanitize_text_field( $_GET['retry'] );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE event_id=%s", $event_id ) );
        if ( $row && $row->payload ) {
            try {
                $arr   = json_decode( $row->payload, true );
                $event = \Stripe\Event::constructFrom( $arr );
                ml_stripe_dispatch_event( $event );
                $wpdb->update( $tbl, array( 'status' => 'processed', 'processed_at' => current_time( 'mysql', true ), 'error_msg' => null ), array( 'id' => $row->id ) );
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Retry succeeded.', 'memorylane' ) . '</p></div>';
            } catch ( \Throwable $e ) {
                $wpdb->update( $tbl, array( 'status' => 'failed', 'error_msg' => substr( $e->getMessage(), 0, 1000 ), 'retry_count' => $row->retry_count + 1 ), array( 'id' => $row->id ) );
                echo '<div class="notice notice-error"><p>' . esc_html( $e->getMessage() ) . '</p></div>';
            }
        }
    }

    $rows = $wpdb->get_results( "SELECT * FROM {$tbl} ORDER BY id DESC LIMIT 200" );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Webhooks log', 'memorylane' ); ?></h1>
        <table class="widefat striped">
            <thead><tr>
                <th><?php esc_html_e( 'Event', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Type', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Status', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Retries', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Received', 'memorylane' ); ?></th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php if ( empty( $rows ) ) : ?>
                <tr><td colspan="6"><?php esc_html_e( 'No events yet.', 'memorylane' ); ?></td></tr>
            <?php else : foreach ( $rows as $r ) : ?>
                <tr>
                    <td><code style="font-size:11px;"><?php echo esc_html( $r->event_id ); ?></code></td>
                    <td><?php echo esc_html( $r->type ); ?></td>
                    <td>
                        <span style="color:<?php echo $r->status === 'processed' ? '#047857' : ( $r->status === 'failed' ? '#B91C1C' : '#71717A' ); ?>;">●</span>
                        <?php echo esc_html( $r->status ); ?>
                        <?php if ( $r->error_msg ) : ?>
                            <br><small style="color:#B91C1C;"><?php echo esc_html( $r->error_msg ); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int) $r->retry_count; ?></td>
                    <td><?php echo esc_html( $r->received_at ); ?></td>
                    <td>
                        <?php if ( $r->status === 'failed' ) : ?>
                            <a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=memorylane-webhooks&retry=' . urlencode( $r->event_id ) ), 'ml_wh_retry' ) ); ?>"><?php esc_html_e( 'Retry', 'memorylane' ); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
