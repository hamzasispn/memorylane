<?php
/**
 * Memory Lane — public /boek REST endpoint + soft-hold + Stripe Checkout.
 *
 * Flow:
 *   1. Visitor POSTs slot_id + name + email + phone + address + notes
 *   2. We validate slot capacity, refuse if email already has a WP user with paid setup
 *   3. We create a Stripe Checkout Session (mode=payment for the setup fee) with
 *      metadata = { ml_intent: 'initial_purchase_with_slot', ml_slot_id, ml_address, ml_notes }
 *      and customer_email pre-filled.
 *   4. Soft-hold: increment slot.booked_count immediately so other visitors can't grab it.
 *      If checkout is abandoned, the hold expires via cron (see ml_cron_release_stale_holds).
 *   5. On webhook: user is created (existing handler) AND a booking row is inserted
 *      tied to the slot. See inc/stripe/events/checkout-session-completed.php branch.
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

    if ( ! ml_stripe_is_configured() || ! ml_stripe_setup_price_id() || ! ml_stripe_reactivation_price_id() ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'payments_not_configured' ), 503 );
    }

    // Rate limit by IP — 8 / 15 min.
    $rl_key = 'ml_boek_rl_' . ml_ip_hash();
    $count  = (int) get_transient( $rl_key );
    if ( $count >= 8 ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'rate_limited' ), 429 );
    }
    set_transient( $rl_key, $count + 1, 15 * MINUTE_IN_SECONDS );

    $slot_id = (int) $req->get_param( 'slot_id' );
    $email   = sanitize_email( (string) $req->get_param( 'email' ) );
    $name    = sanitize_text_field( (string) $req->get_param( 'name' ) );
    $phone   = sanitize_text_field( (string) $req->get_param( 'phone' ) );
    $address = sanitize_text_field( (string) $req->get_param( 'address' ) );
    $notes   = sanitize_textarea_field( (string) $req->get_param( 'notes' ) );

    if ( ! $slot_id || ! $email || ! $name || ! $phone || ! $address ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'missing_fields' ), 400 );
    }
    if ( ! is_email( $email ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'bad_email' ), 400 );
    }

    // Block if this email already belongs to a paying customer.
    $existing = get_user_by( 'email', $email );
    if ( $existing && get_user_meta( $existing->ID, ML_META_SETUP_STATE, true ) === ML_SETUP_STATE_APPROVED ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'already_customer' ), 409 );
    }

    $slot = ml_get_slot( $slot_id );
    if ( ! $slot || $slot->status !== 'open' || $slot->booked_count >= $slot->capacity ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'slot_unavailable' ), 409 );
    }
    if ( strtotime( $slot->slot_start_datetime . ' UTC' ) <= time() ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'slot_in_past' ), 409 );
    }

    // Soft-hold the slot so two visitors don't grab it.
    ml_increment_slot_booked( $slot_id );

    try {
        $stripe = ml_stripe();
        $session = $stripe->checkout->sessions->create( array(
            'mode'        => 'payment',
            'line_items'  => array(
                array( 'price' => ml_stripe_setup_price_id(),        'quantity' => 1 ),
                array( 'price' => ml_stripe_reactivation_price_id(), 'quantity' => 1 ),
            ),
            'customer_creation'          => 'always',
            'customer_email'             => $email,
            'billing_address_collection' => 'required',
            'phone_number_collection'    => array( 'enabled' => true ),
            'locale'                     => ml_current_lang() === 'en' ? 'en' : 'nl',
            'success_url'                => home_url( '/checkout/success?session_id={CHECKOUT_SESSION_ID}' ),
            'cancel_url'                 => home_url( '/boek?cancelled=1' ),
            'payment_intent_data'        => array( 'metadata' => array( 'ml_intent' => 'memory_lane_setup_year_one' ) ),
            'metadata' => array(
                'ml_intent'              => 'initial_purchase_with_slot',
                'ml_includes_matterport' => '1',
                'ml_lang'                => ml_current_lang(),
                'ml_slot_id'             => (string) $slot_id,
                'ml_name'                => substr( $name, 0, 200 ),
                'ml_phone'               => substr( $phone, 0, 80 ),
                'ml_address'             => substr( $address, 0, 200 ),
                'ml_notes'                => substr( $notes, 0, 400 ),
            ),
            'allow_promotion_codes' => true,
        ) );
        return array( 'ok' => true, 'url' => $session->url );
    } catch ( \Throwable $e ) {
        // Release the soft-hold on failure.
        ml_decrement_slot_booked( $slot_id );
        error_log( '[memorylane] /boek checkout failed: ' . $e->getMessage() );
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'stripe_error' ), 500 );
    }
}

/**
 * Daily cron: release soft-holds on slots where the visitor never returned from Stripe.
 * A booked_count > number of actual booking rows for that slot = stale hold.
 */
add_action( 'ml_cron_release_stale_holds', 'ml_cron_run_release_stale_holds' );

function ml_cron_run_release_stale_holds() {
    global $wpdb;
    $slots_tbl = ml_table( 'availability_slots' );
    $book_tbl  = ml_table( 'bookings' );

    $stale = $wpdb->get_results(
        "SELECT s.id, s.booked_count,
                (SELECT COUNT(*) FROM {$book_tbl} b WHERE b.slot_id = s.id AND b.status IN ('requested','confirmed','completed')) AS real_bookings
         FROM {$slots_tbl} s
         WHERE s.booked_count > 0
           AND s.created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR)"
    );
    foreach ( $stale as $row ) {
        if ( (int) $row->booked_count > (int) $row->real_bookings ) {
            $wpdb->update( $slots_tbl, array( 'booked_count' => (int) $row->real_bookings ), array( 'id' => $row->id ) );
        }
    }
}
