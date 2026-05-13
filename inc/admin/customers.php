<?php
/**
 * Memory Lane — admin Customers page.
 */
defined( 'ABSPATH' ) || exit;

function ml_admin_render_customers() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    global $wpdb;

    $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
    $args = array(
        'role__in' => array( ML_ROLE_CUSTOMER ),
        'number'   => 200,
        'orderby'  => 'registered',
        'order'    => 'DESC',
    );
    if ( $search ) $args['search'] = '*' . $search . '*';
    $users = get_users( $args );
    $msg = $_GET['ml_msg'] ?? '';
    $err = $_GET['ml_err'] ?? '';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Customers', 'memorylane' ); ?></h1>

        <?php if ( $msg ) : ?><div class="notice notice-success"><p><?php echo esc_html( wp_unslash( $msg ) ); ?></p></div><?php endif; ?>
        <?php if ( $err ) : ?><div class="notice notice-error"><p><?php echo esc_html( wp_unslash( $err ) ); ?></p></div><?php endif; ?>

        <form method="get">
            <input type="hidden" name="page" value="memorylane-customers">
            <p><input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search email or name', 'memorylane' ); ?>" class="regular-text">
            <button class="button"><?php esc_html_e( 'Search', 'memorylane' ); ?></button></p>
        </form>

        <table class="widefat striped">
            <thead><tr>
                <th><?php esc_html_e( 'Customer', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Access state', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Subscription', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Tours', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Registered', 'memorylane' ); ?></th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php if ( empty( $users ) ) : ?>
                <tr><td colspan="6"><?php esc_html_e( 'No customers yet.', 'memorylane' ); ?></td></tr>
            <?php else :
                foreach ( $users as $u ) :
                    $row       = ml_get_subscription_row( $u->ID );
                    $stripe_id = get_user_meta( $u->ID, ML_META_STRIPE_CUSTOMER, true );
                    $tour_n    = ml_count_user_tours( $u->ID );
                    $setup     = get_user_meta( $u->ID, ML_META_SETUP_STATE, true );
                    $paid_at   = get_user_meta( $u->ID, ML_META_SETUP_PAID_AT, true );
                    $amount    = (int) get_user_meta( $u->ID, ML_META_SETUP_AMOUNT, true );
                    $currency  = strtoupper( (string) get_user_meta( $u->ID, ML_META_SETUP_CURRENCY, true ) );

                    $pill_class = 'background:#F4F4F5;color:#3F3F46';
                    $pill_text  = '—';
                    if ( $setup === ML_SETUP_STATE_PENDING ) {
                        $hours_since_paid = $paid_at ? round( ( time() - strtotime( $paid_at . ' UTC' ) ) / HOUR_IN_SECONDS, 1 ) : 0;
                        $overdue = $hours_since_paid > ML_APPROVAL_SLA_HOURS;
                        $pill_class = $overdue ? 'background:#FEE2E2;color:#B91C1C' : 'background:#FEF3C7;color:#92400E';
                        $pill_text  = sprintf( __( 'Pending (%sh since paid)', 'memorylane' ), $hours_since_paid );
                    } elseif ( $setup === ML_SETUP_STATE_APPROVED ) {
                        $pill_class = 'background:#D1FAE5;color:#047857';
                        $pill_text  = __( 'Approved', 'memorylane' );
                    } elseif ( $setup === ML_SETUP_STATE_REFUNDED ) {
                        $pill_class = 'background:#FEE2E2;color:#B91C1C';
                        $pill_text  = __( 'Refunded', 'memorylane' );
                    }
                ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $u->display_name ?: $u->user_email ); ?></strong>
                            <br><?php echo esc_html( $u->user_email ); ?>
                            <?php if ( $stripe_id ) : ?>
                                <br><code style="font-size:11px;"><?php echo esc_html( $stripe_id ); ?></code>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="display:inline-block;padding:2px 9px;border-radius:999px;font-size:12px;font-weight:500;<?php echo esc_attr( $pill_class ); ?>"><?php echo esc_html( $pill_text ); ?></span>
                            <?php if ( $amount ) : ?>
                                <br><span style="color:#666;font-size:12px;"><?php echo esc_html( $currency . ' ' . number_format( $amount / 100, 2 ) ); ?> · <?php echo esc_html( $paid_at ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $row ) : ?>
                                <strong><?php echo esc_html( ml_subscription_status_label( $row ) ); ?></strong>
                                <br><span style="color:#666;font-size:12px;"><?php esc_html_e( 'Next:', 'memorylane' ); ?> <?php echo esc_html( $row->current_period_end ?: '—' ); ?></span>
                            <?php else : ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int) $tour_n; ?></td>
                        <td><?php echo esc_html( $u->user_registered ); ?></td>
                        <td>
                            <?php if ( $setup === ML_SETUP_STATE_PENDING ) : ?>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;"
                                      onsubmit="return confirm('<?php echo esc_js( __( 'Approve access and create Stripe subscription (365-day trial then monthly)?', 'memorylane' ) ); ?>')">
                                    <?php wp_nonce_field( 'ml_approve_access' ); ?>
                                    <input type="hidden" name="action" value="ml_approve_access">
                                    <input type="hidden" name="user_id" value="<?php echo (int) $u->ID; ?>">
                                    <button class="button button-small button-primary" style="background:#10B981;border-color:#10B981;">✓ <?php esc_html_e( 'Approve access', 'memorylane' ); ?></button>
                                </form>
                            <?php endif; ?>
                            <a class="button button-small" href="<?php echo esc_url( get_edit_user_link( $u->ID ) ); ?>"><?php esc_html_e( 'Edit', 'memorylane' ); ?></a>
                            <?php if ( $stripe_id ) : ?>
                                <a class="button button-small" target="_blank" href="<?php echo esc_url( 'https://dashboard.stripe.com/' . ( ml_stripe_mode() === 'test' ? 'test/' : '' ) . 'customers/' . $stripe_id ); ?>"><?php esc_html_e( 'Stripe', 'memorylane' ); ?></a>
                            <?php endif; ?>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                <?php wp_nonce_field( 'ml_admin_resend_welcome' ); ?>
                                <input type="hidden" name="action" value="ml_admin_resend_welcome">
                                <input type="hidden" name="user_id" value="<?php echo (int) $u->ID; ?>">
                                <button class="button button-small"><?php esc_html_e( 'Resend welcome', 'memorylane' ); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
