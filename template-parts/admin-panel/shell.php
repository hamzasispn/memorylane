<?php
/**
 * Memory Lane — admin panel layout shell.
 * Dispatches to a per-section sub-template under template-parts/admin-panel/.
 *
 * @var string $section   one of: overview, customers, tours, bookings,
 *                        subscriptions, invoices, slots, settings, logs
 * @var string $id        optional second path segment (customer id, tour id, …)
 */
defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$section      = $section ?? 'overview';
$id           = $id      ?? '';

$nav = array(
    'overview' => array( 'label' => 'Overview', 'icon' => 'home'     ),
    'bookings' => array( 'label' => 'Bookings', 'icon' => 'calendar' ),
    'tours'    => array( 'label' => 'Tours',    'icon' => 'view'     ),
    'settings' => array( 'label' => 'Settings', 'icon' => 'gear'     ),
);

function ml_ap_icon( $name ) {
    $svgs = array(
        'home'     => '<path d="M3 12 12 4l9 8M5 10v10h14V10"/>',
        'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'view'     => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>',
        'card'     => '<rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 11h20M6 16h4"/>',
        'invoice'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
        'clock'    => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'gear'     => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.03 1.56V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.03H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1.03-1.56V3a2 2 0 1 1 4 0v.09c0 .67.4 1.28 1.03 1.51.62.26 1.34.12 1.82-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87c.23.63.84 1.03 1.51 1.03H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1.03z"/>',
        'list'     => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    );
    return $svgs[ $name ] ?? '';
}

$titles = array(
    'overview' => 'Overview',
    'tours'    => $id === 'new' ? 'Add tour' : ( $id ? 'Edit tour' : 'Tours' ),
    'bookings' => 'Bookings',
    'settings' => 'Settings',
);
$page_title = $titles[ $section ] ?? 'Memory Lane Admin';

?><!DOCTYPE html>
<html lang="<?php echo esc_attr( ml_current_lang() ); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo esc_html( $page_title ); ?> — Memory Lane</title>
    <link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/assets/css/admin-panel.css?v=' . filemtime( get_template_directory() . '/assets/css/admin-panel.css' ) ); ?>">
    <?php wp_head(); ?>
</head>
<body class="mla-body">
<div class="mla-shell">

    <aside class="mla-sidebar">
        <a href="<?php echo esc_url( home_url( '/admin' ) ); ?>" class="mla-sidebar__brand">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="1" y="1" width="22" height="22" rx="6" fill="#18181B"/>
                <path d="M7 17V7l5 7 5-7v10" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Memory Lane</span>
        </a>
        <ul class="mla-sidebar__nav">
            <?php foreach ( $nav as $key => $item ) :
                $url    = home_url( '/admin' . ( $key === 'overview' ? '' : '/' . $key ) );
                $active = ( $section === $key );
            ?>
                <li>
                    <a href="<?php echo esc_url( $url ); ?>" class="<?php echo $active ? 'is-active' : ''; ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo ml_ap_icon( $item['icon'] ); ?></svg>
                        <?php echo esc_html( $item['label'] ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="mla-sidebar__footer">
            <div><strong style="color:var(--mla-fg);"><?php echo esc_html( $current_user->display_name ?: $current_user->user_email ); ?></strong></div>
            <div><a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>">← Customer dashboard</a></div>
            <div><a href="<?php echo esc_url( wp_logout_url( home_url( '/admin' ) ) ); ?>">Logout</a></div>
        </div>
    </aside>

    <main class="mla-main">
        <div class="mla-topbar">
            <h1><?php echo esc_html( $page_title ); ?></h1>
            <div class="mla-user"><strong><?php echo esc_html( $current_user->display_name ?: $current_user->user_email ); ?></strong>(<?php echo esc_html( $current_user->user_email ); ?>)</div>
        </div>

        <?php
        $msg = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : '';
        if ( $msg ) {
            $banners = array(
                'saved'        => array( 'is-success', 'Saved.' ),
                'synced'       => array( 'is-success', 'Synced with Stripe.' ),
                'sync_failed'  => array( 'is-danger',  'Stripe sync failed. Check logs.' ),
                'approved'     => array( 'is-success', 'Access approved.' ),
                'deactivated'  => array( 'is-success', 'Customer deactivated.' ),
                'confirmed'    => array( 'is-success', 'Booking confirmed.' ),
                'cancelled'    => array( 'is-success', 'Booking cancelled.' ),
                'completed'    => array( 'is-success', 'Booking marked complete.' ),
                'deleted'      => array( 'is-success', 'Deleted.' ),
                'retried'      => array( 'is-success', 'Retry triggered.' ),
                'save_failed'  => array( 'is-danger',  'Could not save.' ),
                'not_found'    => array( 'is-danger',  'Record not found.' ),
            );
            $b = $banners[ $msg ] ?? null;
            if ( $b ) {
                echo '<div class="mla-banner ' . esc_attr( $b[0] ) . '">' . esc_html( $b[1] ) . '</div>';
            }
        }

        // Teamleader-not-connected reminder.
        if ( function_exists( 'ml_tl_is_connected' ) && ! ml_tl_is_connected() ) {
            echo '<div class="mla-banner">Teamleader is not connected. Open <a href="' . esc_url( home_url( '/admin/settings' ) ) . '">Settings</a> to connect so bookings sync to your CRM.</div>';
        }

        $page_file_slug = preg_replace( '/[^a-z0-9\-]/', '', $section );
        $page_file      = ML_PATH . 'template-parts/admin-panel/' . $page_file_slug . '.php';
        if ( file_exists( $page_file ) ) {
            include $page_file;
        } else {
            include ML_PATH . 'template-parts/admin-panel/404.php';
        }
        ?>
    </main>

</div>
<?php wp_footer(); ?>
</body>
</html>
