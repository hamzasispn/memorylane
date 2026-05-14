<?php
/**
 * Memory Lane — Customer billing: invoice fetch + billing-details update.
 * Stripe is the source of truth for invoices; we cache the list briefly.
 * Billing details are mirrored in user meta and pushed to Stripe Customer.
 */
defined( 'ABSPATH' ) || exit;

const ML_INVOICES_CACHE_KEY_PREFIX = 'ml_invoices_';
const ML_INVOICES_CACHE_TTL        = 300; // 5 minutes

/**
 * List invoices for a user from Stripe (cached). Returns array of associative arrays.
 *
 * @param int $user_id
 * @param int $limit   Max number of invoices to return (also passed to Stripe).
 * @param int $page    1-based page index (offset = (page-1)*limit). For >1 we slice locally.
 * @return array { invoices: array, total_estimated: int, has_more: bool }
 */
function ml_billing_list_invoices( $user_id, $limit = 12, $page = 1 ) {
    $cust = (string) get_user_meta( (int) $user_id, ML_META_STRIPE_CUSTOMER, true );
    if ( ! $cust || ! ml_stripe_is_configured() ) {
        return array( 'invoices' => array(), 'total_estimated' => 0, 'has_more' => false );
    }
    $page  = max( 1, (int) $page );
    $limit = max( 1, min( 100, (int) $limit ) );

    $cache_key = ML_INVOICES_CACHE_KEY_PREFIX . $user_id;
    $all       = get_transient( $cache_key );
    if ( ! is_array( $all ) ) {
        try {
            // Fetch up to 100 most-recent invoices. With monthly billing that's
            // ~8 years of history; plenty for a personal-customer dashboard.
            $resp = ml_stripe()->invoices->all( array(
                'customer' => $cust,
                'limit'    => 100,
            ) );
            $all = array();
            foreach ( $resp->data as $inv ) {
                $all[] = array(
                    'id'                => $inv->id,
                    'number'            => $inv->number,
                    'created'           => (int) $inv->created,
                    'status'            => (string) $inv->status,
                    'paid'              => (bool) $inv->paid,
                    'currency'          => strtoupper( (string) $inv->currency ),
                    'amount_paid'       => (int) $inv->amount_paid,
                    'amount_due'        => (int) $inv->amount_due,
                    'hosted_invoice_url'=> (string) ( $inv->hosted_invoice_url ?? '' ),
                    'invoice_pdf'       => (string) ( $inv->invoice_pdf ?? '' ),
                );
            }
            set_transient( $cache_key, $all, ML_INVOICES_CACHE_TTL );
        } catch ( \Throwable $e ) {
            error_log( '[memorylane] invoice list failed: ' . $e->getMessage() );
            return array( 'invoices' => array(), 'total_estimated' => 0, 'has_more' => false );
        }
    }

    $total  = count( $all );
    $offset = ( $page - 1 ) * $limit;
    $slice  = array_slice( $all, $offset, $limit );
    return array(
        'invoices'        => $slice,
        'total_estimated' => $total,
        'has_more'        => ( $offset + count( $slice ) ) < $total,
        'page'            => $page,
        'limit'           => $limit,
    );
}

function ml_billing_invalidate_cache( $user_id ) {
    delete_transient( ML_INVOICES_CACHE_KEY_PREFIX . (int) $user_id );
}

/**
 * Read billing details for a user (from user meta).
 */
function ml_billing_get_details( $user_id ) {
    $user_id = (int) $user_id;
    return array(
        'company' => (string) get_user_meta( $user_id, ML_META_BILLING_COMPANY, true ),
        'vat'     => (string) get_user_meta( $user_id, ML_META_BILLING_VAT,     true ),
        'line1'   => (string) get_user_meta( $user_id, ML_META_BILLING_LINE1,   true ),
        'line2'   => (string) get_user_meta( $user_id, ML_META_BILLING_LINE2,   true ),
        'city'    => (string) get_user_meta( $user_id, ML_META_BILLING_CITY,    true ),
        'postal'  => (string) get_user_meta( $user_id, ML_META_BILLING_POSTAL,  true ),
        'country' => (string) get_user_meta( $user_id, ML_META_BILLING_COUNTRY, true ),
    );
}

