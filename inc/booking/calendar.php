<?php
/**
 * Memory Lane — "Add to calendar" links for a booking (Google / Outlook / iCal).
 */
defined( 'ABSPATH' ) || exit;

function ml_ics_escape( $s ) {
    return str_replace( array( "\\", "\n", ",", ";" ), array( "\\\\", "\\n", "\\,", "\\;" ), (string) $s );
}

/**
 * Build calendar links from a UTC datetime string ("Y-m-d H:i:s").
 *
 * @return array{google:string,outlook:string,ical:string}|array{} Empty on bad date.
 */
function ml_calendar_links( $title, $start_utc, $duration_min = 60, $location = '', $details = '' ) {
    $start = strtotime( $start_utc . ' UTC' );
    if ( ! $start ) return array();
    $end = $start + ( max( 15, (int) $duration_min ) * 60 );

    $compact = function ( $ts ) { return gmdate( 'Ymd\THis\Z', $ts ); };

    $google = 'https://calendar.google.com/calendar/render?' . http_build_query( array(
        'action'   => 'TEMPLATE',
        'text'     => $title,
        'dates'    => $compact( $start ) . '/' . $compact( $end ),
        'details'  => $details,
        'location' => $location,
    ) );

    $outlook = 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query( array(
        'path'     => '/calendar/action/compose',
        'rru'      => 'addevent',
        'subject'  => $title,
        'startdt'  => gmdate( 'Y-m-d\TH:i:s\Z', $start ),
        'enddt'    => gmdate( 'Y-m-d\TH:i:s\Z', $end ),
        'body'     => $details,
        'location' => $location,
    ) );

    $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Memory Lane//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\n"
        . 'UID:' . md5( $title . $start ) . "@memorylane\r\n"
        . 'DTSTAMP:' . $compact( time() ) . "\r\n"
        . 'DTSTART:' . $compact( $start ) . "\r\n"
        . 'DTEND:' . $compact( $end ) . "\r\n"
        . 'SUMMARY:' . ml_ics_escape( $title ) . "\r\n"
        . 'LOCATION:' . ml_ics_escape( $location ) . "\r\n"
        . 'DESCRIPTION:' . ml_ics_escape( $details ) . "\r\n"
        . "END:VEVENT\r\nEND:VCALENDAR";
    $ical = 'data:text/calendar;charset=utf-8,' . rawurlencode( $ics );

    return array( 'google' => $google, 'outlook' => $outlook, 'ical' => $ical );
}

/**
 * The customer's address as a single line, for the calendar event location.
 */
function ml_user_address_line( $user_id ) {
    $parts = array_filter( array(
        get_user_meta( $user_id, '_ml_address_line1', true ),
        trim( get_user_meta( $user_id, '_ml_address_postal', true ) . ' ' . get_user_meta( $user_id, '_ml_address_city', true ) ),
        get_user_meta( $user_id, '_ml_address_country', true ),
    ) );
    return implode( ', ', array_map( 'trim', $parts ) );
}
