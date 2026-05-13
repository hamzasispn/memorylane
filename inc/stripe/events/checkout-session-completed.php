<?php
/**
 * Memory Lane — Stripe event: checkout.session.completed
 *
 * Now uses a TWO-STEP model:
 *   1. Customer pays the one-time Setup + Year 1 fee (this handler).
 *      → user is created, payment recorded, state = pending_approval, NO Stripe Subscription yet.
 *   2. After the scan + Matterport processing, admin clicks "Approve & activate access"
 *      → ml_create_subscription_on_approval() creates the Stripe Subscription with a
 *        365-day trial (Year 1 free, since they already paid), then monthly recurring forever.
 *      → state flips to approved, customer email sent, ml_user_has_access() returns true.
 *
 * This handler does NOT create a Subscription Schedule anymore.
 */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_checkout_session_completed( \Stripe\Event $event ) {
    $session_obj = $event->data->object;
    $stripe = ml_stripe();
    if ( ! $stripe ) throw new \RuntimeException( 'Stripe client unavailable' );

    // Retrieve full session expanded. Different fields for payment vs subscription mode.
    $session = $stripe->checkout->sessions->retrieve( $session_obj->id, array(
        'expand' => array( 'customer', 'payment_intent', 'customer_details', 'subscription' ),
    ) );

    // Branch: reactivation Checkout (mode=subscription, metadata.ml_intent=reactivation).
    $intent = $session->metadata->ml_intent ?? '';
    if ( $session->mode === 'subscription' && $intent === 'reactivation' ) {
        ml_stripe_event_reactivation_completed( $session );
        return;
    }

    // Only handle mode=payment initial purchases here.
    if ( $session->mode !== 'payment' ) {
        return;
    }
    if ( $session->payment_status !== 'paid' ) {
        return;
    }

    $customer = $session->customer;
    $email    = $session->customer_details->email ?? ( $customer->email ?? null );
    if ( ! $email ) throw new \RuntimeException( 'No email on Stripe session/customer' );

    // Find or create WP user.
    $user = get_user_by( 'email', $email );
    $is_new_user = false;
    if ( ! $user ) {
        $username = ml_unique_username( $email );
        $user_id  = wp_insert_user( array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password( 24, true, true ),
            'display_name' => $session->customer_details->name ?? $username,
            'role'         => ML_ROLE_CUSTOMER,
        ) );
        if ( is_wp_error( $user_id ) ) throw new \RuntimeException( 'WP user creation failed: ' . $user_id->get_error_message() );
        $user = get_user_by( 'id', $user_id );
        $is_new_user = true;
    } else {
        if ( ! in_array( ML_ROLE_CUSTOMER, (array) $user->roles, true ) && ! user_can( $user, 'administrator' ) ) {
            $user->add_role( ML_ROLE_CUSTOMER );
        }
    }

    // Save Stripe customer id + address + phone.
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

    // Record the one-time setup payment + pending_approval state.
    $pi_id = is_object( $session->payment_intent ) ? $session->payment_intent->id : (string) $session->payment_intent;
    update_user_meta( $user->ID, ML_META_SETUP_STATE,    ML_SETUP_STATE_PENDING );
    update_user_meta( $user->ID, ML_META_SETUP_PAID_AT,  current_time( 'mysql', true ) );
    update_user_meta( $user->ID, ML_META_SETUP_PAYMENT,  $pi_id );
    update_user_meta( $user->ID, ML_META_SETUP_AMOUNT,   (int) $session->amount_total );
    update_user_meta( $user->ID, ML_META_SETUP_CURRENCY, strtolower( (string) $session->currency ) );

    // Welcome email with password setup link.
    ml_send_reset_email( $user, 'welcome_set_password' );

    // Purchase confirmation (now mentions the 8h approval SLA + Year 1).
    ml_mail_send( $user->user_email, 'purchase_confirmation', array(
        'user'         => $user,
        'amount_total' => $session->amount_total,
        'currency'     => $session->currency,
        'sla_hours'    => ML_APPROVAL_SLA_HOURS,
    ), $user->ID );

    // Notify admin — they need to do the scan + approve.
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
