<?php
/**
 * Memory Lane — reactivation REST + admin-post handlers.
 *
 *   POST /wp-json/memorylane/v1/reactivate     — customer initiates (nonce + login)
 *   admin-post: ml_reactivation_complete       — admin "Reactivation done"
 */
defined( 'ABSPATH' ) || exit;

/**
 * Customer-initiated reactivation: REST endpoint returns the Stripe Checkout URL.
 *
 * Body: { plan: 'monthly' | 'annual', _wpnonce: '...' }
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'memorylane/v1', '/reactivate', array(
        'methods'             => 'POST',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
        'callback' => 'ml_rest_reactivate',
    ) );
} );

function ml_rest_reactivate( WP_REST_Request $req ) {
    $nonce = $req->get_header( 'x_wp_nonce' ) ?: $req->get_param( '_wpnonce' );
    if ( ! wp_verify_nonce( (string) $nonce, 'wp_rest' ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'bad_nonce' ), 403 );
    }

    $user_id = get_current_user_id();

    // Rate limit per user: 5 attempts / 15 min.
    $rl_key = 'ml_react_rl_' . $user_id;
    $count  = (int) get_transient( $rl_key );
    if ( $count >= 5 ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'rate_limited' ), 429 );
    }
    set_transient( $rl_key, $count + 1, 15 * MINUTE_IN_SECONDS );

    $plan = (string) ( $req->get_param( 'plan' ) ?: 'monthly' );
    $plan = $plan === 'annual' ? 'annual' : 'monthly';

    $result = ml_open_reactivation_session( $user_id, $plan );
    if ( ! $result['ok'] ) {
        $status = $result['error'] === 'reactivation_already_pending' ? 409 : 400;
        return new WP_REST_Response( array( 'ok' => false, 'error' => $result['error'] ), $status );
    }
    return array( 'ok' => true, 'url' => $result['url'] );
}

/**
 * Admin clicks "Reactivation done" in WP admin.
 */
add_action( 'admin_post_ml_reactivation_complete', 'ml_handle_admin_reactivation_complete' );

function ml_handle_admin_reactivation_complete() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    check_admin_referer( 'ml_reactivation_complete' );

    $row_id   = (int) ( $_POST['row_id'] ?? 0 );
    $redirect = wp_get_referer() ?: admin_url( 'admin.php?page=memorylane-reactivations' );

    $result = ml_complete_reactivation( $row_id, get_current_user_id() );
    if ( ! $result['ok'] ) {
        wp_safe_redirect( add_query_arg( 'ml_err', rawurlencode( $result['error'] ), $redirect ) );
        exit;
    }

    $msg = ! empty( $result['noop'] )
        ? __( 'Reactivation was already completed.', 'memorylane' )
        : __( 'Reactivation completed — tour is now live for the customer.', 'memorylane' );
    wp_safe_redirect( add_query_arg( 'ml_msg', rawurlencode( $msg ), $redirect ) );
    exit;
}
