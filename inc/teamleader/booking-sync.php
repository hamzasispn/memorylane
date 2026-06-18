<?php
/**
 * Memory Lane — push a booking to Teamleader as a Contact + Deal.
 * Inert (logged no-op) until the integration is connected.
 */
defined( 'ABSPATH' ) || exit;

/**
 * @param WP_User $user
 * @param array   $data  Booking + address fields (see ml_boek_provision_booking).
 */
function ml_tl_push_booking( $user, array $data ) {
    if ( ! ml_tl_is_connected() ) {
        error_log( '[memorylane] Teamleader not connected — booking ' . ( $data['booking_id'] ?? '?' ) . ' not pushed.' );
        return;
    }

    $name  = trim( (string) ( $data['name'] ?? $user->display_name ) );
    $parts = preg_split( '/\s+/', $name, 2 );
    $first = $parts[0] ?? $name;
    $last  = $parts[1] ?? '';

    // 1. Find or create the contact (by email).
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
        // Fall through to create.
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
                    'country'     => (string) ( $data['country'] ?? '' ),
                ),
            ) );
        }
        $created = ml_tl_request( 'contacts.add', $contact_body );
        $contact_id = $created['id'] ?? '';
    }

    if ( ! $contact_id ) {
        throw new \RuntimeException( 'Could not resolve a Teamleader contact id.' );
    }

    // 2. Create the deal (lead) linked to the contact.
    $scheduled = (string) ( $data['scheduled_for'] ?? '' );
    $summary_lines = array_filter( array(
        $data['address']  ?? '',
        ! empty( $data['phone'] ) ? 'Tel: ' . $data['phone'] : '',
        $scheduled ? 'Gewenst moment: ' . $scheduled . ' (UTC)' : '',
        ! empty( $data['notes'] ) ? 'Opmerkingen: ' . $data['notes'] : '',
    ) );

    $deal = ml_tl_request( 'deals.create', array(
        'lead'    => array( 'customer' => array( 'type' => 'contact', 'id' => $contact_id ) ),
        'title'   => 'Opname – ' . $name . ( $scheduled ? ' – ' . substr( $scheduled, 0, 10 ) : '' ),
        'summary' => implode( "\n", $summary_lines ),
    ) );

    // Record the Teamleader ids for reference.
    update_user_meta( $user->ID, '_ml_tl_contact_id', $contact_id );
    if ( ! empty( $deal['id'] ) ) {
        update_user_meta( $user->ID, '_ml_tl_last_deal_id', $deal['id'] );
    }
}
