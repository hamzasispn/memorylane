<?php
/**
 * Memory Lane — admin top-level menu.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
    add_menu_page(
        __( 'Memory Lane', 'memorylane' ),
        __( 'Memory Lane', 'memorylane' ),
        ML_CAP_MANAGE,
        'memorylane',
        'ml_admin_render_overview',
        'dashicons-camera-alt',
        58
    );
    add_submenu_page( 'memorylane', __( 'Overview', 'memorylane' ),       __( 'Overview', 'memorylane' ),       ML_CAP_MANAGE, 'memorylane',                'ml_admin_render_overview' );
    add_submenu_page( 'memorylane', __( 'Customers', 'memorylane' ),      __( 'Customers', 'memorylane' ),      ML_CAP_MANAGE, 'memorylane-customers',      'ml_admin_render_customers' );
    add_submenu_page( 'memorylane', __( 'Subscriptions', 'memorylane' ),  __( 'Subscriptions', 'memorylane' ),  ML_CAP_MANAGE, 'memorylane-subscriptions',  'ml_admin_render_subscriptions' );
    add_submenu_page( 'memorylane', __( 'Bookings', 'memorylane' ),       __( 'Bookings', 'memorylane' ),       ML_CAP_MANAGE, 'memorylane-bookings',       'ml_admin_render_bookings' );
    add_submenu_page( 'memorylane', __( 'Webhooks log', 'memorylane' ),   __( 'Webhooks log', 'memorylane' ),   ML_CAP_MANAGE, 'memorylane-webhooks',       'ml_admin_render_webhooks_log' );
    add_submenu_page( 'memorylane', __( 'Notifications', 'memorylane' ),  __( 'Notifications', 'memorylane' ),  ML_CAP_MANAGE, 'memorylane-notifications',  'ml_admin_render_notifications_log' );
    add_submenu_page( 'memorylane', __( 'Settings', 'memorylane' ),       __( 'Settings', 'memorylane' ),       ML_CAP_MANAGE, 'memorylane-settings',       'ml_admin_render_settings' );
} );

function ml_admin_render_overview() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    global $wpdb;
    $subs_tbl = ml_table( 'subscriptions' );
    $wh_tbl   = ml_table( 'webhook_events' );

    $active     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$subs_tbl} WHERE status='active' AND cancel_at_period_end=0" );
    $cancelling = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$subs_tbl} WHERE cancel_at_period_end=1" );
    $past_due   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$subs_tbl} WHERE status='past_due'" );
    $wh_failed  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wh_tbl} WHERE status='failed' AND received_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)" );

    echo '<div class="wrap"><h1>' . esc_html__( 'Memory Lane', 'memorylane' ) . '</h1>';
    echo '<p>' . esc_html__( 'Operations overview.', 'memorylane' ) . '</p>';

    echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;max-width:900px;">';
    ml_admin_kpi( __( 'Active subscriptions', 'memorylane' ), $active );
    ml_admin_kpi( __( 'Pending cancellations', 'memorylane' ), $cancelling );
    ml_admin_kpi( __( 'Past due', 'memorylane' ), $past_due );
    ml_admin_kpi( __( 'Webhook failures (24h)', 'memorylane' ), $wh_failed );
    echo '</div>';

    if ( ! ml_stripe_is_connected() ) {
        echo '<div class="notice notice-warning" style="margin-top:24px;"><p><strong>' . esc_html__( 'Stripe is not connected yet.', 'memorylane' ) . '</strong> ';
        echo '<a href="' . esc_url( admin_url( 'admin.php?page=memorylane-settings' ) ) . '">' . esc_html__( 'Go to Settings → Stripe', 'memorylane' ) . '</a></p></div>';
    }
    echo '</div>';
}

function ml_admin_kpi( $label, $value ) {
    echo '<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;">';
    echo '<div style="color:#666;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">' . esc_html( $label ) . '</div>';
    echo '<div style="font-size:28px;font-weight:600;margin-top:4px;">' . esc_html( $value ) . '</div>';
    echo '</div>';
}
