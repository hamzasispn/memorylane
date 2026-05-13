<?php
/**
 * Memory Lane — Booking working-hours config getters.
 * Reads from wp_options with hardcoded fallbacks until the admin form
 * (V2-3) ships. Values are intentionally narrow — clients with two
 * different open windows per day are unsupported by design.
 */
defined( 'ABSPATH' ) || exit;

const ML_BOOKING_OPT_WORKING_HOURS  = 'ml_booking_working_hours_json';
const ML_BOOKING_OPT_SLOT_LENGTH    = 'ml_booking_slot_length_minutes';
const ML_BOOKING_OPT_CAPACITY       = 'ml_booking_capacity_per_slot';
const ML_BOOKING_OPT_WINDOW_DAYS    = 'ml_booking_window_days';
const ML_BOOKING_OPT_BLOCKED_DATES  = 'ml_booking_blocked_dates_json';

function ml_booking_working_hours() {
    $json = (string) get_option( ML_BOOKING_OPT_WORKING_HOURS, '' );
    if ( $json ) {
        $h = json_decode( $json, true );
        if ( is_array( $h ) ) return $h;
    }
    return array(
        'mon' => array( 'enabled' => true,  'start' => '09:00', 'end' => '17:00' ),
        'tue' => array( 'enabled' => true,  'start' => '09:00', 'end' => '17:00' ),
        'wed' => array( 'enabled' => true,  'start' => '09:00', 'end' => '17:00' ),
        'thu' => array( 'enabled' => true,  'start' => '09:00', 'end' => '17:00' ),
        'fri' => array( 'enabled' => true,  'start' => '09:00', 'end' => '17:00' ),
        'sat' => array( 'enabled' => false, 'start' => '09:00', 'end' => '17:00' ),
        'sun' => array( 'enabled' => false, 'start' => '09:00', 'end' => '17:00' ),
    );
}

function ml_booking_slot_length_minutes() {
    return max( 15, (int) get_option( ML_BOOKING_OPT_SLOT_LENGTH, 60 ) );
}

function ml_booking_capacity_per_slot() {
    return max( 1, (int) get_option( ML_BOOKING_OPT_CAPACITY, 1 ) );
}

function ml_booking_window_days() {
    return max( 1, (int) get_option( ML_BOOKING_OPT_WINDOW_DAYS, 60 ) );
}

function ml_booking_blocked_dates() {
    $json = (string) get_option( ML_BOOKING_OPT_BLOCKED_DATES, '' );
    if ( ! $json ) return array();
    $list = json_decode( $json, true );
    return is_array( $list ) ? array_values( array_filter( $list, 'is_string' ) ) : array();
}

/**
 * Map a YYYY-MM-DD (site timezone) to a weekday key (mon..sun).
 * Noon timestamp avoids DST transition edge cases.
 */
function ml_booking_weekday_key_for_date( $date ) {
    $ts = strtotime( $date . ' 12:00:00' );
    return strtolower( substr( wp_date( 'D', $ts ), 0, 3 ) );
}
