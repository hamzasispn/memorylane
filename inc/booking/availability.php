<?php
/**
 * Memory Lane — Booking availability computations.
 * Generates virtual time slots from working-hours rules, overlays existing
 * booked slot rows, returns availability per time. Pure functions; no
 * caching here — callers cache if needed.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Compute the list of HH:MM start times for the given YYYY-MM-DD date,
 * purely from the working-hours rules (no booking overlay).
 */
function ml_booking_compute_times_for_date( $date ) {
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) return array();

    $weekday = ml_booking_weekday_key_for_date( $date );
    $hours   = ml_booking_working_hours();
    if ( empty( $hours[ $weekday ] ) || empty( $hours[ $weekday ]['enabled'] ) ) {
        return array();
    }

    $start_str = $hours[ $weekday ]['start'];
    $end_str   = $hours[ $weekday ]['end'];
    $start_ts  = strtotime( "{$date} {$start_str}:00" );
    $end_ts    = strtotime( "{$date} {$end_str}:00" );
    if ( ! $start_ts || ! $end_ts || $end_ts <= $start_ts ) return array();

    $len_sec = ml_booking_slot_length_minutes() * 60;
    $times   = array();
    for ( $t = $start_ts; $t + $len_sec <= $end_ts + 1; $t += $len_sec ) {
        $times[] = wp_date( 'H:i', $t );
    }
    return $times;
}

/**
 * Is this date in scope (within window, not blocked, working day)?
 */
function ml_booking_is_date_available( $date ) {
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) return false;
    if ( in_array( $date, ml_booking_blocked_dates(), true ) ) return false;

    $today    = wp_date( 'Y-m-d' );
    $max_date = wp_date( 'Y-m-d', strtotime( $today . ' +' . ml_booking_window_days() . ' days' ) );
    if ( $date < $today || $date > $max_date ) return false;

    $weekday = ml_booking_weekday_key_for_date( $date );
    $hours   = ml_booking_working_hours();
    return ! empty( $hours[ $weekday ] ) && ! empty( $hours[ $weekday ]['enabled'] );
}

/**
 * Convert YYYY-MM-DD + HH:MM (site timezone) → UTC datetime string (DB format).
 */
function ml_booking_local_to_utc( $date, $time ) {
    $dt = new DateTime( "{$date} {$time}:00", wp_timezone() );
    $dt->setTimezone( new DateTimeZone( 'UTC' ) );
    return $dt->format( 'Y-m-d H:i:s' );
}

/**
 * Get availability for one date: [{ time: 'HH:MM', available: bool }, …]
 * Past times for today are unavailable. Times whose slot row is at
 * capacity (or status != 'open') are unavailable.
 */
function ml_booking_get_times_with_availability( $date ) {
    if ( ! ml_booking_is_date_available( $date ) ) return array();

    $times = ml_booking_compute_times_for_date( $date );
    if ( empty( $times ) ) return array();

    $today_local = wp_date( 'Y-m-d' );

    $utc_to_time = array();
    foreach ( $times as $time ) {
        $utc_to_time[ ml_booking_local_to_utc( $date, $time ) ] = $time;
    }
    $utc_list = array_keys( $utc_to_time );

    global $wpdb;
    $tbl = ml_table( 'availability_slots' );

    $booked_map = array();
    if ( ! empty( $utc_list ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $utc_list ), '%s' ) );
        $existing = $wpdb->get_results( $wpdb->prepare(
            "SELECT slot_start_datetime, booked_count, capacity, status
               FROM {$tbl}
              WHERE slot_start_datetime IN ($placeholders)",
            ...$utc_list
        ) );
        foreach ( $existing as $row ) {
            $booked_map[ $row->slot_start_datetime ] = array(
                'booked' => (int) $row->booked_count,
                'cap'    => (int) $row->capacity,
                'status' => $row->status,
            );
        }
    }

    $now_ts = time();
    $result = array();
    foreach ( $times as $time ) {
        $utc = ml_booking_local_to_utc( $date, $time );
        $available = true;
        if ( isset( $booked_map[ $utc ] ) ) {
            $available = $booked_map[ $utc ]['status'] === 'open'
                       && $booked_map[ $utc ]['booked'] < $booked_map[ $utc ]['cap'];
        }
        if ( $date === $today_local && strtotime( "{$date} {$time}:00" ) <= $now_ts ) {
            $available = false;
        }
        $result[] = array( 'time' => $time, 'available' => $available );
    }
    return $result;
}

/**
 * Dates within the booking window with their availability flag. The flag
 * here only considers blocked-date + working-day rules — per-time checks
 * happen when the user clicks a date.
 */
function ml_booking_get_available_dates() {
    $today = wp_date( 'Y-m-d' );
    $days  = ml_booking_window_days();
    $out   = array();
    for ( $i = 0; $i <= $days; $i++ ) {
        $d = wp_date( 'Y-m-d', strtotime( $today . " +{$i} days" ) );
        $out[] = array(
            'date'      => $d,
            'available' => ml_booking_is_date_available( $d ),
            'is_today'  => $d === $today,
            'weekday'   => ml_booking_weekday_key_for_date( $d ),
        );
    }
    return $out;
}
