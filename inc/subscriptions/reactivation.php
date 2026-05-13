<?php
/**
 * Memory Lane — customer-driven reactivation cycle.
 *
 * Lifecycle:
 *   1. Cancelled customer clicks "Reactivate" on /dashboard/subscription
 *   2. Picks plan (monthly | annual) on /dashboard/reactivate
 *   3. REST POST /reactivate → ml_open_reactivation_session() → Stripe Checkout
 *   4. checkout.session.completed webhook → ml_record_reactivation_payment()
 *      inserts pending row, flips tours to pending_reactivation, emails customer + admin
 *   5. Admin clicks "Reactivation done" → ml_complete_reactivation() flips tours
 *      to active, sub to active, emails customer
 *
 * The cycle_number is per-user and monotonic: max(existing) + 1.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Resolve the price ID for a chosen reactivation plan.
 */
function ml_reactivation_price_for_plan( $plan ) {
    return $plan === 'annual'
        ? ml_stripe_annual_price_id()
        : ml_stripe_monthly_price_id();
}

/**
 * Return the open pending reactivation row for a user, or null.
 */
function ml_reactivation_open_for_user( $user_id ) {
    global $wpdb;
    $tbl = ml_table( 'reactivations' );
    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$tbl} WHERE user_id = %d AND status = %s ORDER BY id DESC LIMIT 1",
        (int) $user_id,
        ML_REACTIVATION_STATUS_PENDING
    ) );
}

/**
 * Has the user ever reactivated? Used for cycle numbering.
 */
function ml_reactivation_next_cycle_number( $user_id ) {
    global $wpdb;
    $tbl = ml_table( 'reactivations' );
    $max = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COALESCE(MAX(cycle_number), 0) FROM {$tbl} WHERE user_id = %d",
        (int) $user_id
    ) );
    return $max + 1;
}

/**
 * Eligibility: customer must be a known user, have a Stripe customer linked,
 * have a cancelled subscription, and have no pending reactivation in flight.
 *
 * @return array { ok: bool, error?: string }
 */
function ml_reactivation_check_eligibility( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) return array( 'ok' => false, 'error' => 'not_logged_in' );

    $cust = get_user_meta( $user_id, ML_META_STRIPE_CUSTOMER, true );
    if ( ! $cust ) return array( 'ok' => false, 'error' => 'no_stripe_customer' );

    $row = ml_get_subscription_row( $user_id );
    if ( ! $row ) return array( 'ok' => false, 'error' => 'no_prior_subscription' );

    $cancelled_states = array( 'cancelled', 'canceled', 'incomplete_expired', 'unpaid' );
    if ( ! in_array( $row->status, $cancelled_states, true ) ) {
        return array( 'ok' => false, 'error' => 'subscription_still_active' );
    }

    if ( ml_reactivation_open_for_user( $user_id ) ) {
        return array( 'ok' => false, 'error' => 'reactivation_already_pending' );
    }

    if ( ! ml_stripe_is_configured() ) return array( 'ok' => false, 'error' => 'stripe_not_configured' );
    if ( ! ml_stripe_reactivation_price_id() ) return array( 'ok' => false, 'error' => 'reactivation_fee_not_priced' );

    return array( 'ok' => true );
}

/**
 * Create a Stripe Checkout Session for reactivation.
 * Fee is added to the first invoice via subscription_data.add_invoice_items.
 *
 * @return array { ok: bool, url?: string, error?: string }
 */
