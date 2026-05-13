<?php
defined( 'ABSPATH' ) || exit;
$user = wp_get_current_user();
$err  = ml_flash_take( 'error' );
$ok   = ml_flash_take( 'success' );

$phone   = get_user_meta( $user->ID, ML_META_PHONE, true );
$current_lang = ml_current_lang();
?>
<div>
    <h1 class="ml-h1"><?php ml_e( 'settings.title' ); ?></h1>
    <p class="ml-sub"></p>

    <?php if ( $err ) : ?><div class="ml-alert ml-alert--error"><?php echo esc_html( $err ); ?></div><?php endif; ?>
    <?php if ( $ok ) : ?><div class="ml-alert ml-alert--success"><?php echo esc_html( $ok ); ?></div><?php endif; ?>

    <div class="ml-grid">
        <div class="ml-card">
            <h2 class="ml-h2"><?php ml_e( 'settings.tab.profile' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'ml_profile' ); ?>
                <input type="hidden" name="action" value="ml_profile">

                <div class="ml-field">
                    <label class="ml-label" for="s-name"><?php echo esc_html__( 'Name', 'memorylane' ); ?></label>
                    <input id="s-name" name="display_name" class="ml-input" value="<?php echo esc_attr( $user->display_name ); ?>">
                </div>
                <div class="ml-field">
                    <label class="ml-label" for="s-email"><?php ml_e( 'settings.email' ); ?></label>
                    <input id="s-email" class="ml-input" value="<?php echo esc_attr( $user->user_email ); ?>" disabled>
                </div>
                <div class="ml-field">
                    <label class="ml-label" for="s-phone"><?php ml_e( 'settings.phone' ); ?></label>
                    <input id="s-phone" name="phone" class="ml-input" value="<?php echo esc_attr( $phone ); ?>">
                </div>
                <button type="submit" class="ml-btn ml-btn--primary"><?php ml_e( 'common.save' ); ?></button>
            </form>
        </div>

        <div class="ml-card">
            <h2 class="ml-h2"><?php ml_e( 'settings.tab.security' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'ml_change_pw' ); ?>
                <input type="hidden" name="action" value="ml_change_pw">
                <div class="ml-field">
                    <label class="ml-label" for="s-cp"><?php ml_e( 'settings.current_password' ); ?></label>
                    <input id="s-cp" name="current_password" type="password" required class="ml-input">
                </div>
                <div class="ml-field">
                    <label class="ml-label" for="s-np1"><?php ml_e( 'auth.reset.new_password' ); ?></label>
                    <input id="s-np1" name="new_password" type="password" required minlength="10" class="ml-input">
                </div>
                <button type="submit" class="ml-btn ml-btn--primary"><?php ml_e( 'settings.change_password' ); ?></button>
            </form>
        </div>

        <div class="ml-card">
            <h2 class="ml-h2"><?php ml_e( 'settings.tab.language' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'ml_lang' ); ?>
                <input type="hidden" name="action" value="ml_lang">
                <div class="ml-field">
                    <label class="ml-label"><?php ml_e( 'settings.language_label' ); ?></label>
                    <select name="lang" class="ml-input">
                        <option value="nl" <?php selected( $current_lang, 'nl' ); ?>>Nederlands</option>
                        <option value="en" <?php selected( $current_lang, 'en' ); ?>>English</option>
                    </select>
                </div>
                <button type="submit" class="ml-btn ml-btn--primary"><?php ml_e( 'common.save' ); ?></button>
            </form>
        </div>
    </div>
</div>
