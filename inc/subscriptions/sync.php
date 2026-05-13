<?php
/**
 * Memory Lane — write subscription state from Stripe to local mirror.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Upsert wp_ml_subscriptions for a user. Keys by stripe_sub_id when provided.
 */
function ml_upsert_subscription( $user_id, $fields ) {
    global $wpdb;
    $tbl = ml_table( 'subscriptions' );
    $now = current_time( 'mysql', true );

    $existing = null;
    if ( ! empty( $fields['stripe_sub_id'] ) ) {
        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$tbl} WHERE stripe_sub_id=%s", $fields['stripe_sub_id'] ) );
    }
    if ( ! $existing ) {
        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$tbl} WHERE user_id=%d ORDER BY id DESC LIMIT 1", $user_id ) );
    }

    $payload = array_merge(
        array(
            'user_id'    => $user_id,
            'updated_at' => $now,
        ),
        $fields
    );

    if ( $existing ) {
        // Preserve year_one_end_date if already set and new fields don't include it.
        if ( ! array_key_exists( 'year_one_end_date', $fields ) ) {
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT year_one_end_date FROM {$tbl} WHERE id=%d", $existing->id ) );
            if ( $row && $row->year_one_end_date ) $payload['year_one_end_date'] = $row->year_one_end_date;
        }
        $wpdb->update( $tbl, $payload, array( 'id' => $existing->id ) );
    } else {
        $payload['created_at'] = $now;
        $wpdb->insert( $tbl, $payload );
    }

    wp_cache_delete( 'ml_access_' . $user_id, 'ml' );
}
