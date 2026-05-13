<?php
/**
 * Memory Lane — booking row helpers + customer form handlers.
 */
defined( 'ABSPATH' ) || exit;

function ml_get_user_bookings( $user_id ) {
    global $wpdb;
    $tbl = ml_table( 'bookings' );
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$tbl} WHERE user_id=%d ORDER BY scheduled_for ASC LIMIT 10",
        (int) $user_id
    ) );
}

function ml_get_user_next_booking( $user_id ) {
    global $wpdb;
    $tbl = ml_table( 'bookings' );
    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$tbl}
         WHERE user_id=%d AND status IN ('requested','confirmed') AND scheduled_for > UTC_TIMESTAMP()
         ORDER BY scheduled_for ASC LIMIT 1",
        (int) $user_id
    ) );
}

/**
 * Handle customer booking request.
 */
add_action( 'admin_post_ml_booking_request', function () {
    if ( ! is_user_logged_in() ) { wp_safe_redirect( home_url( '/login' ) ); exit; }
    check_admin_referer( 'ml_booking_request' );

    $user = wp_get_current_user();
    if ( ! ml_user_has_access( $user->ID ) ) {
        ml_flash_set( 'error', ml_t( 'error.access_denied' ) );
        wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
    }

    $slot_id = (int) ( $_POST['slot_id'] ?? 0 );
    $notes   = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

    $slot = ml_get_slot( $slot_id );
    if ( ! $slot || $slot->status !== 'open' || $slot->booked_count >= $slot->capacity ) {
        ml_flash_set( 'error', __( 'This slot is no longer available.', 'memorylane' ) );
        wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
    }
    if ( strtotime( $slot->slot_start_datetime . ' UTC' ) <= time() ) {
        ml_flash_set( 'error', __( 'Slot is in the past.', 'memorylane' ) );
        wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
    }

    global $wpdb;
    $now = current_time( 'mysql', true );
    $wpdb->insert( ml_table( 'bookings' ), array(
        'user_id'        => $user->ID,
        'slot_id'        => $slot_id,
        'service_type'   => 'initial_scan',
        'status'         => 'requested',
        'customer_notes' => $notes,
        'scheduled_for'  => $slot->slot_start_datetime,
        'created_at'     => $now,
        'updated_at'     => $now,
    ) );
    ml_increment_slot_booked( $slot_id );

    ml_mail_send( $user->user_email, 'booking_requested', array(
        'user' => $user,
        'slot' => $slot,
    ), $user->ID );

    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_booking_requested', array(
            'user' => $user,
            'slot' => $slot,
            'notes' => $notes,
        ) );
    }

    ml_flash_set( 'success', __( 'Booking requested. We will confirm shortly by email.', 'memorylane' ) );
    wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
} );

/**
 * Handle customer booking cancellation.
 */
add_action( 'admin_post_ml_booking_cancel', function () {
    if ( ! is_user_logged_in() ) { wp_safe_redirect( home_url( '/login' ) ); exit; }
    check_admin_referer( 'ml_booking_cancel' );

    $id   = (int) ( $_POST['id'] ?? 0 );
    $user = wp_get_current_user();
    global $wpdb;
    $tbl  = ml_table( 'bookings' );
    $row  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE id=%d AND user_id=%d", $id, $user->ID ) );
    if ( ! $row ) { wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit; }

    $hours_before = ( strtotime( $row->scheduled_for . ' UTC' ) - time() ) / HOUR_IN_SECONDS;
    $cutoff       = (int) get_option( ML_OPT_BOOKING_CANCEL_HOURS, 24 );
    if ( $hours_before < $cutoff ) {
        ml_flash_set( 'error', sprintf( __( 'Cancellation must be at least %d hours in advance.', 'memorylane' ), $cutoff ) );
        wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
    }

    $wpdb->update( $tbl, array(
        'status'       => 'cancelled',
        'cancelled_at' => current_time( 'mysql', true ),
        'updated_at'   => current_time( 'mysql', true ),
    ), array( 'id' => $id ) );

    if ( $row->slot_id ) ml_decrement_slot_booked( $row->slot_id );

    ml_flash_set( 'success', __( 'Booking cancelled.', 'memorylane' ) );
    wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
} );
