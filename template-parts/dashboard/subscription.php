<?php
defined( 'ABSPATH' ) || exit;
$user       = wp_get_current_user();
$row        = ml_get_subscription_row( $user->ID );
$is_pending = ml_user_is_pending_approval( $user->ID );
$paid_at    = get_user_meta( $user->ID, ML_META_SETUP_PAID_AT, true );
$paid_amt   = (int) get_user_meta( $user->ID, ML_META_SETUP_AMOUNT, true );
$paid_cur   = strtoupper( (string) get_user_meta( $user->ID, ML_META_SETUP_CURRENCY, true ) );
?>
<div>
    <h1 class="ml-h1"><?php ml_e( 'sub.title' ); ?></h1>
    <p class="ml-sub"></p>

    <?php if ( $is_pending ) : ?>
        <div class="ml-card ml-card--lg" style="background:#FEF3C7;border-color:#FCD34D;">
            <div class="ml-row-between">
                <div>
                    <p class="ml-card__title" style="color:#92400E;"><?php echo esc_html( ml_t( 'sub.pending.title', 'In afwachting van goedkeuring' ) ); ?></p>
                    <p class="ml-card__value" style="color:#78350F;"><span class="ml-pill ml-pill--warning"><?php echo esc_html( ml_t( 'overview.pending.pill', 'In afwachting' ) ); ?></span></p>
                </div>
                <?php if ( $paid_amt ) : ?>
                    <div style="text-align:right;">
                        <p class="ml-text-sm ml-text-muted" style="color:#78350F;"><?php echo esc_html( ml_t( 'sub.pending.paid', 'Betaling ontvangen' ) ); ?></p>
                        <p class="ml-h3" style="color:#78350F;"><?php echo esc_html( $paid_cur . ' ' . number_format( $paid_amt / 100, 2 ) ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <hr class="ml-divider">
            <p style="color:#78350F;margin:0;"><?php echo esc_html( sprintf( ml_t( 'sub.pending.body', 'Je toegang wordt geactiveerd binnen %d uur. Het maandabonnement start automatisch na 12 maanden vanaf de activering en is dan op elk moment opzegbaar.' ), ML_APPROVAL_SLA_HOURS ) ); ?></p>
        </div>
    <?php elseif ( ! $row ) : ?>
        <div class="ml-empty">
            <div class="ml-empty__title"><?php echo esc_html__( 'No subscription yet', 'memorylane' ); ?></div>
            <p class="ml-text-sm"><?php echo esc_html__( 'You do not have an active subscription on this account.', 'memorylane' ); ?></p>
        </div>
    <?php else :
        $phase = ml_subscription_phase( $row );
        $label = ml_subscription_status_label( $row );
        $pill  = ml_subscription_status_pill_class( $row );
    ?>
        <div class="ml-card ml-card--lg">
            <div class="ml-row-between">
                <div>
                    <p class="ml-card__title"><?php ml_e( $phase === 'year_one' ? 'sub.phase.year_one' : 'sub.phase.monthly' ); ?></p>
                    <p class="ml-card__value"><span class="ml-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( $label ); ?></span></p>
                </div>
                <div style="text-align: right;">
                    <p class="ml-text-sm ml-text-muted"><?php ml_e( 'sub.next_billing' ); ?></p>
                    <p class="ml-h3"><?php echo esc_html( ml_format_date( $row->current_period_end ) ); ?></p>
                </div>
            </div>

            <?php if ( $row->cancel_at_period_end ) : ?>
                <div class="ml-alert ml-alert--warning ml-mt-2">
                    <?php echo esc_html( str_replace( '{date}', ml_format_date( $row->current_period_end ), ml_t( 'sub.cancelled_msg' ) ) ); ?>
                </div>
            <?php endif; ?>

            <hr class="ml-divider">

            <div class="ml-flex ml-gap-2" style="flex-wrap: wrap;">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
                    <?php wp_nonce_field( 'ml_portal' ); ?>
                    <input type="hidden" name="action" value="ml_portal">
                    <button type="submit" class="ml-btn ml-btn--secondary"><?php ml_e( 'sub.manage_in_stripe' ); ?></button>
                </form>

                <?php if ( ! $row->cancel_at_period_end && in_array( $row->status, array( 'active', 'trialing', 'past_due' ), true ) ) : ?>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;"
                          onsubmit="return confirm('<?php echo esc_js( ml_t( 'sub.cancel_confirm' ) ); ?>');">
                        <?php wp_nonce_field( 'ml_sub_cancel' ); ?>
                        <input type="hidden" name="action" value="ml_sub_cancel">
                        <button type="submit" class="ml-btn ml-btn--danger"><?php ml_e( 'sub.cancel' ); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