function ml_open_reactivation_session( $user_id, $plan ) {
    $plan = $plan === 'annual' ? 'annual' : 'monthly';

    $eligibility = ml_reactivation_check_eligibility( $user_id );
    if ( ! $eligibility['ok'] ) return $eligibility;

    $recurring_price = ml_reactivation_price_for_plan( $plan );
    if ( ! $recurring_price ) {
        return array( 'ok' => false, 'error' => 'plan_price_missing' );
    }
    if ( $plan === 'annual' && ! ml_stripe_annual_price_id() ) {
        return array( 'ok' => false, 'error' => 'annual_not_configured' );
    }

    $stripe = ml_stripe();
    $cust   = get_user_meta( $user_id, ML_META_STRIPE_CUSTOMER, true );

    try {
        $session = $stripe->checkout->sessions->create( array(
            'mode'        => 'subscription',
            'customer'    => $cust,
            'line_items'  => array( array(
                'price'    => $recurring_price,
                'quantity' => 1,
            ) ),
            'subscription_data' => array(
                'add_invoice_items' => array( array(
                    'price'    => ml_stripe_reactivation_price_id(),
                    'quantity' => 1,
                ) ),
                'metadata' => array(
                    'ml_intent'   => 'reactivation',
                    'ml_plan'     => $plan,
                    'ml_user_id'  => (string) $user_id,
                ),
            ),
            'metadata' => array(
                'ml_intent'  => 'reactivation',
                'ml_plan'    => $plan,
                'ml_user_id' => (string) $user_id,
                'ml_lang'    => ml_current_lang(),
            ),
            'billing_address_collection' => 'auto',
            'locale'                     => ml_current_lang() === 'en' ? 'en' : 'nl',
            'success_url'                => home_url( '/dashboard/subscription?reactivation=pending' ),
            'cancel_url'                 => home_url( '/dashboard/subscription?reactivation=cancelled' ),
        ) );
        return array( 'ok' => true, 'url' => $session->url );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] reactivation session create failed: ' . $e->getMessage() );
        return array( 'ok' => false, 'error' => $e->getMessage() );
    }
}

/**
 * Webhook callback when a reactivation checkout completes.
 * Idempotent on stripe_checkout_session_id (UNIQUE constraint).
 *
 * @param \Stripe\Checkout\Session $session  Already expanded with subscription + invoice if available.
 * @param int                       $user_id Resolved from Stripe customer lookup (never from metadata).
 */
function ml_record_reactivation_payment( $session, $user_id ) {
    global $wpdb;
    $tbl = ml_table( 'reactivations' );

    $plan = ( $session->metadata->ml_plan ?? 'monthly' ) === 'annual' ? 'annual' : 'monthly';
    $now  = current_time( 'mysql', true );

    // Pull subscription + payment intent ids from the session (subscription mode populates these).
    $sub_id = is_object( $session->subscription ) ? $session->subscription->id : (string) $session->subscription;
    $pi_id  = null; // mode=subscription doesn't return a top-level payment_intent; we'll fill from invoice if present.

    // Try to fetch the latest invoice to get the PI (fee was on first invoice).
    try {
        if ( $sub_id ) {
            $stripe = ml_stripe();
            $sub    = $stripe->subscriptions->retrieve( $sub_id, array( 'expand' => array( 'latest_invoice.payment_intent' ) ) );
            if ( $sub && $sub->latest_invoice && $sub->latest_invoice->payment_intent ) {
                $pi_id = is_object( $sub->latest_invoice->payment_intent ) ? $sub->latest_invoice->payment_intent->id : (string) $sub->latest_invoice->payment_intent;
            }
            $current_period_end = $sub->current_period_end ? gmdate( 'Y-m-d H:i:s', $sub->current_period_end ) : null;
            $raw_sub            = wp_json_encode( $sub->toArray() );
        } else {
            $current_period_end = null;
            $raw_sub            = null;
        }
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] reactivation: subscription fetch failed: ' . $e->getMessage() );
        $current_period_end = null;
        $raw_sub            = null;
    }

    $reactivation_fee_cents = (int) ( ml_stripe_opt( 'plan_reactivation_amount', 0 ) );
    $currency               = strtolower( (string) ( $session->currency ?? ml_stripe_opt( 'plan_currency', 'eur' ) ) );
    $cycle                  = ml_reactivation_next_cycle_number( $user_id );

    $inserted = $wpdb->query( $wpdb->prepare(
        "INSERT IGNORE INTO {$tbl}
            (user_id, cycle_number, plan_chosen, activation_fee_paid_cents, activation_fee_currency,
             stripe_checkout_session_id, stripe_payment_intent_id, stripe_subscription_id,
             requested_at, status, raw_json, created_at, updated_at)
         VALUES (%d, %d, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        $user_id,
        $cycle,
        $plan,
        $reactivation_fee_cents,
        $currency,
        $session->id,
        $pi_id,
        $sub_id,
        $now,
        ML_REACTIVATION_STATUS_PENDING,
        wp_json_encode( $session->toArray() ),
        $now,
        $now
    ) );

    if ( ! $inserted ) {
        // Duplicate webhook delivery — already recorded.
        return false;
    }

    // Mirror Stripe subscription state locally with pending_reactivation status.
    $cust_id = is_object( $session->customer ) ? $session->customer->id : (string) $session->customer;
    ml_upsert_subscription( $user_id, array(
        'stripe_customer_id'   => $cust_id,
        'stripe_sub_id'        => $sub_id,
        'status'               => ML_SUB_STATUS_PENDING_REACTIVATION,
        'current_period_end'   => $current_period_end,
        'cancel_at_period_end' => 0,
        'raw_json'             => $raw_sub,
    ) );

    // Flip the user's archived / pending_archive tours to pending_reactivation.
    ml_flag_user_tours_pending_reactivation( $user_id );

    // Notify customer + admin.
    $user = get_user_by( 'id', $user_id );
    if ( $user ) {
        ml_mail_send( $user->user_email, 'reactivation_pending', array(
            'user'      => $user,
            'plan'      => $plan,
            'sla_hours' => ML_REACTIVATION_SLA_HOURS,
            'cycle'     => $cycle,
        ), $user_id );
    }
    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_reactivation_request', array(
            'user'   => $user,
            'plan'   => $plan,
            'cycle'  => $cycle,
            'tours'  => $user ? ml_get_user_tours( $user_id ) : array(),
        ) );
    }

    wp_cache_delete( 'ml_access_' . $user_id, 'ml' );
    return true;
}

