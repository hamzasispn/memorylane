<?php
defined( 'ABSPATH' ) || exit;
$user  = wp_get_current_user();
$tours = ml_get_user_tours( $user->ID );
$has_access = ml_user_has_access( $user->ID );
?>
<div>
    <h1 class="ml-h1"><?php ml_e( 'tours.title' ); ?></h1>
    <p class="ml-sub"></p>

    <?php if ( empty( $tours ) ) : ?>
        <div class="ml-empty">
            <div class="ml-empty__title"><?php ml_e( 'overview.empty.tours' ); ?></div>
            <p class="ml-text-sm"><?php ml_e( 'tours.empty' ); ?></p>
        </div>
    <?php else : ?>
        <div class="ml-grid">
            <?php foreach ( $tours as $t ) :
                $status = get_post_meta( $t->ID, ML_META_TOUR_STATUS, true ) ?: ML_TOUR_STATUS_ACTIVE;
                $addr   = get_post_meta( $t->ID, ML_META_TOUR_ADDRESS, true );
                $pill   = $status === ML_TOUR_STATUS_ACTIVE ? 'ml-pill--success' : ( $status === ML_TOUR_STATUS_ARCHIVED ? 'ml-pill--neutral' : 'ml-pill--warning' );
            ?>
                <div class="ml-card">
                    <p class="ml-card__title"><?php echo esc_html( $addr ?: $t->post_title ); ?></p>
                    <p class="ml-mt-1"><span class="ml-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( ml_t( 'tours.status.' . $status, $status ) ); ?></span></p>
                    <?php if ( $has_access && $status === ML_TOUR_STATUS_ACTIVE ) : ?>
                        <a class="ml-btn ml-btn--primary ml-mt-2" href="<?php echo esc_url( home_url( '/dashboard/tour/' . $t->post_name ) ); ?>"><?php ml_e( 'tours.view' ); ?></a>
                    <?php else : ?>
                        <span class="ml-text-sm ml-text-muted ml-mt-2"><?php ml_e( 'tours.access_expired.body' ); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
