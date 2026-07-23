<?php
/**
 * Memory Lane — create the trialed monthly Stripe Subscription after the
 * booking payment. Year 1 is already covered by the one-time activation +
 * year-1 charge, so the subscription trials for 365 days, then bills monthly
 * automatically. Fully autonomous — no admin approval step.
 */
defined( 'ABSPATH' ) || exit;

/**
 * @return array { ok: bool, sub_id?: string, error?: string, reused?: bool }
 */
function ml_create_monthly_subscription( int $user_id, string $default_pm = '' ) {
    $stripe = ml_stripe();
    if ( ! $stripe ) return array( 'ok' => false, 'error' => 'Stripe not configured.' );

    $customer_id = get_user_meta( $user_id, ML_META_STRIPE_CUSTOMER, true );
    if ( ! $customer_id ) return array( 'ok' => false, 'error' => 'User has no Stripe customer ID.' );

    $monthly_price_id = ml_stripe_monthly_price_id();
    if ( ! $monthly_price_id ) return array( 'ok' => false, 'error' => 'Monthly price ID not set.' );

    // Idempotency: skip if a non-cancelled subscription already exists.
    $existing = function_exists( 'ml_get_subscription_row' ) ? ml_get_subscription_row( $user_id ) : null;
    if ( $existing && ! in_array( $existing->status, array( 'cancelled', 'canceled', 'incomplete_expired' ), true ) ) {
        return array( 'ok' => true, 'sub_id' => $existing->stripe_sub_id, 'reused' => true );
    }

    try {
        $params = array(
            'customer'           => $customer_id,
            'items'              => array( array( 'price' => $monthly_price_id ) ),
            'trial_period_days'  => ML_YEAR_ONE_DAYS,
            'proration_behavior' => 'none',
            'metadata'           => array( 'ml_intent' => 'memory_lane_monthly', 'ml_user_id' => (string) $user_id ),
        );
        if ( $default_pm ) {
            $params['default_payment_method'] = $default_pm;
        }
        $sub = $stripe->subscriptions->create( $params );

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
        error_log( '[memorylane] monthly subscription create failed: ' . $e->getMessage() );
        return array( 'ok' => false, 'error' => $e->getMessage() );
    }
}
