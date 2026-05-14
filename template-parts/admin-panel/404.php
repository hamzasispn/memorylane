<?php defined( 'ABSPATH' ) || exit; ?>
<div class="mla-card" style="text-align:center;">
    <h2>Page not found</h2>
    <p class="mla-muted">Section "<?php echo esc_html( $section ?? '?' ); ?>" doesn't exist.</p>
    <a class="mla-btn mla-btn--primary" href="<?php echo esc_url( home_url( '/admin' ) ); ?>">Back to overview</a>
</div>
