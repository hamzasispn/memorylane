<?php
defined( 'ABSPATH' ) || exit;
$page_title = ml_t( 'auth.reset.title' );
include ML_PATH . 'template-parts/auth/_layout-head.php';

$token = sanitize_text_field( get_query_var( 'ml_token' ) );
$login = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ), true ) : '';

$user_check = $token && $login ? check_password_reset_key( $token, $login ) : new WP_Error( 'missing' );
$token_valid = ! is_wp_error( $user_check );
$err = ml_flash_take( 'error' );
?>
<div class="ml-auth">
    <div class="ml-auth__form-wrap">
        <div class="ml-auth__form">
            <?php include ML_PATH . 'template-parts/auth/_logo.php'; ?>
            <h1 class="ml-h1"><?php ml_e( 'auth.reset.title' ); ?></h1>

            <?php if ( ! $token_valid ) : ?>
                <div class="ml-alert ml-alert--error"><?php ml_e( 'auth.reset.error_token' ); ?></div>
                <a class="ml-btn ml-btn--secondary ml-btn--block" href="<?php echo esc_url( home_url( '/forgot-password' ) ); ?>"><?php ml_e( 'auth.forgot.title' ); ?></a>
            <?php else : ?>
                <?php if ( $err ) : ?><div class="ml-alert ml-alert--error"><?php echo esc_html( $err ); ?></div><?php endif; ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
                    <input type="hidden" name="action" value="ml_reset">
                    <input type="hidden" name="user_login" value="<?php echo esc_attr( $login ); ?>">
                    <input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">
                    <?php wp_nonce_field( 'ml_reset' ); ?>

                    <div class="ml-field">
                        <label class="ml-label" for="p1"><?php ml_e( 'auth.reset.new_password' ); ?></label>
                        <input id="p1" name="password1" type="password" required minlength="10" autocomplete="new-password" class="ml-input">
                    </div>
                    <div class="ml-field">
                        <label class="ml-label" for="p2"><?php ml_e( 'auth.reset.confirm_password' ); ?></label>
                        <input id="p2" name="password2" type="password" required minlength="10" autocomplete="new-password" class="ml-input">
                    </div>
                    <button type="submit" class="ml-btn ml-btn--primary ml-btn--block"><?php ml_e( 'auth.reset.submit' ); ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php include ML_PATH . 'template-parts/auth/_aside.php'; ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
