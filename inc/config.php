<?php
/**
 * Memory Lane — constants & config readers.
 */
defined( 'ABSPATH' ) || exit;

// Versioning.
define( 'ML_VERSION',    '0.1.0' );
define( 'ML_DB_VERSION', '0.1.0' );

// Paths.
define( 'ML_PATH', get_template_directory() . '/' );
define( 'ML_URI',  get_template_directory_uri() . '/' );
define( 'ML_INC',  ML_PATH . 'inc/' );

// Custom user role.
define( 'ML_ROLE_CUSTOMER', 'memorylane_customer' );
define( 'ML_CAP_MANAGE',    'manage_memorylane' );

// User-meta keys.
define( 'ML_META_STRIPE_CUSTOMER', '_ml_stripe_customer_id' );
define( 'ML_META_PHONE',           '_ml_phone' );
define( 'ML_META_LANG',            '_ml_language' );
define( 'ML_META_LAST_LOGIN',      '_ml_last_login_at' );

// Setup approval lifecycle.
define( 'ML_META_SETUP_STATE',      '_ml_setup_state' );
define( 'ML_META_SETUP_PAID_AT',    '_ml_setup_paid_at' );
define( 'ML_META_SETUP_PAYMENT',    '_ml_setup_payment_intent_id' );
define( 'ML_META_SETUP_AMOUNT',     '_ml_setup_amount' );
define( 'ML_META_SETUP_CURRENCY',   '_ml_setup_currency' );
define( 'ML_META_SETUP_APPROVED_AT','_ml_setup_approved_at' );
define( 'ML_META_SETUP_APPROVED_BY','_ml_setup_approved_by' );

define( 'ML_SETUP_STATE_PENDING',  'pending_approval' );
define( 'ML_SETUP_STATE_APPROVED', 'approved' );
define( 'ML_SETUP_STATE_REFUNDED', 'refunded' );

// Approval SLA shown to customer.
define( 'ML_APPROVAL_SLA_HOURS', 8 );

// Year 1 length (trial days on the monthly subscription).
define( 'ML_YEAR_ONE_DAYS', 365 );

// Tour CPT + meta keys.
define( 'ML_CPT_TOUR',           'ml_tour' );
define( 'ML_META_TOUR_USER',     '_ml_tour_user_id' );
define( 'ML_META_TOUR_PROVIDER', '_ml_tour_provider' );
define( 'ML_META_TOUR_URL',      '_ml_tour_url' );
define( 'ML_META_TOUR_EMBED',    '_ml_tour_embed_code' );
define( 'ML_META_TOUR_STATUS',   '_ml_tour_status' );
define( 'ML_META_TOUR_ADDRESS',  '_ml_tour_address' );

// Tour statuses.
define( 'ML_TOUR_STATUS_ACTIVE',  'active' );
define( 'ML_TOUR_STATUS_ARCHIVED', 'archived' );
define( 'ML_TOUR_STATUS_PENDING_ARCHIVE', 'pending_archive' );

// Subscription statuses we treat as access-granting.
function ml_active_subscription_statuses() {
    return array( 'active', 'trialing', 'past_due' );
}

// Options.
define( 'ML_OPT_DB_VERSION',           'ml_db_version' );
define( 'ML_OPT_STRIPE_MODE',          'ml_stripe_active_mode' );
define( 'ML_OPT_STRIPE_CONNECTED_AT',  'ml_stripe_connected_at' );
define( 'ML_OPT_STRIPE_ACCOUNT_ID',    'ml_stripe_account_id' );
define( 'ML_OPT_STRIPE_ACCOUNT_NAME',  'ml_stripe_account_name' );
define( 'ML_OPT_PAST_DUE_GRACE_DAYS',  'ml_past_due_grace_days' );
define( 'ML_OPT_ADMIN_RECIPIENTS',     'ml_admin_recipients' );
define( 'ML_OPT_EMBED_DOMAIN_ALLOW',   'ml_embed_domain_allowlist' );
define( 'ML_OPT_BOOKING_RESCHED_HOURS','ml_booking_reschedule_hours' );
define( 'ML_OPT_BOOKING_CANCEL_HOURS', 'ml_booking_cancel_hours' );
define( 'ML_OPT_EMAIL_FROM_NAME',      'ml_email_from_name' );
define( 'ML_OPT_EMAIL_FROM_ADDRESS',   'ml_email_from_address' );

/**
 * Read a Stripe option for the active mode (test/live).
 */
function ml_stripe_opt( $key, $default = '' ) {
    $mode = get_option( ML_OPT_STRIPE_MODE, 'test' ) === 'live' ? 'live' : 'test';
    return get_option( "ml_stripe_{$mode}_{$key}", $default );
}

function ml_stripe_mode() {
    return get_option( ML_OPT_STRIPE_MODE, 'test' ) === 'live' ? 'live' : 'test';
}

function ml_admin_recipients() {
    $raw = get_option( ML_OPT_ADMIN_RECIPIENTS, get_option( 'admin_email' ) );
    if ( is_array( $raw ) ) return $raw;
    return array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) );
}

function ml_past_due_grace_seconds() {
    return DAY_IN_SECONDS * (int) get_option( ML_OPT_PAST_DUE_GRACE_DAYS, 7 );
}

function ml_embed_domain_allowlist() {
    $raw = get_option( ML_OPT_EMBED_DOMAIN_ALLOW, "my.matterport.com\nmatterport.com" );
    return array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $raw ) ) );
}

function ml_email_from() {
    return array(
        'name'    => get_option( ML_OPT_EMAIL_FROM_NAME, 'Memory Lane' ),
        'address' => get_option( ML_OPT_EMAIL_FROM_ADDRESS, 'no-reply@' . wp_parse_url( home_url(), PHP_URL_HOST ) ),
    );
}
