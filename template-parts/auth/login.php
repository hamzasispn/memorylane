<?php
defined( 'ABSPATH' ) || exit;
$page_title = ml_t( 'auth.login.title' );
include ML_PATH . 'template-parts/auth/_layout-head.php';
$err   = ml_flash_take( 'error' );
$ok    = ml_flash_take( 'success' );
$email = ml_flash_take( 'email' );
$redir = $_GET['redirect_to'] ?? '';
?>
<div class="ml-auth">
    <div class="ml-auth__form-wrap">
        <div class="ml-auth__form">
            <?php include ML_PATH . 'template-parts/auth/_logo.php'; ?>
            <h1 class="ml-h1"><?php ml_e( 'auth.login.title' ); ?></h1>
            <p class="ml-sub"><?php ml_e( 'auth.login.subtitle' ); ?></p>

            <?php if ( $err ) : ?>
                <div class="ml-alert ml-alert--error"><?php echo esc_html( $err ); ?></div>
            <?php endif; ?>
            <?php if ( $ok ) : ?>
                <div class="ml-alert ml-alert--success"><?php echo esc_html( $ok ); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
                <input type="hidden" name="action" value="ml_login">
                <?php wp_nonce_field( 'ml_login' ); ?>
                <?php if ( $redir ) : ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redir ); ?>">
                <?php endif; ?>
                <input type="text" name="ml_hp" class="ml-hp" tabindex="-1" autocomplete="off">

                <div class="ml-field">
                    <label class="ml-label" for="ml-email"><?php ml_e( 'auth.login.email' ); ?></label>
                    <input id="ml-email" name="email" type="email" required autocomplete="email"
                           class="ml-input" value="<?php echo esc_attr( $email ?? '' ); ?>">
                </div>

                <div class="ml-field">
                    <div class="ml-row-between" style="margin-bottom: 6px;">
                        <label class="ml-label" for="ml-pass" style="margin: 0;"><?php ml_e( 'auth.login.password' ); ?></label>
                        <a href="<?php echo esc_url( home_url( '/forgot-password' ) ); ?>" class="ml-text-sm"><?php ml_e( 'auth.login.forgot' ); ?></a>
                    </div>
                    <input id="ml-pass" name="password" type="password" required autocomplete="current-password" class="ml-input">
                </div>

                <div class="ml-field ml-checkbox-row">
                    <input id="ml-remember" type="checkbox" name="remember" value="1">
                    <label for="ml-remember"><?php ml_e( 'auth.login.remember' ); ?></label>
                </div>

                <button type="submit" class="ml-btn ml-btn--primary ml-btn--block"><?php ml_e( 'auth.login.submit' ); ?></button>
            </form>
        </div>
    </div>
    <?php include ML_PATH . 'template-parts/auth/_aside.php'; ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
