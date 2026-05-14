<?php defined( 'ABSPATH' ) || exit;
$detail_id = $detail_id ?? 0;
$user      = get_user_by( 'id', $detail_id );
if ( ! $user ) {
    echo '<div class="mla-card"><p class="mla-muted">Customer not found.</p><a class="mla-btn mla-btn--ghost" href="' . esc_url( home_url( '/admin/customers' ) ) . '">← Back</a></div>';
    return;
}

global $wpdb;
$setup_state = (string) get_user_meta( $user->ID, ML_META_SETUP_STATE, true );
$sub         = ml_get_subscription_row( $user->ID );
$stripe_cust = (string) get_user_meta( $user->ID, ML_META_STRIPE_CUSTOMER, true );

$tours = get_posts( array(
    'post_type'   => ML_CPT_TOUR,
    'numberposts' => -1,
    'meta_query'  => array( array( 'key' => ML_META_TOUR_USER, 'value' => $user->ID ) ),
) );

$bookings = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM " . ml_table( 'bookings' ) . " WHERE user_id=%d ORDER BY scheduled_for DESC LIMIT 10",
    $user->ID
) );
?>

<a class="mla-btn mla-btn--ghost" href="<?php echo esc_url( home_url( '/admin/customers' ) ); ?>" style="margin-bottom:12px;display:inline-block;">← All customers</a>

<div class="mla-card">
    <h2><?php echo esc_html( $user->display_name ?: $user->user_email ); ?></h2>
    <table class="mla-table">
        <tbody>
            <tr><th style="width:200px;">Email</th><td><?php echo esc_html( $user->user_email ); ?></td></tr>
            <tr><th>Joined</th><td><?php echo esc_html( ml_format_datetime( $user->user_registered ) ); ?></td></tr>
            <tr><th>Setup state</th><td><?php echo esc_html( $setup_state ?: '—' ); ?></td></tr>
            <tr><th>Subscription</th><td>
                <?php if ( $sub ) : ?>
                    <span class="mla-pill mla-pill--neutral"><?php echo esc_html( $sub->status ); ?></span>
                    · next billing <?php echo esc_html( ml_format_date( $sub->current_period_end ) ); ?>
                <?php else : ?>
                    <span class="mla-muted">No subscription yet</span>
                <?php endif; ?>
            </td></tr>
            <tr><th>Stripe customer</th><td>
                <?php if ( $stripe_cust ) : ?>
                    <code style="font-size:12px;"><?php echo esc_html( $stripe_cust ); ?></code>
                    · <a target="_blank" rel="noopener" href="<?php echo esc_url( 'https://dashboard.stripe.com/' . ( ml_stripe_mode() === 'live' ? '' : 'test/' ) . 'customers/' . $stripe_cust ); ?>">Open in Stripe ↗</a>
                <?php else : ?>
                    <span class="mla-muted">—</span>
                <?php endif; ?>
            </td></tr>
        </tbody>
    </table>

    <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ( $setup_state === ML_SETUP_STATE_PENDING ) : ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'ml_ap_customer_approve' ); ?>
                <input type="hidden" name="action" value="ml_ap_customer_approve">
                <input type="hidden" name="user_id" value="<?php echo (int) $user->ID; ?>">
                <button class="mla-btn mla-btn--primary" type="submit">✓ Approve access</button>
            </form>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Archive all active tours for this customer?');">
            <?php wp_nonce_field( 'ml_ap_customer_deactivate' ); ?>
            <input type="hidden" name="action" value="ml_ap_customer_deactivate">
            <input type="hidden" name="user_id" value="<?php echo (int) $user->ID; ?>">
            <button class="mla-btn mla-btn--danger" type="submit">Deactivate access</button>
        </form>
    </div>
</div>

<div class="mla-card">
    <h2>Tours (<?php echo count( $tours ); ?>)</h2>
    <?php if ( empty( $tours ) ) : ?>
        <p class="mla-muted">No tours yet. <a href="<?php echo esc_url( home_url( '/admin/tours/new?user_id=' . $user->ID ) ); ?>">Add one</a>.</p>
    <?php else : ?>
        <table class="mla-table">
            <thead><tr><th>Title</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ( $tours as $t ) :
                $status = (string) get_post_meta( $t->ID, ML_META_TOUR_STATUS, true );
            ?>
                <tr>
                    <td><?php echo esc_html( $t->post_title ); ?></td>
                    <td><span class="mla-pill mla-pill--<?php echo $status === ML_TOUR_STATUS_ACTIVE ? 'success' : 'neutral'; ?>"><?php echo esc_html( $status ); ?></span></td>
                    <td style="text-align:right;"><a class="mla-btn mla-btn--ghost" href="<?php echo esc_url( home_url( '/admin/tours/' . $t->ID ) ); ?>">Edit →</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="mla-card">
    <h2>Recent bookings</h2>
    <?php if ( empty( $bookings ) ) : ?>
        <p class="mla-muted">No bookings.</p>
    <?php else : ?>
        <table class="mla-table">
            <thead><tr><th>Scheduled</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ( $bookings as $b ) : ?>
                <tr><td><?php echo esc_html( ml_format_datetime( $b->scheduled_for ) ); ?></td><td><?php echo esc_html( $b->status ); ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
