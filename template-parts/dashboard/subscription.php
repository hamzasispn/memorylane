<?php
defined( 'ABSPATH' ) || exit;
$user = wp_get_current_user();
$row  = function_exists( 'ml_get_subscription_row' ) ? ml_get_subscription_row( $user->ID ) : null;
$post = esc_url( admin_url( 'admin-post.php' ) );
?>
<div>
    <h1 class="ml-h1"><?php ml_e( 'sub.title' ); ?></h1>
    <p class="ml-sub"></p>

    <?php if ( ! empty( $_GET['reactivated'] ) ) : ?>
        <div class="ml-alert ml-alert--success ml-mb-2"><?php echo esc_html__( 'Payment received — your tour will be back online shortly (within 8 hours).', 'memorylane' ); ?></div>
    <?php endif; ?>

    <?php
    $can_reactivate = function_exists( 'ml_stripe_reactivation_price_id' ) && ml_stripe_reactivation_price_id()
        && ( ! $row || ! in_array( $row->status, array( 'active', 'trialing' ), true ) );
    ?>

    <?php if ( ! $row ) : ?>
        <div class="ml-empty">
            <div class="ml-empty__title"><?php echo esc_html__( 'No subscription yet', 'memorylane' ); ?></div>
            <p class="ml-text-sm"><?php echo esc_html__( 'There is no active subscription on this account.', 'memorylane' ); ?></p>
            <?php if ( $can_reactivate ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ml-mt-2">
                    <?php wp_nonce_field( 'ml_reactivate' ); ?>
                    <input type="hidden" name="action" value="ml_reactivate">
                    <button class="ml-btn ml-btn--primary" type="submit"><?php echo esc_html__( 'Reactivate my tour', 'memorylane' ); ?></button>
                </form>
            <?php endif; ?>
        </div>
    <?php else :
        $label = ml_subscription_status_label( $row );
        $pill  = ml_subscription_status_pill_class( $row );
    ?>
        <div class="ml-card ml-card--lg">
            <div class="ml-row-between">
                <div>
                    <p class="ml-card__title"><?php echo esc_html__( 'Status', 'memorylane' ); ?></p>
                    <p class="ml-card__value"><span class="ml-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( $label ); ?></span></p>
                </div>
                <div style="text-align:right;">
                    <p class="ml-text-sm ml-text-muted"><?php ml_e( 'sub.next_billing' ); ?></p>
                    <p class="ml-h3"><?php echo esc_html( $row->current_period_end ? ml_format_date( $row->current_period_end ) : '—' ); ?></p>
                </div>
            </div>
            <?php if ( ! empty( $row->cancel_at_period_end ) ) : ?>
                <hr class="ml-divider">
                <p class="ml-text-sm ml-text-muted"><?php echo esc_html__( 'Your subscription ends at the end of the current period.', 'memorylane' ); ?></p>
            <?php endif; ?>
            <hr class="ml-divider">
            <div class="ml-flex ml-gap-2">
                <form method="post" action="<?php echo $post; ?>" style="display:inline;">
                    <?php wp_nonce_field( 'ml_portal' ); ?>
                    <input type="hidden" name="action" value="ml_portal">
                    <button class="ml-btn ml-btn--primary" type="submit"><?php echo esc_html__( 'Manage payment method', 'memorylane' ); ?></button>
                </form>
                <?php if ( empty( $row->cancel_at_period_end ) && in_array( $row->status, array( 'active', 'trialing' ), true ) ) : ?>
                    <form method="post" action="<?php echo $post; ?>" style="display:inline;" onsubmit="return confirm('Cancel your subscription at period end?');">
                        <?php wp_nonce_field( 'ml_sub_cancel' ); ?>
                        <input type="hidden" name="action" value="ml_sub_cancel">
                        <button class="ml-btn ml-btn--ghost" type="submit"><?php echo esc_html__( 'Cancel subscription', 'memorylane' ); ?></button>
                    </form>
                <?php endif; ?>
                <?php if ( $can_reactivate ) : ?>
                    <form method="post" action="<?php echo $post; ?>" style="display:inline;">
                        <?php wp_nonce_field( 'ml_reactivate' ); ?>
                        <input type="hidden" name="action" value="ml_reactivate">
                        <button class="ml-btn ml-btn--primary" type="submit"><?php echo esc_html__( 'Reactivate my tour', 'memorylane' ); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
