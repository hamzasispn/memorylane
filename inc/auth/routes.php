<?php
/**
 * Memory Lane — frontend routes via rewrite rules.
 * Dispatches /login, /forgot-password, /reset-password/{token}, /welcome/{token},
 * /dashboard[/*], /checkout/{start,success,cancel}, /logout to template-parts.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Register query vars.
 */
add_filter( 'query_vars', function ( $vars ) {
    $vars[] = 'ml_route';
    $vars[] = 'ml_token';
    $vars[] = 'ml_subroute';
    $vars[] = 'ml_slug';
    $vars[] = 'ml_admin_section';
    $vars[] = 'ml_admin_id';
    return $vars;
} );

/**
 * Register rewrite rules. Flushed on theme activation.
 */
add_action( 'init', function () {

    // Auth.
    add_rewrite_rule( '^login/?$',                              'index.php?ml_route=login',                       'top' );
    add_rewrite_rule( '^logout/?$',                             'index.php?ml_route=logout',                      'top' );
    add_rewrite_rule( '^forgot-password/?$',                    'index.php?ml_route=forgot',                      'top' );
    add_rewrite_rule( '^reset-password/([^/]+)/?$',             'index.php?ml_route=reset&ml_token=$matches[1]',  'top' );
    add_rewrite_rule( '^welcome/([^/]+)/?$',                    'index.php?ml_route=welcome&ml_token=$matches[1]','top' );

    // Booking thank-you page (reuses the old checkout-success template).
    add_rewrite_rule( '^checkout/success/?$',                   'index.php?ml_route=checkout_success',            'top' );
    add_rewrite_rule( '^boek/?$',                               'index.php?ml_route=boek',                        'top' );

    // Dashboard.
    add_rewrite_rule( '^dashboard/?$',                          'index.php?ml_route=dashboard&ml_subroute=overview',   'top' );
    add_rewrite_rule( '^dashboard/tours/?$',                    'index.php?ml_route=dashboard&ml_subroute=tours',      'top' );
    add_rewrite_rule( '^dashboard/tour/([^/]+)/?$',             'index.php?ml_route=dashboard&ml_subroute=tour-viewer&ml_slug=$matches[1]', 'top' );
    add_rewrite_rule( '^dashboard/booking/?$',                  'index.php?ml_route=dashboard&ml_subroute=booking',    'top' );
    add_rewrite_rule( '^dashboard/settings/?$',                 'index.php?ml_route=dashboard&ml_subroute=settings',   'top' );

    // Admin panel (V2-6/V2-7).
    add_rewrite_rule( '^admin/?$',                                  'index.php?ml_route=admin_panel&ml_admin_section=overview',                              'top' );
    add_rewrite_rule( '^admin/([a-z0-9\-]+)/?$',                    'index.php?ml_route=admin_panel&ml_admin_section=$matches[1]',                           'top' );
    add_rewrite_rule( '^admin/([a-z0-9\-]+)/([a-z0-9\-]+)/?$',      'index.php?ml_route=admin_panel&ml_admin_section=$matches[1]&ml_admin_id=$matches[2]',   'top' );
} );

/**
 * One-time rewrite-flush when routes change. Bumped manually when new rules
 * are added so customers' hosts don't need to re-save permalinks.
 */
define( 'ML_REWRITE_VERSION', '0.4.0-booking-only' );
add_action( 'admin_init', function () {
    if ( get_option( 'ml_rewrite_version' ) !== ML_REWRITE_VERSION ) {
        flush_rewrite_rules( false );
        update_option( 'ml_rewrite_version', ML_REWRITE_VERSION, false );
    }
} );

/**
 * Dispatch the request to the correct template.
 */
add_action( 'template_redirect', function () {
    $route = get_query_var( 'ml_route' );
    if ( ! $route ) return;

    nocache_headers();

    switch ( $route ) {
        case 'login':
            if ( is_user_logged_in() ) {
                wp_safe_redirect( home_url( '/dashboard' ) );
                exit;
            }
            ml_render_template( 'auth/login' );
            break;

        case 'logout':
            check_admin_referer( 'ml_logout' );
            wp_logout();
            wp_safe_redirect( home_url( '/login' ) );
            exit;

        case 'forgot':
            if ( is_user_logged_in() ) {
                wp_safe_redirect( home_url( '/dashboard' ) );
                exit;
            }
            ml_render_template( 'auth/forgot-password' );
            break;

        case 'reset':
            ml_render_template( 'auth/reset-password' );
            break;

        case 'welcome':
            ml_render_template( 'auth/welcome' );
            break;

        case 'boek':
            ml_render_template( 'public/boek' );
            break;

        case 'checkout_success':
            ml_render_template( 'auth/checkout-success' );
            break;

        case 'dashboard':
            if ( ! is_user_logged_in() ) {
                $r = rawurlencode( $_SERVER['REQUEST_URI'] ?? '/dashboard' );
                wp_safe_redirect( home_url( "/login?redirect_to={$r}" ) );
                exit;
            }
            $sub = get_query_var( 'ml_subroute' ) ?: 'overview';
            ml_render_template( 'dashboard/shell', array( 'subroute' => $sub ) );
            break;

        case 'admin_panel':
            if ( ! is_user_logged_in() ) {
                $r = rawurlencode( $_SERVER['REQUEST_URI'] ?? '/admin' );
                wp_safe_redirect( home_url( "/login?redirect_to={$r}" ) );
                exit;
            }
            if ( ! current_user_can( ML_CAP_MANAGE ) && ! current_user_can( 'manage_options' ) ) {
                status_header( 403 );
                wp_die( esc_html__( 'You do not have permission to access this page.', 'memorylane' ), 403 );
            }
            $section = get_query_var( 'ml_admin_section' ) ?: 'overview';
            $id      = get_query_var( 'ml_admin_id' ) ?: '';
            ml_render_template( 'admin-panel/shell', array( 'section' => $section, 'id' => $id ) );
            break;
    }
} );

/**
 * Load a template-parts file with optional vars. Templates output their own full HTML.
 */
function ml_render_template( $relative_path, $vars = array() ) {
    $file = ML_PATH . 'template-parts/' . $relative_path . '.php';
    if ( ! file_exists( $file ) ) {
        status_header( 404 );
        echo '<h1>Template missing: ' . esc_html( $relative_path ) . '</h1>';
        exit;
    }
    extract( $vars, EXTR_SKIP );
    include $file;
    exit;
}
