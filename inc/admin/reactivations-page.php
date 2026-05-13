<?php
/**
 * Memory Lane — admin Reactivations page (operational queue).
 */
defined( 'ABSPATH' ) || exit;

function ml_admin_render_reactivations() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    global $wpdb;
    $tbl = ml_table( 'reactivations' );

    $filter = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'pending';
    $allowed = array( 'pending', 'completed', 'refunded', 'all' );
    if ( ! in_array( $filter, $allowed, true ) ) $filter = 'pending';

    $msg = $_GET['ml_msg'] ?? '';
    $err = $_GET['ml_err'] ?? '';

    if ( $filter === 'all' ) {
        $rows = $wpdb->get_results( "SELECT * FROM {$tbl} ORDER BY requested_at DESC LIMIT 200" );
    } else {
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE status=%s ORDER BY requested_at ASC LIMIT 200", $filter ) );
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Reactivations', 'memorylane' ); ?></h1>
        <p class="description"><?php esc_html_e( 'Customer-driven tour reactivations. Click "Reactivation done" once the Matterport space is set public.', 'memorylane' ); ?></p>

        <?php if ( $msg ) : ?><div class="notice notice-success"><p><?php echo esc_html( wp_unslash( $msg ) ); ?></p></div><?php endif; ?>
        <?php if ( $err ) : ?><div class="notice notice-error"><p><?php echo esc_html( wp_unslash( $err ) ); ?></p></div><?php endif; ?>

        <ul class="subsubsub">
        <?php foreach ( $allowed as $k ) :
            $cnt = $k === 'all'
                ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl}" )
                : (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl} WHERE status=%s", $k ) );
        ?>
            <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=memorylane-reactivations&status=' . $k ) ); ?>" class="<?php echo $filter === $k ? 'current' : ''; ?>"><?php echo esc_html( ucfirst( $k ) ); ?> <span class="count">(<?php echo (int) $cnt; ?>)</span></a></li>
        <?php endforeach; ?>
        </ul>
        <br class="clear">

        <table class="widefat striped">
            <thead><tr>
                <th><?php esc_html_e( 'Customer', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Cycle', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Plan', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Fee', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Requested', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Waiting', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Tours', 'memorylane' ); ?></th>
                <th><?php esc_html_e( 'Status', 'memorylane' ); ?></th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php if ( empty( $rows ) ) : ?>
                <tr><td colspan="9"><?php esc_html_e( 'No reactivations.', 'memorylane' ); ?></td></tr>
            <?php else :
                foreach ( $rows as $r ) :
                    $u    = get_user_by( 'id', $r->user_id );
                    $hrs  = round( ( time() - strtotime( $r->requested_at . ' UTC' ) ) / HOUR_IN_SECONDS, 1 );
                    $over = ( $r->status === 'pending' ) && $hrs > ML_REACTIVATION_SLA_HOURS;
                    $tours = $u ? ml_get_user_tours( $r->user_id ) : array();
            ?>
                <tr style="<?php echo $over ? 'background:#FEF2F2;' : ''; ?>">
                    <td><?php echo $u ? esc_html( $u->display_name ?: $u->user_email ) : '—'; ?><br><small style="color:#71717A;"><?php echo $u ? esc_html( $u->user_email ) : ''; ?></small></td>
                    <td>#<?php echo (int) $r->cycle_number; ?></td>
                    <td><?php echo esc_html( ucfirst( $r->plan_chosen ) ); ?></td>
                    <td><?php echo esc_html( strtoupper( $r->activation_fee_currency ) . ' ' . number_format( $r->activation_fee_paid_cents / 100, 2 ) ); ?></td>
                    <td><?php echo esc_html( $r->requested_at ); ?></td>
                    <td><?php echo $r->status === 'pending' ? esc_html( $hrs . 'h' ) : '—'; ?>
                        <?php if ( $over ) : ?><br><span style="color:#B91C1C;font-size:11px;">OVERDUE</span><?php endif; ?>
                    </td>
                    <td><?php echo (int) count( $tours ); ?></td>
                    <td><?php echo esc_html( $r->status ); ?></td>
                    <td>
                        <?php if ( $r->status === 'pending' ) : ?>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;"
                                  onsubmit="return confirm('<?php echo esc_js( __( 'Mark reactivation complete?', 'memorylane' ) ); ?>')">
                                <?php wp_nonce_field( 'ml_reactivation_complete' ); ?>
                                <input type="hidden" name="action" value="ml_reactivation_complete">
                                <input type="hidden" name="row_id" value="<?php echo (int) $r->id; ?>">
                                <button class="button button-small button-primary" style="background:#F97316;border-color:#F97316;">✓ <?php esc_html_e( 'Done', 'memorylane' ); ?></button>
                            </form>
                        <?php endif; ?>
                        <?php if ( $r->stripe_subscription_id ) : ?>
                            <a class="button button-small" target="_blank" href="<?php echo esc_url( 'https://dashboard.stripe.com/' . ( ml_stripe_mode() === 'test' ? 'test/' : '' ) . 'subscriptions/' . $r->stripe_subscription_id ); ?>"><?php esc_html_e( 'Stripe', 'memorylane' ); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
