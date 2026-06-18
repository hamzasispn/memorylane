<?php
/**
 * Memory Lane — public /boek REST endpoint.
 *
 * Visitor books a recording (any working-hour time — no slots, no payment).
 * The booking is stored in WP and pushed to Teamleader as a Contact + Deal.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
    register_rest_route( 'memorylane/v1', '/boek', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'ml_rest_boek',
    ) );
} );

function ml_rest_boek( WP_REST_Request $req ) {
    $nonce = $req->get_header( 'x_wp_nonce' ) ?: $req->get_param( '_wpnonce' );
    if ( ! wp_verify_nonce( (string) $nonce, 'wp_rest' ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'bad_nonce' ), 403 );
    }

    // Rate limit by IP — 8 / 15 min.
    $rl_key = 'ml_boek_rl_' . ml_ip_hash();
    $count  = (int) get_transient( $rl_key );
    if ( $count >= 8 ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'rate_limited' ), 429 );
    }
    set_transient( $rl_key, $count + 1, 15 * MINUTE_IN_SECONDS );

    $date     = sanitize_text_field( (string) $req->get_param( 'date' ) );
    $time     = sanitize_text_field( (string) $req->get_param( 'time' ) );
    $email    = sanitize_email( (string) $req->get_param( 'email' ) );
    $name     = sanitize_text_field( (string) $req->get_param( 'name' ) );
    $phone    = sanitize_text_field( (string) $req->get_param( 'phone' ) );
    $notes    = sanitize_textarea_field( (string) $req->get_param( 'notes' ) );

    // Structured address fields.
    $street   = sanitize_text_field( (string) $req->get_param( 'street' ) );
    $postcode = sanitize_text_field( (string) $req->get_param( 'postcode' ) );
    $city     = sanitize_text_field( (string) $req->get_param( 'city' ) );
    $state    = sanitize_text_field( (string) $req->get_param( 'state' ) );
    $country_code = strtoupper( sanitize_text_field( (string) $req->get_param( 'country' ) ) );
    $countries    = ml_iso_countries();
    $country_name = $countries[ $country_code ] ?? '';

    // Single-line address (display fallback + Teamleader summary).
    $address = trim( implode( ', ', array_filter( array( $street, trim( $postcode . ' ' . $city ), $state, $country_name ) ) ) );

    if ( ! $date || ! $time || ! $email || ! $name || ! $phone || ! $street || ! $postcode || ! $city || ! $country_code ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'missing_fields' ), 400 );
    }
    if ( ! $country_name ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'bad_country' ), 400 );
    }
    if ( ! is_email( $email ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'bad_email' ), 400 );
    }

    $slot = ml_booking_find_or_create_slot( $date, $time );
    if ( ! $slot ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'slot_invalid' ), 400 );
    }
    if ( strtotime( $slot->slot_start_datetime . ' UTC' ) <= time() ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'slot_in_past' ), 409 );
    }

    try {
        ml_boek_provision_booking( array(
            'email'        => $email,
            'name'         => $name,
            'phone'        => $phone,
            'address'      => $address,
            'street'       => $street,
            'postcode'     => $postcode,
            'city'         => $city,
            'state'        => $state,
            'country'      => $country_code,
            'country_name' => $country_name,
            'notes'        => $notes,
        ), $slot );
        return array( 'ok' => true, 'url' => home_url( '/checkout/success?booking=1' ) );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] /boek booking failed: ' . $e->getMessage() );
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'booking_failed' ), 500 );
    }
}

/**
 * Create/find the WP user + insert the booking row, then push to Teamleader.
 * No payment, no approval queue. The customer gets a confirmation email and the
 * admins are notified. Teamleader failures never fail the booking (WP is the
 * source of truth / safety net).
 *
 * @param array{email:string,name:string,phone:string,address:string,street:string,postcode:string,city:string,state:string,country:string,country_name:string,notes:string} $data
 * @param object $slot  Slot row (must have id + slot_start_datetime).
 */
function ml_boek_provision_booking( array $data, $slot ) {
    $email = $data['email'];

    $user = get_user_by( 'email', $email );
    if ( ! $user ) {
        $username = ml_unique_username( $email );
        $user_id  = wp_insert_user( array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password( 24, true, true ),
            'display_name' => $data['name'] ?: $username,
            'role'         => ML_ROLE_CUSTOMER,
        ) );
        if ( is_wp_error( $user_id ) ) {
            throw new \RuntimeException( 'WP user creation failed: ' . $user_id->get_error_message() );
        }
        $user = get_user_by( 'id', $user_id );
    } elseif ( ! in_array( ML_ROLE_CUSTOMER, (array) $user->roles, true ) && ! user_can( $user, 'administrator' ) ) {
        $user->add_role( ML_ROLE_CUSTOMER );
    }

    // Contact + structured address details from the /boek form.
    if ( $data['phone'] ) update_user_meta( $user->ID, ML_META_PHONE, $data['phone'] );
    if ( ! empty( $data['street'] ) )   update_user_meta( $user->ID, '_ml_address_line1',   $data['street'] );
    if ( ! empty( $data['postcode'] ) ) update_user_meta( $user->ID, '_ml_address_postal',  $data['postcode'] );
    if ( ! empty( $data['city'] ) )     update_user_meta( $user->ID, '_ml_address_city',    $data['city'] );
    if ( ! empty( $data['state'] ) )    update_user_meta( $user->ID, '_ml_address_state',   $data['state'] );
    if ( ! empty( $data['country'] ) )  update_user_meta( $user->ID, '_ml_address_country', $data['country'] );
    update_user_meta( $user->ID, ML_META_LANG, ml_current_lang() );

    // Insert the booking row — idempotent per (user, slot, service_type).
    global $wpdb;
    $book_tbl   = ml_table( 'bookings' );
    $slot_id    = (int) $slot->id;
    $booking_id = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$book_tbl} WHERE user_id=%d AND slot_id=%d AND service_type=%s",
        $user->ID, $slot_id, 'initial_scan'
    ) );
    if ( ! $booking_id ) {
        $now_db = current_time( 'mysql', true );
        $wpdb->insert( $book_tbl, array(
            'user_id'        => $user->ID,
            'slot_id'        => $slot_id,
            'service_type'   => 'initial_scan',
            'status'         => 'requested',
            'customer_notes' => (string) $data['notes'],
            'scheduled_for'  => $slot->slot_start_datetime,
            'created_at'     => $now_db,
            'updated_at'     => $now_db,
        ) );
        $booking_id = (int) $wpdb->insert_id;
    }

    // Confirm to the customer + notify admins.
    ml_mail_send( $user->user_email, 'booking_requested', array(
        'user' => $user,
        'slot' => $slot,
    ), $user->ID );
    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_booking_requested', array(
            'user'  => $user,
            'slot'  => $slot,
            'notes' => (string) $data['notes'],
        ) );
    }

    // Push the lead to Teamleader (no-op until the integration is connected).
    if ( function_exists( 'ml_tl_push_booking' ) ) {
        try {
            ml_tl_push_booking( $user, array_merge( $data, array(
                'booking_id'    => $booking_id,
                'scheduled_for' => $slot->slot_start_datetime,
            ) ) );
        } catch ( \Throwable $e ) {
            error_log( '[memorylane] Teamleader push failed (booking still saved): ' . $e->getMessage() );
        }
    }

    return $booking_id;
}
