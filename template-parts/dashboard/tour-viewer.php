<?php
defined( 'ABSPATH' ) || exit;
$user  = wp_get_current_user();
$slug  = get_query_var( 'ml_slug' );
$tour  = $slug ? get_page_by_path( $slug, OBJECT, ML_CPT_TOUR ) : null;

if ( ! $tour ) {
    echo '<div class="ml-empty"><div class="ml-empty__title">' . esc_html( ml_t( 'error.invalid_request' ) ) . '</div></div>';
    return;
}
$tour_user = (int) get_post_meta( $tour->ID, ML_META_TOUR_USER, true );
if ( $tour_user !== $user->ID && ! current_user_can( ML_CAP_MANAGE ) ) {
    echo '<div class="ml-empty"><div class="ml-empty__title">' . esc_html( ml_t( 'error.access_denied' ) ) . '</div></div>';
    return;
}
$status = get_post_meta( $tour->ID, ML_META_TOUR_STATUS, true );
$embed  = get_post_meta( $tour->ID, ML_META_TOUR_EMBED, true );

if ( ! ml_user_has_access( $user->ID ) || $status !== ML_TOUR_STATUS_ACTIVE ) :
?>
<div class="ml-card ml-card--lg" style="text-align: center; max-width: 540px; margin: 32px auto;">
    <h1 class="ml-h2"><?php ml_e( 'tours.access_expired.title' ); ?></h1>
    <p class="ml-sub"><?php ml_e( 'tours.access_expired.body' ); ?></p>
    <a class="ml-btn ml-btn--primary" href="<?php echo esc_url( home_url( '/dashboard/subscription' ) ); ?>"><?php ml_e( 'tours.access_expired.cta' ); ?></a>
</div>
<?php else : ?>
<div>
    <a href="<?php echo esc_url( home_url( '/dashboard/tours' ) ); ?>" class="ml-text-sm">← <?php ml_e( 'common.back' ); ?></a>
    <h1 class="ml-h1 ml-mt-1"><?php echo esc_html( get_post_meta( $tour->ID, ML_META_TOUR_ADDRESS, true ) ?: $tour->post_title ); ?></h1>
    <div class="ml-mt-2"><?php echo ml_safe_tour_embed( (string) $embed ); ?></div>
</div>
<?php endif;
