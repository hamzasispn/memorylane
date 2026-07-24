<?php
/**
 * Memory Lane — reactivation of an archived tour (one-time fee).
 * Customer pays the reactivation fee; staff restore the tour (≤8h). Paying also
 * restarts the monthly subscription (no free year) so access returns.
 */
defined( 'ABSPATH' ) || exit;

/** Start the reactivation Stripe Checkout for the logged-in customer. */
add_action( 'admin_post_ml_reactivate', function () {
    if ( ! is_user_logged_in() ) { wp_safe_redirect( home_url( '/login' ) ); exit; }
    check_admin_referer( 'ml_reactivate' );
    $user = wp_get_current_user();
    $back = home_url( '/dashboard/subscription' );

    if ( ! function_exists( 'ml_stripe_is_configured' ) || ! ml_stripe_is_configured() || ! ml_stripe_reactivation_price_id() ) {
        ml_flash_set( 'error', __( 'Reactivation is not available right now.', 'memorylane' ) );
        wp_safe_redirect( $back ); exit;
    }
    $stripe = ml_stripe();
    $cust   = get_user_meta( $user->ID, ML_META_STRIPE_CUSTOMER, true );
    try {
        $args = array(
            'mode'                => 'payment',
            'line_items'          => array( array( 'price' => ml_stripe_reactivation_price_id(), 'quantity' => 1 ) ),
            'payment_intent_data' => array( 'setup_future_usage' => 'off_session' ),
            'invoice_creation'    => array( 'enabled' => true ),
            'locale'              => ml_current_lang() === 'en' ? 'en' : 'nl',
            'success_url'         => home_url( '/dashboard/subscription?reactivated=1' ),
            'cancel_url'          => $back,
            'metadata'            => array( 'ml_intent' => 'reactivation', 'ml_user_id' => (string) $user->ID ),
        );
        if ( $cust ) {
            $args['customer'] = $cust;
        } else {
            $args['customer_creation'] = 'always';
            $args['customer_email']    = $user->user_email;
        }
        $session = $stripe->checkout->sessions->create( $args );
        wp_redirect( $session->url, 303 ); exit;
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] reactivation checkout failed: ' . $e->getMessage() );
        ml_flash_set( 'error', __( 'Could not start reactivation. Please try again.', 'memorylane' ) );
        wp_safe_redirect( $back ); exit;
    }
} );

/**
 * Handle a paid reactivation (called from checkout.session.completed).
 * Flags the customer's archived tours for staff to restore, restarts the
 * monthly subscription (no trial), and notifies admin + customer.
 */
function ml_reactivation_complete( $user, $session ) {
    $tours = get_posts( array(
        'post_type'   => ML_CPT_TOUR,
        'post_status' => 'any',
        'numberposts' => -1,
        'fields'      => 'ids',
        'meta_query'  => array( array( 'key' => ML_META_TOUR_USER, 'value' => $user->ID ) ),
    ) );
    foreach ( $tours as $tid ) {
        $st = get_post_meta( $tid, ML_META_TOUR_STATUS, true );
        if ( in_array( $st, array( ML_TOUR_STATUS_ARCHIVED, ML_TOUR_STATUS_PENDING_ARCHIVE ), true ) ) {
            update_post_meta( $tid, ML_META_TOUR_STATUS, ML_TOUR_STATUS_PENDING_REACTIVATION );
        }
    }

    // Restart the monthly subscription with NO trial (they had year 1 already).
    if ( function_exists( 'ml_create_monthly_subscription' ) ) {
        $pm = '';
        $pi = $session->payment_intent ?? null;
        if ( is_object( $pi ) ) {
            $pm = is_object( $pi->payment_method ?? null ) ? ( $pi->payment_method->id ?? '' ) : (string) ( $pi->payment_method ?? '' );
        }
        ml_create_monthly_subscription( (int) $user->ID, $pm, 0 );
    }

    $sla = defined( 'ML_REACTIVATION_SLA_HOURS' ) ? ML_REACTIVATION_SLA_HOURS : 8;
    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_reactivation_request', array( 'user' => $user ) );
    }
    ml_mail_send( $user->user_email, 'reactivation_pending', array( 'user' => $user, 'sla_hours' => $sla ), $user->ID );
}