/**
 * Save billing details: write to user meta + push to Stripe Customer.
 */
function ml_billing_save_details( $user_id, $data ) {
    $user_id = (int) $user_id;
    $clean = array(
        'company' => sanitize_text_field( (string) ( $data['company'] ?? '' ) ),
        'vat'     => sanitize_text_field( (string) ( $data['vat']     ?? '' ) ),
        'line1'   => sanitize_text_field( (string) ( $data['line1']   ?? '' ) ),
        'line2'   => sanitize_text_field( (string) ( $data['line2']   ?? '' ) ),
        'city'    => sanitize_text_field( (string) ( $data['city']    ?? '' ) ),
        'postal'  => sanitize_text_field( (string) ( $data['postal']  ?? '' ) ),
        'country' => strtoupper( substr( sanitize_text_field( (string) ( $data['country'] ?? '' ) ), 0, 2 ) ),
    );

    update_user_meta( $user_id, ML_META_BILLING_COMPANY, $clean['company'] );
    update_user_meta( $user_id, ML_META_BILLING_VAT,     $clean['vat']     );
    update_user_meta( $user_id, ML_META_BILLING_LINE1,   $clean['line1']   );
    update_user_meta( $user_id, ML_META_BILLING_LINE2,   $clean['line2']   );
    update_user_meta( $user_id, ML_META_BILLING_CITY,    $clean['city']    );
    update_user_meta( $user_id, ML_META_BILLING_POSTAL,  $clean['postal']  );
    update_user_meta( $user_id, ML_META_BILLING_COUNTRY, $clean['country'] );

    // Push to Stripe Customer (if connected).
    $cust = (string) get_user_meta( $user_id, ML_META_STRIPE_CUSTOMER, true );
    if ( $cust && ml_stripe_is_configured() ) {
        try {
            $user = get_userdata( $user_id );
            $update = array(
                'name'    => $clean['company'] ?: ( $user ? $user->display_name : '' ),
                'address' => array(
                    'line1'       => $clean['line1'],
                    'line2'       => $clean['line2'] ?: null,
                    'city'        => $clean['city'],
                    'postal_code' => $clean['postal'],
                    'country'     => $clean['country'] ?: null,
                ),
                'metadata' => array(
                    'ml_billing_company' => $clean['company'],
                    'ml_billing_vat'     => $clean['vat'],
                ),
            );
            ml_stripe()->customers->update( $cust, $update );
        } catch ( \Throwable $e ) {
            error_log( '[memorylane] billing push to Stripe failed: ' . $e->getMessage() );
            return array( 'ok' => false, 'error' => 'stripe_update_failed' );
        }
    }
    ml_billing_invalidate_cache( $user_id );
    return array( 'ok' => true );
}

/**
 * POST /admin-post.php?action=ml_billing_save — dashboard billing edit submit.
 */
add_action( 'admin_post_ml_billing_save', function () {
    if ( ! is_user_logged_in() ) { wp_safe_redirect( home_url( '/login' ) ); exit; }
    check_admin_referer( 'ml_billing_save' );

    $user_id = get_current_user_id();
    $res = ml_billing_save_details( $user_id, $_POST );

    if ( ! empty( $res['ok'] ) ) {
        ml_flash_set( 'success', __( 'Billing details saved.', 'memorylane' ) );
    } else {
        ml_flash_set( 'error', __( 'Could not save billing details. Please try again.', 'memorylane' ) );
    }
    wp_safe_redirect( home_url( '/dashboard/subscription' ) ); exit;
} );
