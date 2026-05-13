<?php
defined( 'ABSPATH' ) || exit;
$user = wp_get_current_user();
if ( ! ml_user_can_book( $user->ID ) ) {
    echo '<div class="ml-card ml-card--lg" style="text-align: center; max-width: 540px; margin: 32px auto;">';
    echo '<h1 class="ml-h2">' . esc_html( ml_t( 'tours.access_expired.title' ) ) . '</h1>';
    echo '<p class="ml-sub">' . esc_html( ml_t( 'tours.access_expired.body' ) ) . '</p>';
    echo '<a class="ml-btn ml-btn--primary" href="' . esc_url( home_url( '/dashboard/subscription' ) ) . '">' . esc_html( ml_t( 'tours.access_expired.cta' ) ) . '</a>';
    echo '</div>';
    return;
}
$user_bookings = ml_get_user_bookings( $user->ID );
?>
<div>
    <h1 class="ml-h1"><?php ml_e( 'booking.title' ); ?></h1>
    <p class="ml-sub"><?php ml_e( 'booking.subtitle' ); ?></p>

    <?php if ( ! empty( $user_bookings ) ) : ?>
        <div class="ml-card ml-mb-3">
            <h2 class="ml-h2"><?php ml_e( 'overview.card.booking' ); ?></h2>
            <div class="ml-table-wrap ml-mt-2">
                <table class="ml-table">
                    <thead><tr>
                        <th><?php echo esc_html__( 'Date', 'memorylane' ); ?></th>
                        <th><?php echo esc_html__( 'Status', 'memorylane' ); ?></th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ( $user_bookings as $b ) :
                        $pill = array(
                            'requested' => 'ml-pill--warning',
                            'confirmed' => 'ml-pill--success',
                            'completed' => 'ml-pill--neutral',
                            'cancelled' => 'ml-pill--neutral',
                        )[ $b->status ] ?? 'ml-pill--neutral';
                    ?>
                        <tr>
                            <td><?php echo esc_html( ml_format_datetime( $b->scheduled_for ) ); ?></td>
                            <td><span class="ml-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( ml_t( 'booking.status.' . $b->status, $b->status ) ); ?></span></td>
                            <td style="text-align: right;">
                                <?php if ( in_array( $b->status, array( 'requested', 'confirmed' ), true ) ) : ?>
                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;" onsubmit="return confirm('<?php echo esc_js( ml_t( 'sub.cancel_confirm' ) ); ?>');">
                                        <?php wp_nonce_field( 'ml_booking_cancel' ); ?>
                                        <input type="hidden" name="action" value="ml_booking_cancel">
                                        <input type="hidden" name="id" value="<?php echo (int) $b->id; ?>">
                                        <button class="ml-btn ml-btn--ghost ml-text-sm" type="submit"><?php ml_e( 'booking.cancel' ); ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <h2 class="ml-h2"><?php echo esc_html__( 'Pick your appointment', 'memorylane' ); ?></h2>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ml-mt-2">
        <?php wp_nonce_field( 'ml_booking_request' ); ?>
        <input type="hidden" name="action" value="ml_booking_request">
        <input type="hidden" name="date" value="">
        <input type="hidden" name="time" value="">

        <?php
            $picker_id = 'ml-dash-picker';
            include ML_PATH . 'template-parts/booking/picker.php';
        ?>

        <div class="ml-mt-3">
            <label class="ml-label"><?php echo esc_html__( 'Notes (optional)', 'memorylane' ); ?></label>
            <textarea name="notes" class="ml-input" rows="3"></textarea>
        </div>

        <button class="ml-btn ml-btn--primary ml-mt-2" type="submit" disabled><?php ml_e( 'common.confirm' ); ?></button>
    </form>
</div>
