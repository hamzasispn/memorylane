<?php
/**
 * Memory Lane — push a booking to Teamleader as a Contact + Deal.
 *
 * Robust by design: if Teamleader is not connected or the API call fails, the
 * booking is queued and retried later (cron + right after a successful connect),
 * so no lead is ever lost.
 */
defined( 'ABSPATH' ) || exit;

const ML_TL_OPT_QUEUE    = 'ml_tl_queue';
const ML_TL_OPT_PHASE_ID = 'ml_tl_phase_id';
const ML_TL_MAX_ATTEMPTS = 8;

/**
 * Entry point called from the booking flow. Tries to sync immediately; on any
 * problem the lead is queued for retry.
 *
 * @param WP_User $user
 * @param array   $data  Booking + address fields (see ml_boek_provision_booking).
 */
function ml_tl_push_booking( $user, array $data ) {
    $payload = array( 'user_id' => (int) $user->ID, 'data' => $data );

    if ( ! ml_tl_is_connected() ) {
        ml_tl_enqueue( $payload );
        return;
    }
    try {
        ml_tl_sync_now( $user, $data );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] Teamleader sync failed, queued for retry: ' . $e->getMessage() );
        ml_tl_enqueue( $payload );
    }
}

/**
 * Do the actual Teamleader work: find/create contact, then create the deal.
 * Throws on any API failure.
 */
function ml_tl_sync_now( $user, array $data ) {
    $name  = trim( (string) ( $data['name'] ?? $user->display_name ) );
    $parts = preg_split( '/\s+/', $name, 2 );
    $first = $parts[0] ?? $name;
    $last  = $parts[1] ?? '';

    // 1. Find contact by email, else create.
    $contact_id = '';
    try {
        $found = ml_tl_request( 'contacts.list', array(
            'filter' => array( 'email' => array( 'type' => 'primary', 'email' => $user->user_email ) ),
            'page'   => array( 'size' => 1 ),
        ) );
        if ( ! empty( $found[0]['id'] ) ) {
            $contact_id = $found[0]['id'];
        }
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] Teamleader contacts.list failed: ' . $e->getMessage() );
    }

    if ( ! $contact_id ) {
        $contact_body = array(
            'first_name' => $first ?: $name,
            'last_name'  => $last,
            'emails'     => array( array( 'type' => 'primary', 'email' => $user->user_email ) ),
        );
        if ( ! empty( $data['phone'] ) ) {
            $contact_body['telephones'] = array( array( 'type' => 'phone', 'number' => $data['phone'] ) );
        }
        if ( ! empty( $data['street'] ) || ! empty( $data['city'] ) ) {
            $contact_body['addresses'] = array( array(
                'type'    => 'primary',
                'address' => array(
                    'line_1'      => (string) ( $data['street'] ?? '' ),
                    'postal_code' => (string) ( $data['postcode'] ?? '' ),
                    'city'        => (string) ( $data['city'] ?? '' ),
                    'country_id'  => (string) ( $data['country'] ?? '' ), // ISO-2, per API
                ),
            ) );
        }
        $created    = ml_tl_request( 'contacts.add', $contact_body );
        $contact_id = $created['id'] ?? '';
    }

    if ( ! $contact_id ) {
        throw new \RuntimeException( 'Could not resolve a Teamleader contact id.' );
    }

    // 2. Create the deal (lead) linked to the contact. phase_id is required.
    $scheduled = (string) ( $data['scheduled_for'] ?? '' );
    $summary_lines = array_filter( array(
        $data['address'] ?? '',
        ! empty( $data['phone'] ) ? 'Tel: ' . $data['phone'] : '',
        $scheduled ? 'Gewenst moment: ' . $scheduled . ' (UTC)' : '',
        ! empty( $data['notes'] ) ? 'Opmerkingen: ' . $data['notes'] : '',
    ) );

    $deal_body = array(
        'lead'    => array( 'customer' => array( 'type' => 'contact', 'id' => $contact_id ) ),
        'title'   => 'Opname – ' . $name . ( $scheduled ? ' – ' . substr( $scheduled, 0, 10 ) : '' ),
        'summary' => implode( "\n", $summary_lines ),
    );
    $phase_id = ml_tl_default_phase_id();
    if ( $phase_id ) {
        $deal_body['phase_id'] = $phase_id;
    }

    $deal = ml_tl_request( 'deals.create', $deal_body );

    // Record the Teamleader ids for reference.
    update_user_meta( $user->ID, '_ml_tl_contact_id', $contact_id );
    if ( ! empty( $deal['id'] ) ) {
        update_user_meta( $user->ID, '_ml_tl_last_deal_id', $deal['id'] );
    }
}

