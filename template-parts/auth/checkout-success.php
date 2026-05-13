<?php
defined( 'ABSPATH' ) || exit;
$page_title = ml_t( 'checkout.success.title', 'Bedankt voor je aankoop' );
include ML_PATH . 'template-parts/auth/_layout-head.php';
?>
<div class="ml-auth">
    <div class="ml-auth__form-wrap" style="grid-column: 1 / -1;">
        <div class="ml-auth__form" style="max-width: 480px; text-align: center;">
            <?php include ML_PATH . 'template-parts/auth/_logo.php'; ?>
            <div style="width: 64px; height: 64px; margin: 8px auto 24px; border-radius: 50%; background: var(--ml-success-2); display: flex; align-items: center; justify-content: center;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#047857" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <h1 class="ml-h1"><?php ml_e( 'checkout.success.title', 'Bedankt voor je aankoop' ); ?></h1>
            <p class="ml-sub"><?php ml_e( 'checkout.success.body', 'We hebben je betaling ontvangen. Je krijgt zo dadelijk een e-mail met een link om je wachtwoord in te stellen en toegang te krijgen tot jouw klantenzone.' ); ?></p>
            <a class="ml-btn ml-btn--primary ml-btn--block" href="<?php echo esc_url( home_url( '/login' ) ); ?>"><?php ml_e( 'auth.login.submit' ); ?></a>
            <p class="ml-mt-2 ml-text-muted ml-text-sm"><?php ml_e( 'checkout.success.hint', 'Geen e-mail ontvangen? Controleer je spam of vraag een nieuwe link aan via "Wachtwoord vergeten".' ); ?></p>
        </div>
    </div>
</div>
<?php wp_footer(); ?>
</body>
</html>
