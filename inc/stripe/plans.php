<?php
/**
 * Memory Lane — Plan sync.
 * Admin defines plan amounts in WP; this module creates/updates the Stripe
 * Product + three Prices and writes the IDs back to options so Checkout uses them:
 *   - activation : ONE-TIME (scan + setup),          charged at booking
 *   - year 1     : ONE-TIME (first year of hosting), charged at booking
 *   - monthly    : RECURRING monthly,                begins after year 1 (365-day trial)
 *
 * Stripe Prices are immutable. When an amount changes, we create a NEW Price
 * and archive the old one (active=false).
 */
defined( 'ABSPATH' ) || exit;

/**
 * Read the plan config for the active mode. Amounts are in minor units (cents).
 */
function ml_plan_get() {
    return array(
        'product_name'        => ml_stripe_opt( 'plan_name',        'Memory Lane' ),
        'product_description' => ml_stripe_opt( 'plan_description', '' ),
        'currency'            => strtolower( ml_stripe_opt( 'plan_currency', 'eur' ) ),
        'activation_amount'   => (int) ml_stripe_opt( 'plan_activation_amount',   0 ),
        'yearly_amount'       => (int) ml_stripe_opt( 'plan_yearly_amount',       0 ),
        'monthly_amount'      => (int) ml_stripe_opt( 'plan_monthly_amount',      0 ),
        'reactivation_amount' => (int) ml_stripe_opt( 'plan_reactivation_amount', 0 ),
        'product_id'          => ml_stripe_opt( 'product_id',            '' ),
        'activation_price_id' => ml_stripe_opt( 'activation_price_id',   '' ),
        'yearly_price_id'     => ml_stripe_opt( 'yearly_price_id',       '' ),
        'monthly_price_id'    => ml_stripe_opt( 'monthly_price_id',      '' ),
        'reactivation_price_id' => ml_stripe_opt( 'reactivation_price_id', '' ),
        'synced_at'           => (int) ml_stripe_opt( 'plan_synced_at',  0 ),
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
        // Each price gets its OWN Stripe Product so the customer-facing name on
        // Checkout is clear (Checkout shows the product name, not the nickname).
        $specs = array(
            'activation_price_id'   => array( $plan['activation_amount'],   null, ml_plan_product_name( 'activation' ) ),
            'yearly_price_id'       => array( $plan['yearly_amount'],       null, ml_plan_product_name( 'yearly' ) ),
            'monthly_price_id'      => array( $plan['monthly_amount'],      array( 'interval' => 'month', 'interval_count' => 1 ), ml_plan_product_name( 'monthly' ) ),
            'reactivation_price_id' => array( $plan['reactivation_amount'], null, ml_plan_product_name( 'reactivation' ) ),
        );
        foreach ( $specs as $opt_key => $spec ) {
            list( $amount_cents, $recurring, $product_name ) = $spec;
            if ( $amount_cents <= 0 ) continue;
            $price_id = ml_plan_ensure_price( $stripe, $plan[ $opt_key ], $amount_cents, $plan['currency'], $recurring, $product_name );
            if ( $price_id !== $plan[ $opt_key ] ) {
                ml_plan_save_raw( array( $opt_key => $price_id ) );
                $changes[] = $opt_key . ':created';
            } else {
                $changes[] = $opt_key . ':unchanged';
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
 * Customer-facing product name per line (shown on Stripe Checkout + invoices).
 * Filterable so wording/language can be tweaked without touching the sync.
 */
function ml_plan_product_name( $which ) {
    $names = array(
        'activation'   => 'Memory Lane — Activatie & 3D-scan',
        'yearly'       => 'Memory Lane — Online tour (jaar 1)',
        'monthly'      => 'Memory Lane — Maandelijkse hosting',
        'reactivation' => 'Memory Lane — Reactivatie',
    );
    return apply_filters( 'ml_plan_product_name', $names[ $which ] ?? 'Memory Lane', $which );
}

/**
 * Ensure a Price exists matching (amount, currency, recurring) AND whose Product
 * is named $product_name. Each price gets its own product. Reuses a matching
 * price; otherwise creates a new price+product and archives the old price.
 */
function ml_plan_ensure_price( $stripe, $existing_id, $amount_cents, $currency, $recurring, $product_name ) {
    if ( $existing_id ) {
        try {
            $p = $stripe->prices->retrieve( $existing_id );
            $prod_name_ok = false;
            if ( $p->product ) {
                try { $prod_name_ok = ( $stripe->products->retrieve( $p->product )->name === $product_name ); }
                catch ( \Throwable $e ) {}
            }
            $matches =
                (int) $p->unit_amount === (int) $amount_cents
                && strtolower( $p->currency ) === strtolower( $currency )
                && (
                    ( $recurring === null && $p->type === 'one_time' )
                    || ( $recurring !== null && $p->type === 'recurring'
                         && $p->recurring->interval === $recurring['interval']
                         && (int) $p->recurring->interval_count === (int) $recurring['interval_count'] )
                )
                && $p->active
                && $prod_name_ok;
            if ( $matches ) return $existing_id;
        } catch ( \Stripe\Exception\InvalidRequestException $e ) {
            // stale; create new
        }
    }

    $params = array(
        'unit_amount'  => (int) $amount_cents,
        'currency'     => strtolower( $currency ),
        'nickname'     => $product_name,
        'product_data' => array( 'name' => $product_name ), // inline new product with a clear name
    );
    if ( $recurring ) {
        $params['recurring'] = $recurring;
    }
    $new = $stripe->prices->create( $params );

    if ( $existing_id ) {
        try { $stripe->prices->update( $existing_id, array( 'active' => false ) ); } catch ( \Throwable $e ) {}
    }

    return $new->id;
}

/**
 * Fetch current state from Stripe (for the "Synced status" panel). Returns [] on failure.
 */
function ml_plan_fetch_state() {
    if ( ! ml_stripe_secret() ) return null;
    $stripe = ml_stripe();
    $plan   = ml_plan_get();
    $out = array( 'prices' => array() );

    foreach ( array(
        'activation'   => $plan['activation_price_id'],
        'yearly'       => $plan['yearly_price_id'],
        'monthly'      => $plan['monthly_price_id'],
        'reactivation' => $plan['reactivation_price_id'],
    ) as $k => $pid ) {
        if ( ! $pid ) continue;
        try { $out['prices'][ $k ] = $stripe->prices->retrieve( $pid ); } catch ( \Throwable $e ) {}
    }
    return $out;
}
