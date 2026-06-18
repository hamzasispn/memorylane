<?php
/**
 * Memory Lane — Teamleader Focus OAuth2 (authorization-code flow).
 *
 * Tokens are stored in non-autoloaded WP options. The integration is inert
 * until an admin enters client_id + client_secret and clicks "Connect".
 *
 * Docs: https://developer.teamleader.eu/#/authentication
 */
defined( 'ABSPATH' ) || exit;

const ML_TL_AUTHORIZE_URL = 'https://focus.teamleader.eu/oauth2/authorize';
const ML_TL_TOKEN_URL     = 'https://focus.teamleader.eu/oauth2/access_token';
const ML_TL_API_BASE      = 'https://api.focus.teamleader.eu';

const ML_TL_OPT_CLIENT_ID     = 'ml_tl_client_id';
const ML_TL_OPT_CLIENT_SECRET = 'ml_tl_client_secret';
const ML_TL_OPT_ACCESS        = 'ml_tl_access_token';
const ML_TL_OPT_REFRESH       = 'ml_tl_refresh_token';
const ML_TL_OPT_EXPIRES       = 'ml_tl_token_expires';

function ml_tl_client_id()     { return (string) get_option( ML_TL_OPT_CLIENT_ID, '' ); }
function ml_tl_client_secret() { return (string) get_option( ML_TL_OPT_CLIENT_SECRET, '' ); }

/**
 * The OAuth redirect URI to register in the Teamleader integration.
 */
function ml_tl_redirect_uri() {
    return rest_url( 'memorylane/v1/teamleader/callback' );
}

function ml_tl_is_connected() {
    return (string) get_option( ML_TL_OPT_ACCESS, '' ) !== '';
}

/**
 * Build the authorize URL the admin is sent to in order to grant access.
 */
function ml_tl_authorize_url() {
    $state = wp_create_nonce( 'ml_tl_oauth' );
    set_transient( 'ml_tl_oauth_state', $state, 15 * MINUTE_IN_SECONDS );
    return add_query_arg( array(
        'client_id'     => ml_tl_client_id(),
        'response_type' => 'code',
        'redirect_uri'  => ml_tl_redirect_uri(),
        'state'         => $state,
    ), ML_TL_AUTHORIZE_URL );
}

/**
 * Persist a token response (access + refresh + expiry).
 */
function ml_tl_store_tokens( array $tok ) {
    if ( ! empty( $tok['access_token'] ) ) {
        update_option( ML_TL_OPT_ACCESS, $tok['access_token'], false );
    }
    if ( ! empty( $tok['refresh_token'] ) ) {
        update_option( ML_TL_OPT_REFRESH, $tok['refresh_token'], false );
    }
    $expires_in = (int) ( $tok['expires_in'] ?? 3600 );
    update_option( ML_TL_OPT_EXPIRES, time() + $expires_in - 60, false );
}

function ml_tl_disconnect() {
    foreach ( array( ML_TL_OPT_ACCESS, ML_TL_OPT_REFRESH, ML_TL_OPT_EXPIRES ) as $opt ) {
        delete_option( $opt );
    }
}

/**
 * Exchange an authorization code (or refresh token) for tokens.
 */
function ml_tl_token_request( array $extra ) {
    $body = array_merge( array(
        'client_id'     => ml_tl_client_id(),
        'client_secret' => ml_tl_client_secret(),
        'redirect_uri'  => ml_tl_redirect_uri(),
    ), $extra );

    $res = wp_remote_post( ML_TL_TOKEN_URL, array(
        'timeout' => 20,
        'body'    => $body,
    ) );
    if ( is_wp_error( $res ) ) {
        throw new \RuntimeException( 'Teamleader token request failed: ' . $res->get_error_message() );
    }
    $code = (int) wp_remote_retrieve_response_code( $res );
    $json = json_decode( wp_remote_retrieve_body( $res ), true );
    if ( $code < 200 || $code >= 300 || empty( $json['access_token'] ) ) {
        throw new \RuntimeException( 'Teamleader token error (' . $code . '): ' . wp_remote_retrieve_body( $res ) );
    }
    return $json;
}

/**
 * Return a valid access token, refreshing if it has expired.
 */
function ml_tl_get_access_token() {
    $access  = (string) get_option( ML_TL_OPT_ACCESS, '' );
    $expires = (int) get_option( ML_TL_OPT_EXPIRES, 0 );
    if ( $access && $expires > time() ) {
        return $access;
    }
    $refresh = (string) get_option( ML_TL_OPT_REFRESH, '' );
    if ( ! $refresh ) {
        return $access; // may be empty → caller treats as not connected
    }
    $tok = ml_tl_token_request( array(
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refresh,
    ) );
    ml_tl_store_tokens( $tok );
    return (string) $tok['access_token'];
}

/**
 * OAuth callback — Teamleader redirects here with ?code & ?state.
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'memorylane/v1', '/teamleader/callback', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'ml_tl_oauth_callback',
    ) );
} );

function ml_tl_oauth_callback( WP_REST_Request $req ) {
    $code  = (string) $req->get_param( 'code' );
    $state = (string) $req->get_param( 'state' );
    $back  = home_url( '/admin/settings' );

    if ( ! $code || $state !== get_transient( 'ml_tl_oauth_state' ) ) {
        wp_safe_redirect( add_query_arg( 'tl', 'error', $back ) );
        exit;
    }
    delete_transient( 'ml_tl_oauth_state' );

    try {
        $tok = ml_tl_token_request( array(
            'grant_type' => 'authorization_code',
            'code'       => $code,
        ) );
        ml_tl_store_tokens( $tok );
        // Resolve a default deal phase + flush any leads captured while offline.
        if ( function_exists( 'ml_tl_default_phase_id' ) ) ml_tl_default_phase_id();
        if ( function_exists( 'ml_tl_process_queue' ) )    ml_tl_process_queue();
        wp_safe_redirect( add_query_arg( 'tl', 'connected', $back ) );
    } catch ( \Throwable $e ) {
        error_log( '[memorylane] Teamleader OAuth callback failed: ' . $e->getMessage() );
        wp_safe_redirect( add_query_arg( 'tl', 'error', $back ) );
    }
    exit;
}
