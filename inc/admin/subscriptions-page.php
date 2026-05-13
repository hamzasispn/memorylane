<?php
/**
 * Memory Lane — admin Subscriptions page.
 */
defined( 'ABSPATH' ) || exit;

function ml_admin_render_subscriptions() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    global $wpdb;
    $tbl = ml_table( 'subscriptions' );

    $filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
    $sql = "SELECT s.*, u.user_email FROM {$tbl} s LEFT JOIN {$wpdb->users} u ON u.ID=s.user_id";
    if ( $filter ) {
        $sql .= $wpdb->prepare( ' WHERE s.status=%s', $filter );
    }
    $sql .= ' ORDER BY s.updated_at DESC LIMIT 200';
    $rows = $wpdb->get_results( $sql );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Subscriptions', 'memorylane' ); ?></h1>
        <p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=memorylane-subscriptions' ) ); ?>" class="button"><?php esc_html_e( 'All', 'memorylane' ); ?></a>
            <?php foreach ( array( 'active', 'past_due', 'cancelled', 'incomplete' ) as $s ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=memorylane-subscriptions&status=' . $s ) ); ?>" class="button <?php echo $filter === $s ? 'button-primary' : ''; ?>"><?php echo esc_html( $s ); ?></a>
            <?php endforeach; ?>
        </p>

        <table class="widefat striped">
            <thead><tr>
                <th>#</th>
                <th><?php esc_html_e( 'Customer', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Status', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Period end', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Cancel at end?', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Year 1 ends', 'memorylane' ); ?></th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php if ( empty( $rows ) ) : ?>
                <tr><td colspan="7"><?php esc_html_e( 'No subscriptions yet.', 'memorylane' ); ?></td></tr>
            <?php else : foreach ( $rows as $r ) : ?>
                <tr>
                    <td><?php echo (int) $r->id; ?></td>
                    <td><?php echo esc_html( $r->user_email ?: '—' ); ?><br><code style="font-size:11px;"><?php echo esc_html( $r->stripe_sub_id ); ?></code></td>
                    <td><?php echo esc_html( $r->status ); ?></td>
                    <td><?php echo esc_html( $r->current_period_end ?: '—' ); ?></td>
                    <td><?php echo $r->cancel_at_period_end ? '✓' : '—'; ?></td>
                    <td><?php echo esc_html( $r->year_one_end_date ?: '—' ); ?></td>
                    <td>
                        <a class="button button-small" target="_blank" href="<?php echo esc_url( 'https://dashboard.stripe.com/' . ( ml_stripe_mode() === 'test' ? 'test/' : '' ) . 'subscriptions/' . $r->stripe_sub_id ); ?>"><?php esc_html_e( 'Stripe', 'memorylane' ); ?></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
