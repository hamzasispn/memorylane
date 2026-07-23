<?php
/**
 * Memory Lane — Matterport auto-import.
 * Reads models from the Matterport account and links each to a customer by the
 * email in the model's name/label, creating/updating a tour. Idempotent per model id.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Extract the first email address found in a model label/name (pure; testable).
 */
function ml_mp_extract_email( $label ) {
    if ( preg_match( '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', (string) $label, $m ) ) {
        return strtolower( $m[0] );
    }
    return '';
}

/**
 * Pull all models and link them to customers by email-in-label.
 *
 * @return array { ok:bool, linked?:int, skipped?:int, error?:string }
 */
function ml_mp_sync() {
    if ( ! ml_mp_is_configured() ) return array( 'ok' => false, 'error' => 'Matterport not configured.' );

    // Matterport Model API — list models with their name + address.
    $query = 'query { models(pageSize: 200) { results { id name address } } }';
    try {
        $data = ml_mp_request( $query );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] Matterport sync failed: ' . $e->getMessage() );
        return array( 'ok' => false, 'error' => $e->getMessage() );
    }

    $models = $data['models']['results'] ?? array();
    $linked = 0; $skipped = 0;
    foreach ( $models as $mdl ) {
        $model_id = (string) ( $mdl['id'] ?? '' );
        $label    = (string) ( $mdl['name'] ?? '' );
        $email    = ml_mp_extract_email( $label );
        if ( ! $model_id || ! $email ) { $skipped++; continue; }
        $user = get_user_by( 'email', $email );
        if ( ! $user ) { $skipped++; continue; }
        ml_mp_upsert_tour( $user->ID, $model_id, $label );
        $linked++;
    }
    update_option( 'ml_mp_last_sync', time(), false );
    return array( 'ok' => true, 'linked' => $linked, 'skipped' => $skipped );
}

/**
 * Create or update a tour CPT for a Matterport model. Idempotent by model id.
 */
function ml_mp_upsert_tour( $user_id, $model_id, $label ) {
    $existing = get_posts( array(
        'post_type'   => ML_CPT_TOUR,
        'post_status' => 'any',
        'meta_key'    => '_ml_tour_mp_id',
        'meta_value'  => $model_id,
        'numberposts' => 1,
        'fields'      => 'ids',
    ) );

    $embed_url = 'https://my.matterport.com/show/?m=' . rawurlencode( $model_id );
    $iframe    = '<iframe src="' . esc_url( $embed_url ) . '" frameborder="0" allowfullscreen allow="xr-spatial-tracking"></iframe>';

    if ( $existing ) {
        $post_id = (int) $existing[0];
    } else {
        $post_id = wp_insert_post( array(
            'post_type'   => ML_CPT_TOUR,
            'post_status' => 'publish',
            'post_title'  => $label ?: 'Matterport tour',
        ) );
    }
    if ( ! $post_id || is_wp_error( $post_id ) ) return;

    update_post_meta( $post_id, ML_META_TOUR_USER,     (int) $user_id );
    update_post_meta( $post_id, ML_META_TOUR_PROVIDER, 'matterport' );
    update_post_meta( $post_id, ML_META_TOUR_URL,      $embed_url );
    update_post_meta( $post_id, ML_META_TOUR_EMBED,    $iframe );
    update_post_meta( $post_id, ML_META_TOUR_STATUS,   ML_TOUR_STATUS_ACTIVE );
    update_post_meta( $post_id, '_ml_tour_mp_id',      $model_id );
}

// Daily auto-sync — only does anything in Automatic mode + when configured.
add_action( 'ml_cron_mp_sync', function () {
    if ( ml_mp_mode() === 'auto' && ml_mp_is_configured() ) {
        ml_mp_sync();
    }
} );
