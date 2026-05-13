<?php
/**
 * Dashboard shell layout — side nav + topbar + main content.
 * $subroute is set by ml_render_template().
 */
defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$subroute     = $subroute ?? 'overview';

$page_titles = array(
    'overview'     => ml_t( 'overview.title' ),
    'tours'        => ml_t( 'tours.title' ),
    'tour-viewer'  => ml_t( 'tours.view' ),
    'booking'      => ml_t( 'booking.title' ),
    'subscription' => ml_t( 'sub.title' ),
    'settings'     => ml_t( 'settings.title' ),
);
$page_title = $page_titles[ $subroute ] ?? ml_t( 'common.brand' );

include ML_PATH . 'template-parts/auth/_layout-head.php';

$nav_items = array(
    'overview'     => array( 'label' => ml_t( 'nav.overview' ),     'url' => home_url( '/dashboard' ),             'icon' => 'home' ),
    'tours'        => array( 'label' => ml_t( 'nav.tours' ),        'url' => home_url( '/dashboard/tours' ),       'icon' => 'view' ),
    'booking'      => array( 'label' => ml_t( 'nav.booking' ),      'url' => home_url( '/dashboard/booking' ),     'icon' => 'calendar' ),
    'subscription' => array( 'label' => ml_t( 'nav.subscription' ), 'url' => home_url( '/dashboard/subscription' ),'icon' => 'card' ),
    'settings'     => array( 'label' => ml_t( 'nav.settings' ),     'url' => home_url( '/dashboard/settings' ),    'icon' => 'gear' ),
);

$initials = strtoupper( substr( $current_user->display_name ?: $current_user->user_email, 0, 2 ) );

function ml_nav_icon( $name ) {
    $svgs = array(
        'home'     => '<path d="M3 12 12 4l9 8M5 10v10h14V10"/>',
        'view'     => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>',
        'card'     => '<rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 11h20M6 16h4"/>',
        'gear'     => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.03 1.56V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.03H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1.03-1.56V3a2 2 0 1 1 4 0v.09c0 .67.4 1.28 1.03 1.51.62.26 1.34.12 1.82-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87c.23.63.84 1.03 1.51 1.03H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1.03z"/>',
    );
    return $svgs[ $name ] ?? '';
}
?>
<div class="ml-shell" x-data="{ mobile: false, userMenu: false }">

    <aside class="ml-sidebar">
        <a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" class="ml-sidebar__brand">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="1" y="1" width="22" height="22" rx="6" fill="#18181B"/>
                <path d="M7 17V7l5 7 5-7v10" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Memory Lane</span>
        </a>
        <ul class="ml-sidebar__nav">
            <?php foreach ( $nav_items as $key => $item ) :
                $active = ( $subroute === $key ) || ( $key === 'tours' && $subroute === 'tour-viewer' );
            ?>
                <li>
                    <a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo $active ? 'is-active' : ''; ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo ml_nav_icon( $item['icon'] ); ?></svg>
                        <?php echo esc_html( $item['label'] ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="ml-sidebar__footer">
            <form method="post" action="<?php echo esc_url( home_url( '/logout' ) ); ?>" style="display: contents;">
                <?php wp_nonce_field( 'ml_logout' ); ?>
                <button type="submit" class="ml-btn ml-btn--ghost ml-btn--block" style="justify-content: flex-start;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    <?php ml_e( 'auth.logout' ); ?>
                </button>
            </form>
        </div>
    </aside>

    <header class="ml-topbar">
        <button class="ml-mobile-toggle" type="button" @click="mobile = true" aria-label="Menu">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <div class="ml-topbar__title"><?php echo esc_html( $page_title ); ?></div>
        <div class="ml-topbar__right">
            <div class="ml-user-menu" @click.outside="userMenu = false">
                <button class="ml-user-menu__btn" type="button" @click="userMenu = !userMenu">
                    <span class="ml-avatar"><?php echo esc_html( $initials ); ?></span>
                    <span class="ml-text-sm"><?php echo esc_html( $current_user->display_name ?: $current_user->user_email ); ?></span>
                </button>
                <div class="ml-user-menu__dropdown" x-show="userMenu" x-transition style="display: none;">
                    <a href="<?php echo esc_url( home_url( '/dashboard/settings' ) ); ?>"><?php ml_e( 'nav.profile' ); ?></a>
                    <form method="post" action="<?php echo esc_url( home_url( '/logout' ) ); ?>" style="display: contents;">
                        <?php wp_nonce_field( 'ml_logout' ); ?>
                        <button type="submit"><?php ml_e( 'auth.logout' ); ?></button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="ml-main">
        <?php
        $sub_file = ML_PATH . 'template-parts/dashboard/' . preg_replace( '/[^a-z0-9\-]/', '', $subroute ) . '.php';
        if ( file_exists( $sub_file ) ) {
            include $sub_file;
        } else {
            echo '<div class="ml-empty"><div class="ml-empty__title">404</div></div>';
        }
        ?>
    </main>

    <!-- Mobile drawer -->
    <div class="ml-mobile-drawer" x-show="mobile" x-transition @click.self="mobile = false" style="display: none;">
        <div class="ml-mobile-drawer__inner">
            <a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" class="ml-sidebar__brand">
                <span>Memory Lane</span>
            </a>
            <ul class="ml-sidebar__nav">
                <?php foreach ( $nav_items as $key => $item ) : ?>
                    <li>
                        <a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo $subroute === $key ? 'is-active' : ''; ?>">
                            <?php echo esc_html( $item['label'] ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?php wp_footer(); ?>
</body>
</html>
