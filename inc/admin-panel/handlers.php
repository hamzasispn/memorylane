<?php
/**
 * Memory Lane — slim admin panel server actions (admin-post handlers).
 * All entries gate on ML_CAP_MANAGE / manage_options.
 */
defined( 'ABSPATH' ) || exit;

function ml_ap_assert_admin() {
    if ( ! is_user_logged_in() ) { wp_safe_redirect( home_url( '/login' ) ); exit; }
    if ( ! current_user_can( ML_CAP_MANAGE ) && ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Forbidden.', 'memorylane' ), 403 );
    }
}

function ml_ap_back( $section, $extra = array() ) {
    $url = home_url( '/admin/' . ltrim( $section, '/' ) );
    foreach ( $extra as $k => $v ) {
        $url = add_query_arg( $k, $v, $url );
    }
    wp_safe_redirect( $url ); exit;
}

/**
 * Booking actions: confirm, cancel, complete.
 */
add_action( 'admin_post_ml_ap_booking_action', function () {
    ml_ap_assert_admin();
    check_admin_referer( 'ml_ap_booking_action' );

    global $wpdb;
    $tbl    = ml_table( 'bookings' );
    $id     = (int) ( $_POST['id'] ?? 0 );
    $action = sanitize_key( wp_unslash( $_POST['op'] ?? '' ) );

    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE id=%d", $id ) );
    if ( ! $row ) ml_ap_back( 'bookings', array( 'msg' => 'not_found' ) );

    $now = current_time( 'mysql', true );
    switch ( $action ) {
        case 'confirm':
            $wpdb->update( $tbl, array( 'status' => 'confirmed', 'updated_at' => $now ), array( 'id' => $id ) );
            $user = get_user_by( 'id', $row->user_id );
            if ( $user ) ml_mail_send( $user->user_email, 'booking_confirmed', array( 'user' => $user, 'booking' => $row ), $user->ID );
            ml_ap_back( 'bookings', array( 'msg' => 'confirmed' ) );
        case 'complete':
            $wpdb->update( $tbl, array( 'status' => 'completed', 'completed_at' => $now, 'updated_at' => $now ), array( 'id' => $id ) );
            ml_ap_back( 'bookings', array( 'msg' => 'completed' ) );
        case 'cancel':
            $wpdb->update( $tbl, array( 'status' => 'cancelled', 'cancelled_at' => $now, 'updated_at' => $now ), array( 'id' => $id ) );
            ml_ap_back( 'bookings', array( 'msg' => 'cancelled' ) );
    }
    ml_ap_back( 'bookings' );
} );

/**
 * Tour: create / update / delete.
 */
add_action( 'admin_post_ml_ap_tour_save', function () {
    ml_ap_assert_admin();
    check_admin_referer( 'ml_ap_tour_save' );

    $id       = (int) ( $_POST['id'] ?? 0 );
    $title    = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
    $user_id  = (int) ( $_POST['user_id'] ?? 0 );
    $embed    = (string) ( $_POST['embed_code'] ?? '' ); // intentionally not stripped — iframes need angle brackets
    $address  = sanitize_text_field( wp_unslash( $_POST['address'] ?? '' ) );
    $status   = sanitize_key( wp_unslash( $_POST['status'] ?? ML_TOUR_STATUS_ACTIVE ) );

    if ( ! in_array( $status, array( ML_TOUR_STATUS_ACTIVE, ML_TOUR_STATUS_ARCHIVED ), true ) ) {
        $status = ML_TOUR_STATUS_ACTIVE;
    }

    if ( $id ) {
        wp_update_post( array( 'ID' => $id, 'post_title' => $title ?: '(untitled tour)' ) );
    } else {
        $id = wp_insert_post( array(
            'post_type'   => ML_CPT_TOUR,
            'post_status' => 'publish',
            'post_title'  => $title ?: '(untitled tour)',
        ) );
    }
    if ( ! $id || is_wp_error( $id ) ) ml_ap_back( 'tours', array( 'msg' => 'save_failed' ) );

    update_post_meta( $id, ML_META_TOUR_USER,    $user_id );
    update_post_meta( $id, ML_META_TOUR_EMBED,   $embed );
    update_post_meta( $id, ML_META_TOUR_ADDRESS, $address );
    update_post_meta( $id, ML_META_TOUR_STATUS,  $status );

    ml_ap_back( 'tours', array( 'msg' => 'saved' ) );
} );

add_action( 'admin_post_ml_ap_tour_delete', function () {
    ml_ap_assert_admin();
    check_admin_referer( 'ml_ap_tour_delete' );
    $id = (int) ( $_POST['id'] ?? 0 );
    if ( $id && get_post_type( $id ) === ML_CPT_TOUR ) {
        wp_delete_post( $id, true );
    }
    ml_ap_back( 'tours', array( 'msg' => 'deleted' ) );
} );

/**
 * Settings: working hours, booking window/rules, notifications, embed allowlist.
 */
add_action( 'admin_post_ml_ap_settings_save', function () {
    ml_ap_assert_admin();
    check_admin_referer( 'ml_ap_settings_save' );

    // Working hours.
    $days  = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
    $hours = array();
    foreach ( $days as $d ) {
        $hours[ $d ] = array(
            'enabled' => ! empty( $_POST['hours'][ $d ]['enabled'] ),
            'start'   => sanitize_text_field( $_POST['hours'][ $d ]['start'] ?? '09:00' ),
            'end'     => sanitize_text_field( $_POST['hours'][ $d ]['end']   ?? '17:00' ),
        );
    }
    update_option( ML_BOOKING_OPT_WORKING_HOURS, wp_json_encode( $hours ), false );
    update_option( ML_BOOKING_OPT_SLOT_LENGTH,   max( 15, (int) ( $_POST['slot_length'] ?? 60 ) ), false );
    update_option( ML_BOOKING_OPT_WINDOW_DAYS,   max( 1,  (int) ( $_POST['window_days'] ?? 60 ) ), false );

    // Blocked dates.
    $blocked_raw = (string) ( $_POST['blocked_dates'] ?? '' );
    $blocked     = array();
    foreach ( preg_split( '/[\r\n,]+/', $blocked_raw ) as $line ) {
        $line = trim( $line );
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $line ) ) $blocked[] = $line;
    }
    update_option( ML_BOOKING_OPT_BLOCKED_DATES, wp_json_encode( array_values( array_unique( $blocked ) ) ), false );

    // Booking rules + notifications + embed allowlist.
    update_option( ML_OPT_BOOKING_CANCEL_HOURS, max( 0, (int) ( $_POST['cancel_hours']     ?? 24 ) ), false );
    update_option( ML_OPT_BOOKING_RESCHED_HOURS, max( 0, (int) ( $_POST['reschedule_hours'] ?? 24 ) ), false );
    update_option( ML_OPT_ADMIN_RECIPIENTS,   sanitize_text_field( wp_unslash( $_POST['admin_recipients']  ?? '' ) ), false );
    update_option( ML_OPT_EMAIL_FROM_NAME,    sanitize_text_field( wp_unslash( $_POST['email_from_name']   ?? '' ) ), false );
    update_option( ML_OPT_EMAIL_FROM_ADDRESS, sanitize_email( wp_unslash( $_POST['email_from_address']     ?? '' ) ), false );
    update_option( ML_OPT_EMBED_DOMAIN_ALLOW, wp_kses_post( wp_unslash( $_POST['embed_allowlist'] ?? '' ) ), false );

    ml_ap_back( 'settings', array( 'msg' => 'saved' ) );
} );