/* ───────────────────────── Retry queue ───────────────────────── */

function ml_tl_queue() {
    $q = get_option( ML_TL_OPT_QUEUE, array() );
    return is_array( $q ) ? $q : array();
}

function ml_tl_enqueue( array $payload ) {
    $q = ml_tl_queue();
    $payload['attempts']   = 0;
    $payload['queued_at']  = time();
    $q[] = $payload;
    if ( count( $q ) > 500 ) { $q = array_slice( $q, -500 ); } // safety cap
    update_option( ML_TL_OPT_QUEUE, $q, false );
}

function ml_tl_queue_count() {
    return count( ml_tl_queue() );
}

/**
 * Drain the retry queue. Safe to call repeatedly; no-op when not connected.
 */
function ml_tl_process_queue() {
    if ( ! ml_tl_is_connected() ) return;

    $q = ml_tl_queue();
    if ( empty( $q ) ) return;

    $remaining = array();
    foreach ( $q as $item ) {
        $user = get_user_by( 'id', (int) ( $item['user_id'] ?? 0 ) );
        if ( ! $user ) continue; // user gone — drop
        try {
            ml_tl_sync_now( $user, (array) ( $item['data'] ?? array() ) );
            // success → not re-queued
        } catch ( \Throwable $e ) {
            $item['attempts'] = (int) ( $item['attempts'] ?? 0 ) + 1;
            if ( $item['attempts'] < ML_TL_MAX_ATTEMPTS ) {
                $remaining[] = $item;
            } else {
                error_log( '[memorylane] Teamleader queue: dropping booking after max attempts: ' . $e->getMessage() );
            }
        }
    }
    update_option( ML_TL_OPT_QUEUE, $remaining, false );
}

add_action( 'ml_cron_tl_retry', 'ml_tl_process_queue' );

/* ───────────────────────── Deal phase ───────────────────────── */

/**
 * The deal phase new deals are created in. Stored as an option; if unset, the
 * first available phase is fetched + cached.
 */
function ml_tl_default_phase_id() {
    $pid = (string) get_option( ML_TL_OPT_PHASE_ID, '' );
    if ( $pid ) return $pid;
    if ( ! ml_tl_is_connected() ) return '';
    try {
        $phases = ml_tl_request( 'dealPhases.list' );
        if ( ! empty( $phases[0]['id'] ) ) {
            update_option( ML_TL_OPT_PHASE_ID, $phases[0]['id'], false );
            return (string) $phases[0]['id'];
        }
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] Teamleader dealPhases.list failed: ' . $e->getMessage() );
    }
    return '';
}

/**
 * All deal phases (for the settings dropdown). Returns [] on failure.
 */
function ml_tl_list_phases() {
    if ( ! ml_tl_is_connected() ) return array();
    try {
        $phases = ml_tl_request( 'dealPhases.list' );
        return is_array( $phases ) ? $phases : array();
    } catch ( \Throwable $e ) {
        return array();
    }
}

/**
 * Verify the connection by calling users.me. Returns the account/user data or
 * throws.
 */
function ml_tl_test_connection() {
    return ml_tl_request( 'users.me' );
}
