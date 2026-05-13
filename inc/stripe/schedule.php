<?php
/**
 * Memory Lane — create the Stripe Subscription on admin approval.
 *
 * Flow:
 *  - Customer paid the one-time setup fee at Checkout (mode=payment).
 *  - Admin clicks "Approve & activate access" once the scan is done.
 *  - We create a Stripe Subscription:
 *      items:               [{ price: MONTHLY_PRICE }]
 *      trial_period_days:   365   (Year 1 free — customer already paid for it via the setup fee)
 *      proration_behavior:  'none'
 *  - After the 365-day trial, Stripe automatically starts charging the monthly amount.
 *  - During the trial, subscription.status = 'trialing' which our access gate treats as active.
 *
 * No Subscription Schedule is needed anymore — a single subscription with a trial
 * does exactly the two-phase behaviour we want, and the schedule transition is
 * implicit (trialing → active at trial_end).
 */
defined( 'ABSPATH' ) || exit;

/**
 * Create the Stripe Subscription for a user who has paid + been admin-approved.
 * Idempotent: if user already has an active local sub row, returns that.
 *
 * @return array { ok: bool, sub_id?: string, error?: string }
 */
function ml_create_subscription_on_approval( int $user_id ) {
    $stripe = ml_stripe();
    if ( ! $stripe ) return array( 'ok' => false, 'error' => 'Stripe not configured.' );

    $customer_id = get_user_meta( $user_id, ML_META_STRIPE_CUSTOMER, true );
    if ( ! $customer_id ) return array( 'ok' => false, 'error' => 'User has no Stripe customer ID — did they pay?' );

    $monthly_price_id = ml_stripe_monthly_price_id();
    if ( ! $monthly_price_id ) return array( 'ok' => false, 'error' => 'Monthly price ID is not set in Settings → Stripe → Subscription plan.' );

    // Idempotency: skip if a non-cancelled subscription already exists for this user.
    $existing = ml_get_subscription_row( $user_id );
    if ( $existing && ! in_array( $existing->status, array( 'cancelled', 'canceled', 'incomplete_expired' ), true ) ) {
        return array( 'ok' => true, 'sub_id' => $existing->stripe_sub_id, 'reused' => true );
    }

    try {
        $sub = $stripe->subscriptions->create( array(
            'customer'           => $customer_id,
            'items'              => array( array( 'price' => $monthly_price_id ) ),
            'trial_period_days'  => ML_YEAR_ONE_DAYS,
            'proration_behavior' => 'none',
            'metadata'           => array(
                'ml_intent'   => 'memory_lane_monthly',
                'ml_user_id'  => (string) $user_id,
                'ml_approved' => '1',
            ),
        ) );

        $year_one_end = $sub->trial_end ?? ( $sub->current_period_end ?? null );

        ml_upsert_subscription( $user_id, array(
            'stripe_customer_id'   => $customer_id,
            'stripe_sub_id'        => $sub->id,
            'stripe_schedule_id'   => null,
            'status'               => $sub->status,
            'current_period_end'   => $sub->current_period_end ? gmdate( 'Y-m-d H:i:s', $sub->current_period_end ) : null,
            'year_one_end_date'    => $year_one_end ? gmdate( 'Y-m-d H:i:s', $year_one_end ) : null,
            'cancel_at_period_end' => $sub->cancel_at_period_end ? 1 : 0,
            'raw_json'             => wp_json_encode( $sub->toArray() ),
        ) );

        return array( 'ok' => true, 'sub_id' => $sub->id, 'reused' => false );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] subscription create on approval failed: ' . $e->getMessage() );
        return array( 'ok' => false, 'error' => $e->getMessage() );
    }
}