/**
 * Flag all of a user's archived / pending_archive tours as pending_reactivation.
 * @return array of tour IDs that were flipped.
 */
function ml_flag_user_tours_pending_reactivation( $user_id ) {
    $tours   = ml_get_user_tours( $user_id );
    $flagged = array();
    foreach ( $tours as $t ) {
        $status = get_post_meta( $t->ID, ML_META_TOUR_STATUS, true );
        if ( in_array( $status, array( ML_TOUR_STATUS_ARCHIVED, ML_TOUR_STATUS_PENDING_ARCHIVE, '' ), true ) ) {
            update_post_meta( $t->ID, ML_META_TOUR_STATUS, ML_TOUR_STATUS_PENDING_REACTIVATION );
            $flagged[] = $t->ID;
        }
    }
    return $flagged;
}

/**
 * Admin clicks "Reactivation done". Idempotent.
 *
 * @return array { ok: bool, error?: string }
 */
function ml_complete_reactivation( $row_id, $admin_id ) {
    global $wpdb;
    $tbl = ml_table( 'reactivations' );

    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE id = %d", (int) $row_id ) );
    if ( ! $row ) return array( 'ok' => false, 'error' => 'not_found' );
    if ( $row->status === ML_REACTIVATION_STATUS_COMPLETED ) {
        return array( 'ok' => true, 'noop' => true );
    }
    if ( $row->status !== ML_REACTIVATION_STATUS_PENDING ) {
        return array( 'ok' => false, 'error' => 'not_pending' );
    }

    $now = current_time( 'mysql', true );

    $wpdb->update( $tbl, array(
        'status'       => ML_REACTIVATION_STATUS_COMPLETED,
        'completed_at' => $now,
        'completed_by' => (int) $admin_id,
        'updated_at'   => $now,
    ), array( 'id' => $row->id ) );

    // Flip subscription status from pending_reactivation → active.
    ml_upsert_subscription( $row->user_id, array(
        'stripe_sub_id' => $row->stripe_subscription_id,
        'status'        => 'active',
    ) );

    // Flip every pending_reactivation tour back to active.
    $tours = ml_get_user_tours( $row->user_id );
    foreach ( $tours as $t ) {
        if ( get_post_meta( $t->ID, ML_META_TOUR_STATUS, true ) === ML_TOUR_STATUS_PENDING_REACTIVATION ) {
            update_post_meta( $t->ID, ML_META_TOUR_STATUS, ML_TOUR_STATUS_ACTIVE );
        }
    }

    // Audit.
    $wpdb->insert( ml_table( 'admin_actions_log' ), array(
        'admin_id'       => (int) $admin_id,
        'target_user_id' => (int) $row->user_id,
        'action'         => 'reactivation_complete',
        'before_state'   => ML_REACTIVATION_STATUS_PENDING,
        'after_state'    => ML_REACTIVATION_STATUS_COMPLETED,
        'reason'         => 'reactivation_id=' . $row->id,
        'created_at'     => $now,
    ) );

    // Customer email.
    $user = get_user_by( 'id', $row->user_id );
    if ( $user ) {
        ml_mail_send( $user->user_email, 'reactivation_completed', array(
            'user'  => $user,
            'plan'  => $row->plan_chosen,
            'cycle' => (int) $row->cycle_number,
        ), $row->user_id );
    }

    wp_cache_delete( 'ml_access_' . $row->user_id, 'ml' );
    return array( 'ok' => true );
}

