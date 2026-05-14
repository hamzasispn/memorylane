<?php
/**
 * Memory Lane — WP admin top-level menu.
 *
 * V2-9: the operator-facing admin lives at /admin (custom panel, see
 * inc/admin-panel/ + template-parts/admin-panel/). The legacy wp-admin
 * sub-menu pages registered here have been retired. We leave a single
 * stub menu entry that points operators at /admin so they don't get
 * lost if they were used to opening "Memory Lane" from the WP sidebar.
 *
 * The other inc/admin/*.php files (settings, customers, webhooks-log,
 * notifications-log, manual-actions, approve-access, reactivations-page,
 * subscriptions-page) intentionally remain loaded — they define helper
 * functions and admin-post handlers that the new /admin panel uses.
 * Their page-render functions just go unreferenced.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
    add_menu_page(
        __( 'Memory Lane', 'memorylane' ),
        __( 'Memory Lane', 'memorylane' ),
        ML_CAP_MANAGE,
        'memorylane',
        'ml_admin_render_stub',
        'dashicons-camera-alt',
        58
    );
} );

function ml_admin_render_stub() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    $url = home_url( '/admin' );
    echo '<div class="wrap"><h1>Memory Lane</h1>';
    echo '<p>The Memory Lane admin moved to a custom panel.</p>';
    echo '<p><a class="button button-primary button-hero" href="' . esc_url( $url ) . '">Open Memory Lane admin →</a></p>';
    echo '<p style="margin-top:24px;color:#666;">URL: <code>' . esc_html( $url ) . '</code></p>';
    echo '</div>';
}
