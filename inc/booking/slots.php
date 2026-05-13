<?php
/**
 * Memory Lane — booking slot helpers.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Open slots within the next $days days that still have capacity.
 */
function ml_get_open_slots( $days = 60 ) {
    global $wpdb;
    $tbl = ml_table( 'availability_slots' );
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$tbl}
         WHERE status='open'
           AND booked_count < capacity
           AND slot_start_datetime BETWEEN UTC_TIMESTAMP() AND DATE_ADD(UTC_TIMESTAMP(), INTERVAL %d DAY)
         ORDER BY slot_start_datetime ASC
         LIMIT 200",
        (int) $days
    ) );
}

function ml_get_slot( $id ) {
    global $wpdb;
    $tbl = ml_table( 'availability_slots' );
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE id=%d", (int) $id ) );
}

function ml_increment_slot_booked( $slot_id ) {
    global $wpdb;
    $tbl = ml_table( 'availability_slots' );
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$tbl} SET booked_count = booked_count + 1 WHERE id=%d",
        (int) $slot_id
    ) );
}

function ml_decrement_slot_booked( $slot_id ) {
    global $wpdb;
    $tbl = ml_table( 'availability_slots' );
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$tbl} SET booked_count = GREATEST(0, booked_count - 1) WHERE id=%d",
        (int) $slot_id
    ) );
}

/**
 * Find an existing availability_slots row matching the local date+time,
 * or create one using the working-hours rules. Returns the row object,
 * or null if the date+time is not valid per the rules.
 */
function ml_booking_find_or_create_slot( $date, $time ) {
    if ( ! ml_booking_is_date_available( $date ) ) return null;

    $valid_times = ml_booking_compute_times_for_date( $date );
    if ( ! in_array( $time, $valid_times, true ) ) return null;

    $utc_start = ml_booking_local_to_utc( $date, $time );

    global $wpdb;
    $tbl = ml_table( 'availability_slots' );

    $existing = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$tbl} WHERE slot_start_datetime = %s LIMIT 1",
        $utc_start
    ) );
    if ( $existing ) return $existing;

    $len_min = ml_booking_slot_length_minutes();
    $utc_end = ( new DateTime( $utc_start, new DateTimeZone( 'UTC' ) ) )
                  ->modify( "+{$len_min} minutes" )->format( 'Y-m-d H:i:s' );

    $wpdb->insert( $tbl, array(
        'slot_start_datetime' => $utc_start,
        'slot_end_datetime'   => $utc_end,
        'capacity'            => ml_booking_capacity_per_slot(),
        'booked_count'        => 0,
        'status'              => 'open',
        'created_at'          => current_time( 'mysql', true ),
    ) );
    if ( ! $wpdb->insert_id ) return null;

    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$tbl} WHERE id = %d",
        $wpdb->insert_id
    ) );
}
