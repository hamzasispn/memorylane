<?php
/**
 * Memory Lane — Plan sync.
 * Admin defines plan amounts in WP; this module creates/updates the Stripe
 * Product + three Prices (yearly setup, monthly, reactivation) and writes
 * the IDs back to options so Checkout uses them.
 *
 * Stripe Prices are immutable. When an amount changes, we create a NEW Price
 * and archive the old one (active=false). Existing subscriptions keep their
 * old price — that's by design; only new checkouts use the new one.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Read the plan config for the active mode.
 * Amounts are stored in minor units (cents).
 */
function ml_plan_get() {
    return array(
        'product_name'           => ml_stripe_opt( 'plan_name',           'Memory Lane' ),
        'product_description'    => ml_stripe_opt( 'plan_description',    '' ),
        'currency'               => strtolower( ml_stripe_opt( 'plan_currency', 'eur' ) ),
        'year_one_amount'        => (int) ml_stripe_opt( 'plan_year_one_amount',    0 ),
        'monthly_amount'         => (int) ml_stripe_opt( 'plan_monthly_amount',     0 ),
        'reactivation_amount'    => (int) ml_stripe_opt( 'plan_reactivation_amount', 0 ),
        'product_id'             => ml_stripe_opt( 'product_id',          '' ),
        'setup_price_id'         => ml_stripe_opt( 'setup_price_id',       '' ),
        'monthly_price_id'       => ml_stripe_opt( 'monthly_price_id',     '' ),
        'reactivation_price_id'  => ml_stripe_opt( 'reactivation_price_id', '' ),
        'synced_at'              => (int) ml_stripe_opt( 'plan_synced_at',  0 ),
    );
}

function ml_plan_save_raw( $fields ) {
    $mode = ml_stripe_mode();
    foreach ( $fields as $k => $v ) {
        update_option( "ml_stripe_{$mode}_{$k}", $v, false );
    }
}

/**
 * Convert a decimal (string) like "299.00" or "9,50" into cents.
 */
function ml_to_minor_units( $decimal ) {
    if ( $decimal === '' || $decimal === null ) return 0;
    $clean = str_replace( array( ' ', "\xC2\xA0" ), '', (string) $decimal );
    $clean = str_replace( ',', '.', $clean );
    if ( ! is_numeric( $clean ) ) return 0;
    return (int) round( ( (float) $clean ) * 100 );
}

function ml_from_minor_units( $cents ) {
    return number_format( ( (int) $cents ) / 100, 2, '.', '' );
}

/**
 * Push the WP plan to Stripe. Creates Product if missing; creates new Prices
 * when the amount differs from what's already saved. Archives old Prices.
 *
 * @return array { ok: bool, error?: string, changes: array }
 */
