<?php
/**
 * Stripe event: checkout.session.completed (subscription mode).
 * Autonomous activation — no admin approval. Provisions the WP user, the
 * booking row (from /boek metadata), and the subscription mirror row, then
 * sends the welcome + purchase-confirmation emails.
 */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_checkout_session_completed( \Stripe\Event $event ) {
    $obj    = $event->data->object;
    $stripe = ml_stripe();
    if ( ! $stripe ) throw new \RuntimeException( 'Stripe client unavailable' );

    $session = $stripe->checkout->sessions->retrieve( $obj->id, array(
        'expand' => array( 'customer', 'customer_details', 'subscription' ),
    ) );

    if ( $session->mode !== 'subscription' ) return;          // Phase 2 only handles subscription checkouts
    if ( $session->status !== 'complete' ) return;

    $customer = $session->customer;
    $email    = $session->customer_details->email ?? ( is_object( $customer ) ? ( $customer->email ?? null ) : null );
    if ( ! $email ) throw new \RuntimeException( 'No email on Stripe session' );

    // 1. Find / create the WP user.
    $user = get_user_by( 'email', $email );
    $is_new = false;
    if ( ! $user ) {
        $username = ml_unique_username( $email );
        $uid = wp_insert_user( array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password( 24, true, true ),
            'display_name' => $session->customer_details->name ?? $username,
            'role'         => ML_ROLE_CUSTOMER,
        ) );
        if ( is_wp_error( $uid ) ) throw new \RuntimeException( 'WP user create failed: ' . $uid->get_error_message() );
        $user   = get_user_by( 'id', $uid );
        $is_new = true;
    } elseif ( ! in_array( ML_ROLE_CUSTOMER, (array) $user->roles, true ) && ! user_can( $user, 'administrator' ) ) {
        $user->add_role( ML_ROLE_CUSTOMER );
    }

    // 2. Save Stripe customer id + contact details.
    $cust_id = is_object( $customer ) ? $customer->id : (string) $customer;
    update_user_meta( $user->ID, ML_META_STRIPE_CUSTOMER, $cust_id );
    if ( ! empty( $session->customer_details->phone ) ) update_user_meta( $user->ID, ML_META_PHONE, $session->customer_details->phone );
    $md = $session->metadata;
    if ( ! empty( $md['ml_lang'] ) )    update_user_meta( $user->ID, ML_META_LANG, (string) $md['ml_lang'] );
    if ( ! empty( $md['ml_street'] ) )  update_user_meta( $user->ID, '_ml_address_line1',   (string) $md['ml_street'] );
    if ( ! empty( $md['ml_postcode'] ) ) update_user_meta( $user->ID, '_ml_address_postal', (string) $md['ml_postcode'] );
    if ( ! empty( $md['ml_city'] ) )    update_user_meta( $user->ID, '_ml_address_city',    (string) $md['ml_city'] );
    if ( ! empty( $md['ml_country'] ) ) update_user_meta( $user->ID, '_ml_address_country', (string) $md['ml_country'] );

    // 3. Insert the booking row (idempotent) from the /boek slot metadata.
    $slot_id = isset( $md['ml_slot_id'] ) ? (int) $md['ml_slot_id'] : 0;
    $slot    = null;
    if ( $slot_id ) {
        global $wpdb;
        $btbl = ml_table( 'bookings' );
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$btbl} WHERE user_id=%d AND slot_id=%d AND service_type=%s",
            $user->ID, $slot_id, 'initial_scan'
        ) );
        if ( ! $exists ) {
            $slot = function_exists( 'ml_get_slot' ) ? ml_get_slot( $slot_id ) : null;
            if ( $slot ) {
                $now = current_time( 'mysql', true );
                $wpdb->insert( $btbl, array(
                    'user_id'        => $user->ID,
                    'slot_id'        => $slot_id,
                    'service_type'   => 'initial_scan',
                    'status'         => 'requested',
                    'customer_notes' => isset( $md['ml_notes'] ) ? (string) $md['ml_notes'] : '',
                    'scheduled_for'  => $slot->slot_start_datetime,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ) );
            }
        }
    }

    // 4. Mirror the subscription row (status drives access).
    $sub = $session->subscription;
    if ( is_object( $sub ) ) {
        $fields = ml_sub_fields_from_stripe( $sub );
        if ( empty( $fields['year_one_end_date'] ) && ! empty( $fields['current_period_end'] ) ) {
            $fields['year_one_end_date'] = $fields['current_period_end']; // first period = year one
        }
        ml_upsert_subscription( $user->ID, $fields );
    }

    // 5. Autonomous — mark setup approved, send emails. No admin approval step.
    update_user_meta( $user->ID, ML_META_SETUP_STATE,   ML_SETUP_STATE_APPROVED );
    update_user_meta( $user->ID, ML_META_SETUP_PAID_AT, current_time( 'mysql', true ) );

    if ( $is_new && function_exists( 'ml_send_reset_email' ) ) {
        ml_send_reset_email( $user, 'welcome_set_password' );
    }
    ml_mail_send( $user->user_email, 'purchase_confirmation', array(
        'user'         => $user,
        'amount_total' => (int) ( $session->amount_total ?? 0 ),
        'currency'     => (string) ( $session->currency ?? 'eur' ),
    ), $user->ID );
    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_new_purchase', array( 'user' => $user, 'session' => $session ) );
    }

    // Push Contact + Deal to Teamleader (mirrors the no-payment booking flow),
    // so a Deal exists and _ml_tl_contact_id is set for invoicing on invoice.paid.
    if ( function_exists( 'ml_tl_push_booking' ) ) {
        $tl_data = array(
            'name'          => $session->customer_details->name ?? $user->display_name,
            'phone'         => (string) ( $session->customer_details->phone ?? ( $md['ml_phone'] ?? '' ) ),
            'street'        => (string) ( $md['ml_street'] ?? '' ),
            'postcode'      => (string) ( $md['ml_postcode'] ?? '' ),
            'city'          => (string) ( $md['ml_city'] ?? '' ),
            'country'       => (string) ( $md['ml_country'] ?? '' ),
            'notes'         => (string) ( $md['ml_notes'] ?? '' ),
            'scheduled_for' => isset( $slot ) && $slot ? $slot->slot_start_datetime : '',
        );
        try { ml_tl_push_booking( $user, $tl_data ); }
        catch ( \Throwable $e ) { error_log( '[memorylane] TL push on activation failed: ' . $e->getMessage() ); }
    }
}
