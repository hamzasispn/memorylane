<?php
/**
 * Inline logo — clean mark used on auth pages.
 */
defined( 'ABSPATH' ) || exit;
?>
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ml-auth__logo">
    <svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <rect x="2" y="2" width="24" height="24" rx="6" fill="#18181B"/>
        <path d="M9 19V9l5 7 5-7v10" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span>Memory Lane</span>
</a>