function ml_plan_sync_to_stripe() {
    if ( ! ml_stripe_secret() ) {
        return array( 'ok' => false, 'error' => __( 'Add your Stripe secret key first.', 'memorylane' ) );
    }
    $stripe = ml_stripe();
    $plan   = ml_plan_get();
    $changes = array();

    try {
        // 1. Product.
        $product_id = $plan['product_id'];
        if ( $product_id ) {
            try {
                $product = $stripe->products->retrieve( $product_id );
                $stripe->products->update( $product_id, array(
                    'name'        => $plan['product_name'] ?: 'Memory Lane',
                    'description' => $plan['product_description'] ?: null,
                ) );
                $changes[] = 'product:updated';
            } catch ( \Stripe\Exception\InvalidRequestException $e ) {
                // Saved ID is stale; create a fresh product.
                $product_id = '';
            }
        }
        if ( ! $product_id ) {
            $product = $stripe->products->create( array(
                'name'        => $plan['product_name'] ?: 'Memory Lane',
                'description' => $plan['product_description'] ?: null,
                'metadata'    => array( 'ml_source' => 'memorylane_wp' ),
            ) );
            $product_id = $product->id;
            $changes[] = 'product:created';
            ml_plan_save_raw( array( 'product_id' => $product_id ) );
        }

        // 2. Yearly setup price (€XX, recurring yearly, 1 iteration via the Schedule).
        if ( $plan['year_one_amount'] > 0 ) {
            $price_id = ml_plan_ensure_price(
                $stripe,
                $product_id,
                $plan['setup_price_id'],
                $plan['year_one_amount'],
                $plan['currency'],
                array( 'interval' => 'year', 'interval_count' => 1 ),
                'Memory Lane Setup + Year 1'
            );
            if ( $price_id !== $plan['setup_price_id'] ) {
                ml_plan_save_raw( array( 'setup_price_id' => $price_id ) );
                $changes[] = 'setup_price:created';
            } else {
                $changes[] = 'setup_price:unchanged';
            }
        }

        // 3. Monthly recurring price.
        if ( $plan['monthly_amount'] > 0 ) {
            $price_id = ml_plan_ensure_price(
                $stripe,
                $product_id,
                $plan['monthly_price_id'],
                $plan['monthly_amount'],
                $plan['currency'],
                array( 'interval' => 'month', 'interval_count' => 1 ),
                'Memory Lane Monthly Hosting'
            );
            if ( $price_id !== $plan['monthly_price_id'] ) {
                ml_plan_save_raw( array( 'monthly_price_id' => $price_id ) );
                $changes[] = 'monthly_price:created';
            } else {
                $changes[] = 'monthly_price:unchanged';
            }
        }

        // 4. Reactivation one-time price.
        if ( $plan['reactivation_amount'] > 0 ) {
            $price_id = ml_plan_ensure_price(
                $stripe,
                $product_id,
                $plan['reactivation_price_id'],
                $plan['reactivation_amount'],
                $plan['currency'],
                null, // one-time
                'Memory Lane Reactivation'
            );
            if ( $price_id !== $plan['reactivation_price_id'] ) {
                ml_plan_save_raw( array( 'reactivation_price_id' => $price_id ) );
                $changes[] = 'reactivation_price:created';
            } else {
                $changes[] = 'reactivation_price:unchanged';
            }
        }

        ml_plan_save_raw( array( 'plan_synced_at' => time() ) );

        return array( 'ok' => true, 'changes' => $changes );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] plan sync failed: ' . $e->getMessage() );
        return array( 'ok' => false, 'error' => $e->getMessage(), 'changes' => $changes );
    }
}

/**
 * Ensure a Price object exists in Stripe matching the (amount, currency, recurring) tuple
 * under $product_id. If $existing_id matches the desired tuple, reuse it.
 * Otherwise, create a new Price and archive the old one.
 */
function ml_plan_ensure_price( $stripe, $product_id, $existing_id, $amount_cents, $currency, $recurring, $nickname ) {
    if ( $existing_id ) {
        try {
            $p = $stripe->prices->retrieve( $existing_id );
            $matches =
                $p->product === $product_id
                && (int) $p->unit_amount === (int) $amount_cents
                && strtolower( $p->currency ) === strtolower( $currency )
                && (
                    // recurring tuple match
                    ( $recurring === null && $p->type === 'one_time' )
                    || ( $recurring !== null && $p->type === 'recurring'
                         && $p->recurring->interval === $recurring['interval']
                         && (int) $p->recurring->interval_count === (int) $recurring['interval_count'] )
                )
                && $p->active;
            if ( $matches ) return $existing_id;
        } catch ( \Stripe\Exception\InvalidRequestException $e ) {
            // stale; ignore and create new
        }
    }

    $params = array(
        'product'     => $product_id,
        'unit_amount' => (int) $amount_cents,
        'currency'    => strtolower( $currency ),
        'nickname'    => $nickname,
        'metadata'    => array( 'ml_source' => 'memorylane_wp' ),
    );
    if ( $recurring ) {
        $params['recurring'] = $recurring;
    }
    $new = $stripe->prices->create( $params );

    // Archive the previous price so it doesn't get used by new checkouts.
    if ( $existing_id ) {
        try { $stripe->prices->update( $existing_id, array( 'active' => false ) ); } catch ( \Throwable $e ) {}
    }

    return $new->id;
}

/**
 * Fetch current state from Stripe (for the "Synced status" panel).
 */
function ml_plan_fetch_state() {
    if ( ! ml_stripe_secret() ) return null;
    $stripe = ml_stripe();
    $plan   = ml_plan_get();
    $out = array( 'product' => null, 'prices' => array() );

    if ( $plan['product_id'] ) {
        try { $out['product'] = $stripe->products->retrieve( $plan['product_id'] ); } catch ( \Throwable $e ) {}
    }
    foreach ( array(
        'setup'        => $plan['setup_price_id'],
        'monthly'      => $plan['monthly_price_id'],
        'reactivation' => $plan['reactivation_price_id'],
    ) as $k => $pid ) {
        if ( ! $pid ) continue;
        try { $out['prices'][ $k ] = $stripe->prices->retrieve( $pid ); } catch ( \Throwable $e ) {}
    }
    return $out;
}
