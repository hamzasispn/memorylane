<?php
/**
 * Memory Lane — convert Stripe Subscription into a two-phase Subscription Schedule.
 * Phase A: yearly setup price (already in flight after Checkout)
 * Phase B: monthly recurring forever, starts at end of Phase A
 */
defined( 'ABSPATH' ) || exit;

/**
 * Convert a freshly-created Stripe Subscription into a two-phase schedule.
 * Returns the schedule ID or throws.
 */
function ml_stripe_convert_to_schedule( $subscription_id, $year_one_end_ts ) {
    $stripe = ml_stripe();
    if ( ! $stripe ) throw new \RuntimeException( 'Stripe not configured' );

    // Create schedule from the existing subscription.
    $schedule = $stripe->subscriptionSchedules->create( array(
        'from_subscription' => $subscription_id,
    ) );

    // Now update with phase B appended.
    $current_phase = $schedule->phases[0];
    $start_date    = $current_phase->start_date;

    $stripe->subscriptionSchedules->update( $schedule->id, array(
        'end_behavior' => 'release',
        'phases'       => array(
            array(
                'items'      => array( array(
                    'price'    => ml_stripe_setup_price_id(),
                    'quantity' => 1,
                ) ),
                'start_date' => $start_date,
                'end_date'   => $year_one_end_ts,
            ),
            array(
                'items'    => array( array(
                    'price'    => ml_stripe_monthly_price_id(),
                    'quantity' => 1,
                ) ),
                // no iterations / end_date => continues until cancelled
            ),
        ),
    ) );

    return $schedule->id;
}
