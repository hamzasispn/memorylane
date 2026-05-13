<?php
/**
 * Memory Lane — Booking REST endpoints (date-picker support).
 */
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
    register_rest_route( 'memorylane/v1', '/booking/slots', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'ml_rest_booking_slots',
        'args'                => array(
            'date' => array( 'required' => true, 'type' => 'string' ),
        ),
    ) );
} );

function ml_rest_booking_slots( WP_REST_Request $req ) {
    $date = (string) $req->get_param( 'date' );
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'bad_date' ), 400 );
    }
    if ( ! ml_booking_is_date_available( $date ) ) {
        return new WP_REST_Response( array(
            'ok'     => true,
            'date'   => $date,
            'times'  => array(),
            'closed' => true,
        ), 200 );
    }
    return new WP_REST_Response( array(
        'ok'     => true,
        'date'   => $date,
        'times'  => ml_booking_get_times_with_availability( $date ),
        'closed' => false,
    ), 200 );
}
