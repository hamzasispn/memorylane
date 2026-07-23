<?php
/**
 * Memory Lane — Matterport Model API (GraphQL) client.
 * Auth: API Token (key + secret) via HTTP Basic. Inert until configured.
 */
defined( 'ABSPATH' ) || exit;

const ML_MP_API_URL = 'https://api.matterport.com/api/models/graph';

/**
 * Run a GraphQL query against the Matterport Model API.
 *
 * @throws \RuntimeException on transport / API error.
 */
function ml_mp_request( $query, array $variables = array() ) {
    if ( ! ml_mp_is_configured() ) {
        throw new \RuntimeException( 'Matterport not configured (missing API key/secret).' );
    }
    $res = wp_remote_post( ML_MP_API_URL, array(
        'timeout' => 25,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( ml_mp_api_key() . ':' . ml_mp_api_secret() ),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
        'body'    => wp_json_encode( array( 'query' => $query, 'variables' => $variables ) ),
    ) );

    if ( is_wp_error( $res ) ) {
        throw new \RuntimeException( 'Matterport request failed: ' . $res->get_error_message() );
    }
    $code = (int) wp_remote_retrieve_response_code( $res );
    $raw  = wp_remote_retrieve_body( $res );
    $json = json_decode( $raw, true );

    if ( $code < 200 || $code >= 300 ) {
        throw new \RuntimeException( "Matterport API error ({$code}): {$raw}" );
    }
    if ( ! empty( $json['errors'] ) ) {
        throw new \RuntimeException( 'Matterport GraphQL error: ' . wp_json_encode( $json['errors'] ) );
    }
    return $json['data'] ?? array();
}
