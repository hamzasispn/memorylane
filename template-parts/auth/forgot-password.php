<?php
defined( 'ABSPATH' ) || exit;
$page_title = ml_t( 'auth.forgot.title' );
include ML_PATH . 'template-parts/auth/_layout-head.php';
$err = ml_flash_take( 'error' );
$ok  = ml_flash_take( 'success' );
?>
<div class="ml-auth">
    <div class="ml-auth__form-wrap">
        <div class="ml-auth__form">
            <?php include ML_PATH . 'template-parts/auth/_logo.php'; ?>
            <h1 class="ml-h1"><?php ml_e( 'auth.forgot.title' ); ?></h1>
            <p class="ml-sub"><?php ml_e( 'auth.forgot.subtitle' ); ?></p>

            <?php if ( $err ) : ?><div class="ml-alert ml-alert--error"><?php echo esc_html( $err ); ?></div><?php endif; ?>
            <?php if ( $ok ) : ?><div class="ml-alert ml-alert--success"><?php echo esc_html( $ok ); ?></div><?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
                <input type="hidden" name="action" value="ml_forgot">
                <?php wp_nonce_field( 'ml_forgot' ); ?>
                <input type="text" name="ml_hp" class="ml-hp" tabindex="-1" autocomplete="off">

                <div class="ml-field">
                    <label class="ml-label" for="ml-email"><?php ml_e( 'auth.login.email' ); ?></label>
                    <input id="ml-email" name="email" type="email" required autocomplete="email" class="ml-input">
                </div>

                <button type="submit" class="ml-btn ml-btn--primary ml-btn--block"><?php ml_e( 'auth.forgot.submit' ); ?></button>
                <div class="ml-mt-2 ml-text-sm" style="text-align: center;">
                    <a href="<?php echo esc_url( home_url( '/login' ) ); ?>"><?php ml_e( 'common.back' ); ?></a>
                </div>
            </form>
        </div>
    </div>
    <?php include ML_PATH . 'template-parts/auth/_aside.php'; ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
