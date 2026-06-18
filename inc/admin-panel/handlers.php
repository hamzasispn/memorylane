<?php
/**
 * Memory Lane — admin panel server actions (admin-post handlers).
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
            if ( $row->slot_id ) ml_decrement_slot_booked( $row->slot_id );
            ml_ap_back( 'bookings', array( 'msg' => 'cancelled' ) );
    }
    ml_ap_back( 'bookings' );
} );

/**
 * Working-hours form (V2-3 — lives at /admin/slots).
 */
add_action( 'admin_post_ml_ap_slots_save', function () {
    ml_ap_assert_admin();
    check_admin_referer( 'ml_ap_slots_save' );

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
    update_option( ML_BOOKING_OPT_CAPACITY,      max( 1,  (int) ( $_POST['capacity']    ?? 1  ) ), false );
    update_option( ML_BOOKING_OPT_WINDOW_DAYS,   max( 1,  (int) ( $_POST['window_days'] ?? 60 ) ), false );

    $blocked_raw = (string) ( $_POST['blocked_dates'] ?? '' );
    $blocked     = array();
    foreach ( preg_split( '/[\r\n,]+/', $blocked_raw ) as $line ) {
        $line = trim( $line );
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $line ) ) $blocked[] = $line;
    }
    update_option( ML_BOOKING_OPT_BLOCKED_DATES, wp_json_encode( array_values( array_unique( $blocked ) ) ), false );

    ml_ap_back( 'slots', array( 'msg' => 'saved' ) );
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

    if ( ! in_array( $status, array( ML_TOUR_STATUS_ACTIVE, ML_TOUR_STATUS_ARCHIVED, ML_TOUR_STATUS_PENDING_ARCHIVE, ML_TOUR_STATUS_PENDING_REACTIVATION ), true ) ) {
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
 * Customer access: approve / deactivate.
 */
add_action( 'admin_post_ml_ap_customer_approve', function () {
    ml_ap_assert_admin();
    check_admin_referer( 'ml_ap_customer_approve' );
    $user_id = (int) ( $_POST['user_id'] ?? 0 );
    $user    = $user_id ? get_user_by( 'id', $user_id ) : null;
    if ( ! $user ) ml_ap_back( 'customers', array( 'msg' => 'not_found' ) );

    $state = get_user_meta( $user_id, ML_META_SETUP_STATE, true );
    if ( $state !== ML_SETUP_STATE_PENDING ) {
        ml_ap_back( 'customers/' . $user_id, array( 'msg' => 'approved' ) );
    }

    if ( function_exists( 'ml_create_subscription_on_approval' ) ) {
        $result = ml_create_subscription_on_approval( $user_id );
        if ( empty( $result['ok'] ) ) {
            error_log( '[memorylane] approve_access failed: ' . ( $result['error'] ?? 'unknown' ) );
            ml_ap_back( 'customers/' . $user_id, array( 'msg' => 'save_failed' ) );
        }
    }

    update_user_meta( $user_id, ML_META_SETUP_STATE,       ML_SETUP_STATE_APPROVED );
    update_user_meta( $user_id, ML_META_SETUP_APPROVED_AT, current_time( 'mysql', true ) );
    update_user_meta( $user_id, ML_META_SETUP_APPROVED_BY, get_current_user_id() );
    wp_cache_delete( 'ml_access_' . $user_id, 'ml' );

    if ( function_exists( 'ml_mail_send' ) ) {
        ml_mail_send( $user->user_email, 'access_approved', array( 'user' => $user ), $user_id );
    }

    ml_ap_back( 'customers/' . $user_id, array( 'msg' => 'approved' ) );
} );

add_action( 'admin_post_ml_ap_customer_deactivate', function () {
    ml_ap_assert_admin();
    check_admin_referer( 'ml_ap_customer_deactivate' );
    $user_id = (int) ( $_POST['user_id'] ?? 0 );
    if ( $user_id ) {
        // Archive all of their active tours.
        $ids = get_posts( array(
            'post_type'   => ML_CPT_TOUR,
            'post_status' => 'any',
            'numberposts' => -1,
            'fields'      => 'ids',
            'meta_query'  => array(
                array( 'key' => ML_META_TOUR_USER,   'value' => $user_id ),
                array( 'key' => ML_META_TOUR_STATUS, 'value' => ML_TOUR_STATUS_ACTIVE ),
            ),
        ) );
        foreach ( $ids as $tid ) {
            update_post_meta( $tid, ML_META_TOUR_STATUS, ML_TOUR_STATUS_ARCHIVED );
        }
        wp_cache_delete( 'ml_access_' . $user_id, 'ml' );
    }
    ml_ap_back( 'customers/' . $user_id, array( 'msg' => 'deactivated' ) );
} );

/**
 * Settings (Stripe keys, prices, grace days, embed allowlist, admin recipients).
 */
add_action( 'admin_post_ml_ap_settings_save', function () {
    ml_ap_assert_admin();
    check_admin_referer( 'ml_ap_settings_save' );

    // Stripe mode + keys.
    $mode = sanitize_key( wp_unslash( $_POST['stripe_mode'] ?? 'test' ) );
    if ( ! in_array( $mode, array( 'test', 'live' ), true ) ) $mode = 'test';
    update_option( ML_OPT_STRIPE_MODE, $mode, false );

    $keys = array( 'publishable_key', 'secret_key', 'webhook_secret' );
    foreach ( $keys as $k ) {
        $val = sanitize_text_field( wp_unslash( $_POST[ "stripe_{$mode}_{$k}" ] ?? '' ) );
        if ( $val ) update_option( "ml_stripe_{$mode}_{$k}", $val, false );
    }

    // Plan amounts (cents) — saved + optional sync.
    $plan_fields = array(
        'plan_name'                 => sanitize_text_field( wp_unslash( $_POST['plan_name'] ?? '' ) ),
        'plan_description'          => sanitize_textarea_field( wp_unslash( $_POST['plan_description'] ?? '' ) ),
        'plan_currency'             => strtolower( sanitize_text_field( wp_unslash( $_POST['plan_currency'] ?? 'eur' ) ) ),
        'plan_year_one_amount'      => ml_to_minor_units( wp_unslash( $_POST['plan_year_one_amount'] ?? '' ) ),
        'plan_monthly_amount'       => ml_to_minor_units( wp_unslash( $_POST['plan_monthly_amount'] ?? '' ) ),
        'plan_annual_amount'        => ml_to_minor_units( wp_unslash( $_POST['plan_annual_amount'] ?? '' ) ),
        'plan_reactivation_amount'  => ml_to_minor_units( wp_unslash( $_POST['plan_reactivation_amount'] ?? '' ) ),
    );
    ml_plan_save_raw( $plan_fields );

    // Grace days + admin emails.
    update_option( ML_OPT_PAST_DUE_GRACE_DAYS,  max( 0, (int) ( $_POST['grace_days']        ?? 7 ) ), false );
    update_option( ML_OPT_ADMIN_RECIPIENTS,     sanitize_text_field( wp_unslash( $_POST['admin_recipients']   ?? '' ) ), false );
    update_option( ML_OPT_EMBED_DOMAIN_ALLOW,   wp_kses_post( wp_unslash( $_POST['embed_allowlist'] ?? '' ) ), false );
    update_option( ML_OPT_EMAIL_FROM_NAME,      sanitize_text_field( wp_unslash( $_POST['email_from_name']    ?? '' ) ), false );
    update_option( ML_OPT_EMAIL_FROM_ADDRESS,   sanitize_email( wp_unslash( $_POST['email_from_address']      ?? '' ) ), false );
    update_option( ML_OPT_BOOKING_CANCEL_HOURS, max( 0, (int) ( $_POST['cancel_hours']      ?? 24 ) ), false );
    update_option( ML_OPT_BOOKING_RESCHED_HOURS,max( 0, (int) ( $_POST['reschedule_hours']  ?? 24 ) ), false );
    update_option( ML_OPT_BOOKING_REQUIRE_PAYMENT, empty( $_POST['booking_require_payment'] ) ? 0 : 1, false );

    if ( ! empty( $_POST['sync_with_stripe'] ) ) {
        $res = ml_plan_sync_to_stripe();
        if ( ! empty( $res['ok'] ) ) {
            ml_ap_back( 'settings', array( 'msg' => 'synced' ) );
        }
        ml_ap_back( 'settings', array( 'msg' => 'sync_failed' ) );
    }
    ml_ap_back( 'settings', array( 'msg' => 'saved' ) );
} );

/**
 * Webhook log retry.
 */
add_action( 'admin_post_ml_ap_webhook_retry', function () {
    ml_ap_assert_admin();
    check_admin_referer( 'ml_ap_webhook_retry' );
    $id = (int) ( $_POST['id'] ?? 0 );
    if ( $id && function_exists( 'ml_retry_webhook_event' ) ) {
        ml_retry_webhook_event( $id );
    }
    ml_ap_back( 'logs', array( 'msg' => 'retried', 'tab' => 'webhooks' ) );
} );

add_action( 'admin_post_ml_ap_email_retry', function () {
    ml_ap_assert_admin();
    check_admin_referer( 'ml_ap_email_retry' );
    $id = (int) ( $_POST['id'] ?? 0 );
    if ( $id && function_exists( 'ml_retry_email' ) ) {
        ml_retry_email( $id );
    }
    ml_ap_back( 'logs', array( 'msg' => 'retried', 'tab' => 'emails' ) );
} );
