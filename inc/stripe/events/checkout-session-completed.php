<?php
/**
 * Memory Lane — Stripe event: checkout.session.completed
 *
 * Pipeline:
 *  1. Retrieve session with expanded customer + subscription
 *  2. Find or create WP user by email
 *  3. Save Stripe customer id + address + phone
 *  4. Convert subscription to 2-phase schedule (year → monthly)
 *  5. Upsert wp_ml_subscriptions row
 *  6. Email welcome / set-password + email admin
 */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_checkout_session_completed( \Stripe\Event $event ) {
    $session_obj = $event->data->object;
    $stripe = ml_stripe();
    if ( ! $stripe ) throw new \RuntimeException( 'Stripe client unavailable' );

    // Retrieve full session with expansion.
    $session = $stripe->checkout->sessions->retrieve( $session_obj->id, array(
        'expand' => array( 'customer', 'subscription', 'customer_details' ),
    ) );

    if ( ! $session->subscription ) {
        // Not a subscription session — ignore.
        return;
    }

    $sub      = $session->subscription;
    $customer = $session->customer;
    $email    = $session->customer_details->email ?? $customer->email ?? null;
    if ( ! $email ) throw new \RuntimeException( 'No email on Stripe session/customer' );

    // Find or create WP user.
    $user = get_user_by( 'email', $email );
    $is_new_user = false;
    if ( ! $user ) {
        $username = ml_unique_username( $email );
        $user_id  = wp_insert_user( array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => wp_generate_password( 24, true, true ),
            'display_name' => $session->customer_details->name ?? $username,
            'role'       => ML_ROLE_CUSTOMER,
        ) );
        if ( is_wp_error( $user_id ) ) throw new \RuntimeException( 'WP user creation failed: ' . $user_id->get_error_message() );
        $user = get_user_by( 'id', $user_id );
        $is_new_user = true;
    } else {
        // Ensure existing user has customer role.
        if ( ! in_array( ML_ROLE_CUSTOMER, (array) $user->roles, true ) && ! user_can( $user, 'administrator' ) ) {
            $user->add_role( ML_ROLE_CUSTOMER );
        }
    }

    // Save customer id + address + phone.
    update_user_meta( $user->ID, ML_META_STRIPE_CUSTOMER, $customer->id );
    if ( ! empty( $session->customer_details->phone ) ) {
        update_user_meta( $user->ID, ML_META_PHONE, $session->customer_details->phone );
    }
    if ( ! empty( $session->customer_details->address ) ) {
        $addr = $session->customer_details->address;
        update_user_meta( $user->ID, '_ml_address_line1',   $addr->line1   ?? '' );
        update_user_meta( $user->ID, '_ml_address_line2',   $addr->line2   ?? '' );
        update_user_meta( $user->ID, '_ml_address_city',    $addr->city    ?? '' );
        update_user_meta( $user->ID, '_ml_address_postal',  $addr->postal_code ?? '' );
        update_user_meta( $user->ID, '_ml_address_country', $addr->country ?? '' );
    }
    if ( ! empty( $session->metadata['ml_lang'] ?? null ) ) {
        update_user_meta( $user->ID, ML_META_LANG, $session->metadata['ml_lang'] );
    }

    // Convert to schedule (Phase A → Phase B).
    $year_one_end = $sub->current_period_end;
    $schedule_id  = null;
    try {
        $schedule_id = ml_stripe_convert_to_schedule( $sub->id, $year_one_end );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] Schedule conversion failed (will retry via cron): ' . $e->getMessage() );
    }

    // Upsert local subscription row.
    ml_upsert_subscription( $user->ID, array(
        'stripe_customer_id'   => $customer->id,
        'stripe_sub_id'        => $sub->id,
        'stripe_schedule_id'   => $schedule_id,
        'status'               => $schedule_id ? $sub->status : 'schedule_pending',
        'current_period_end'   => $year_one_end ? gmdate( 'Y-m-d H:i:s', $year_one_end ) : null,
        'year_one_end_date'    => $year_one_end ? gmdate( 'Y-m-d H:i:s', $year_one_end ) : null,
        'cancel_at_period_end' => $sub->cancel_at_period_end ? 1 : 0,
        'raw_json'             => wp_json_encode( $sub->toArray() ),
    ) );

    // Send welcome email with password setup link.
    ml_send_reset_email( $user, 'welcome_set_password' );

    // Send purchase confirmation.
    ml_mail_send( $user->user_email, 'purchase_confirmation', array(
        'user'              => $user,
        'amount_total'      => $session->amount_total,
        'currency'          => $session->currency,
        'year_one_end_date' => $year_one_end,
    ), $user->ID );

    // Notify admin.
    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_new_purchase', array(
            'user'    => $user,
            'amount'  => $session->amount_total,
            'currency'=> $session->currency,
            'session' => $session,
        ) );
    }
}

/**
 * Generate a unique username from an email.
 */
function ml_unique_username( $email ) {
    $base = sanitize_user( current( explode( '@', $email ) ), true );
    if ( ! $base ) $base = 'user';
    $candidate = $base;
    $i = 1;
    while ( username_exists( $candidate ) ) {
        $candidate = $base . $i;
        $i++;
    }
    return $candidate;
}
