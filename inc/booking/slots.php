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
