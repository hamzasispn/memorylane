<?php
/**
 * Memory Lane — thin Teamleader Focus API client.
 * All Teamleader endpoints are POST to https://api.focus.teamleader.eu/<action>.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Call a Teamleader API action. Returns the decoded `data` (or full body).
 *
 * @throws \RuntimeException on transport or API error.
 */
function ml_tl_request( $action, array $body = array() ) {
    $token = ml_tl_get_access_token();
    if ( ! $token ) {
        throw new \RuntimeException( 'Teamleader not connected (no access token).' );
    }

    $res = wp_remote_post( ML_TL_API_BASE . '/' . ltrim( $action, '/' ), array(
        'timeout' => 20,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
        'body'    => $body ? wp_json_encode( $body ) : '{}',
    ) );

    if ( is_wp_error( $res ) ) {
        throw new \RuntimeException( 'Teamleader request failed: ' . $res->get_error_message() );
    }
    $code = (int) wp_remote_retrieve_response_code( $res );
    $raw  = wp_remote_retrieve_body( $res );
    $json = json_decode( $raw, true );

    if ( $code < 200 || $code >= 300 ) {
        throw new \RuntimeException( "Teamleader API {$action} error ({$code}): {$raw}" );
    }
    // 204 No Content (e.g. some updates) → empty.
    if ( $code === 204 || $raw === '' ) {
        return array();
    }
    return $json['data'] ?? $json;
}
