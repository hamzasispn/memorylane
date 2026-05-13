<?php
defined( 'ABSPATH' ) || exit;

$user        = wp_get_current_user();
$has_access  = ml_user_has_access( $user->ID );
$is_pending  = ml_user_is_pending_approval( $user->ID );
$sub_row     = ml_get_subscription_row( $user->ID );
$upcoming    = ml_get_user_next_booking( $user->ID );
$tour_count  = ml_count_user_tours( $user->ID );

if ( $is_pending ) {
    $status_label = ml_t( 'overview.pending.pill', 'In afwachting van goedkeuring' );
    $status_pill  = 'ml-pill--warning';
} else {
    $status_label = $sub_row ? ml_subscription_status_label( $sub_row ) : ml_t( 'sub.status.cancelled' );
    $status_pill  = $sub_row ? ml_subscription_status_pill_class( $sub_row ) : 'ml-pill--neutral';
}
?>
<div>
    <h1 class="ml-h1"><?php echo esc_html( ml_t( 'overview.title' ) ); ?>, <?php echo esc_html( $user->display_name ?: $user->user_email ); ?></h1>
    <p class="ml-sub"><?php ml_e( 'overview.subtitle' ); ?></p>

    <?php if ( $is_pending ) :
        $paid_at = get_user_meta( $user->ID, ML_META_SETUP_PAID_AT, true );
    ?>
        <div class="ml-card ml-card--lg ml-mb-3" style="background:#FEF3C7;border-color:#FCD34D;">
            <div class="ml-flex ml-items-center ml-gap-3">
                <span style="font-size:32px;">⏳</span>
                <div>
                    <h2 class="ml-h2 ml-mb-0" style="color:#78350F;">
                        <?php echo esc_html( ml_t( 'overview.pending.title', 'Je aanvraag wordt verwerkt' ) ); ?>
                    </h2>
                    <p class="ml-text-sm ml-mt-1" style="color:#78350F;margin-bottom:0;">
                        <?php echo esc_html( sprintf( ml_t( 'overview.pending.body', 'We hebben je betaling ontvangen. Ons team verwerkt je aanvraag binnen %d uur en activeert je toegang. Je krijgt een e-mail zodra je tour beschikbaar is. In de tussentijd kan je alvast een opname-afspraak inplannen.' ), ML_APPROVAL_SLA_HOURS ) ); ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="ml-grid">
        <div class="ml-card">
            <p class="ml-card__title"><?php ml_e( 'overview.card.subscription' ); ?></p>
            <p class="ml-card__value">
                <span class="ml-pill <?php echo esc_attr( $status_pill ); ?>"><?php echo esc_html( $status_label ); ?></span>
            </p>
            <p class="ml-text-sm ml-text-muted ml-mt-1">
                <?php if ( $sub_row && $sub_row->current_period_end ) : ?>
                    <?php ml_e( 'sub.next_billing' ); ?>: <?php echo esc_html( ml_format_date( $sub_row->current_period_end ) ); ?>
                <?php else : ?>
                    —
                <?php endif; ?>
            </p>
        </div>

        <div class="ml-card">
            <p class="ml-card__title"><?php ml_e( 'overview.card.tours' ); ?></p>
            <p class="ml-card__value"><?php echo (int) $tour_count; ?></p>
            <p class="ml-text-sm ml-text-muted ml-mt-1">
                <?php if ( $tour_count > 0 ) : ?>
                    <a href="<?php echo esc_url( home_url( '/dashboard/tours' ) ); ?>"><?php ml_e( 'tours.view' ); ?> →</a>
                <?php else : ?>
                    <?php ml_e( 'overview.empty.tours' ); ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="ml-card">
            <p class="ml-card__title"><?php ml_e( 'overview.card.booking' ); ?></p>
            <?php if ( $upcoming ) : ?>
                <p class="ml-card__value"><?php echo esc_html( ml_format_datetime( $upcoming->scheduled_for ) ); ?></p>
                <p class="ml-text-sm ml-text-muted ml-mt-1"><span class="ml-pill ml-pill--info"><?php echo esc_html( ml_t( 'booking.status.' . $upcoming->status, $upcoming->status ) ); ?></span></p>
            <?php else : ?>
                <p class="ml-card__value">—</p>
                <p class="ml-text-sm ml-text-muted ml-mt-1">
                    <a href="<?php echo esc_url( home_url( '/dashboard/booking' ) ); ?>"><?php ml_e( 'booking.title' ); ?> →</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( ! $has_access ) : ?>
        <div class="ml-alert ml-alert--warning ml-mt-3">
            <strong><?php ml_e( 'tours.access_expired.title' ); ?>.</strong>
            <?php ml_e( 'tours.access_expired.body' ); ?>
            <a class="ml-btn ml-btn--secondary ml-mt-2" href="<?php echo esc_url( home_url( '/dashboard/subscription' ) ); ?>"><?php ml_e( 'tours.access_expired.cta' ); ?></a>
        </div>
    <?php endif; ?>
</div>
