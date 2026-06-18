<?php
defined( 'ABSPATH' ) || exit;

$user        = wp_get_current_user();
$upcoming    = ml_get_user_next_booking( $user->ID );
$tour_count  = ml_count_user_tours( $user->ID );
?>
<div>
    <h1 class="ml-h1"><?php echo esc_html( ml_t( 'overview.title' ) ); ?>, <?php echo esc_html( $user->display_name ?: $user->user_email ); ?></h1>
    <p class="ml-sub"><?php ml_e( 'overview.subtitle' ); ?></p>

    <div class="ml-grid">
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
</div>
