<?php
/**
 * Memory Lane — admin Email log.
 */
defined( 'ABSPATH' ) || exit;

function ml_admin_render_notifications_log() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    global $wpdb;
    $tbl = ml_table( 'email_log' );

    if ( isset( $_GET['retry'], $_GET['_wpnonce'] ) && check_admin_referer( 'ml_email_retry' ) ) {
        $id = (int) $_GET['retry'];
        $ok = ml_mail_retry( $id );
        if ( $ok ) echo '<div class="notice notice-success"><p>' . esc_html__( 'Retry succeeded.', 'memorylane' ) . '</p></div>';
        else      echo '<div class="notice notice-error"><p>'   . esc_html__( 'Retry failed.',    'memorylane' ) . '</p></div>';
    }

    $rows = $wpdb->get_results( "SELECT * FROM {$tbl} ORDER BY id DESC LIMIT 200" );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Notifications log', 'memorylane' ); ?></h1>
        <table class="widefat striped">
            <thead><tr>
                <th>#</th>
                <th><?php esc_html_e( 'Template', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'To', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Status', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Created', 'memorylane' ); ?></th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php if ( empty( $rows ) ) : ?>
                <tr><td colspan="6"><?php esc_html_e( 'No emails sent yet.', 'memorylane' ); ?></td></tr>
            <?php else : foreach ( $rows as $r ) : ?>
                <tr>
                    <td><?php echo (int) $r->id; ?></td>
                    <td><?php echo esc_html( $r->template ); ?></td>
                    <td><?php echo esc_html( $r->to_email ); ?></td>
                    <td><?php echo esc_html( $r->status ); ?><?php if ( $r->error_msg ) echo '<br><small style="color:#B91C1C;">' . esc_html( $r->error_msg ) . '</small>'; ?></td>
                    <td><?php echo esc_html( $r->created_at ); ?></td>
                    <td>
                        <?php if ( $r->status === 'failed' ) : ?>
                            <a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=memorylane-notifications&retry=' . (int) $r->id ), 'ml_email_retry' ) ); ?>"><?php esc_html_e( 'Retry', 'memorylane' ); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