/**
 * Handle refund of a reactivation invoice. Triggered from charge.refunded webhook.
 */
function ml_mark_reactivation_refunded( $stripe_payment_intent_id ) {
    global $wpdb;
    $tbl = ml_table( 'reactivations' );
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$tbl} WHERE stripe_payment_intent_id = %s LIMIT 1",
        (string) $stripe_payment_intent_id
    ) );
    if ( ! $row ) return false;

    $now = current_time( 'mysql', true );
    $wpdb->update( $tbl, array(
        'status'     => ML_REACTIVATION_STATUS_REFUNDED,
        'updated_at' => $now,
    ), array( 'id' => $row->id ) );

    // Cancel the Stripe subscription if still around.
    if ( $row->stripe_subscription_id ) {
        try { ml_stripe()->subscriptions->cancel( $row->stripe_subscription_id ); } catch ( \Throwable $e ) {}
    }

    // Tours back to archived.
    $tours = ml_get_user_tours( $row->user_id );
    foreach ( $tours as $t ) {
        $st = get_post_meta( $t->ID, ML_META_TOUR_STATUS, true );
        if ( in_array( $st, array( ML_TOUR_STATUS_PENDING_REACTIVATION, ML_TOUR_STATUS_ACTIVE ), true ) ) {
            update_post_meta( $t->ID, ML_META_TOUR_STATUS, ML_TOUR_STATUS_ARCHIVED );
        }
    }

    // Sub status cancelled.
    ml_upsert_subscription( $row->user_id, array(
        'stripe_sub_id' => $row->stripe_subscription_id,
        'status'        => 'cancelled',
    ) );

    $user = get_user_by( 'id', $row->user_id );
    if ( $user ) {
        ml_mail_send( $user->user_email, 'reactivation_refunded', array( 'user' => $user ), $row->user_id );
    }
    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'reactivation_refunded', array( 'user' => $user, 'admin' => true ) );
    }

    wp_cache_delete( 'ml_access_' . $row->user_id, 'ml' );
    return true;
}

/**
 * KPI counter for admin overview.
 */
function ml_count_pending_reactivations() {
    global $wpdb;
    $tbl = ml_table( 'reactivations' );
    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$tbl} WHERE status = %s",
        ML_REACTIVATION_STATUS_PENDING
    ) );
}

function ml_oldest_pending_reactivation_hours() {
    global $wpdb;
    $tbl = ml_table( 'reactivations' );
    $oldest = $wpdb->get_var( $wpdb->prepare(
        "SELECT requested_at FROM {$tbl} WHERE status = %s ORDER BY requested_at ASC LIMIT 1",
        ML_REACTIVATION_STATUS_PENDING
    ) );
    if ( ! $oldest ) return 0;
    $ts = strtotime( $oldest . ' UTC' );
    return $ts ? max( 0, round( ( time() - $ts ) / HOUR_IN_SECONDS, 1 ) ) : 0;
}
