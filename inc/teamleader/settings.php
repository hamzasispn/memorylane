<?php
/**
 * Memory Lane — Teamleader connection settings (rendered inside the slim /admin).
 */
defined( 'ABSPATH' ) || exit;

/**
 * Render the Teamleader settings block. Call from the admin settings template.
 */
function ml_tl_render_settings() {
    $connected  = ml_tl_is_connected();
    $cid        = ml_tl_client_id();
    $has_secret = ml_tl_client_secret() !== '';
    $flag       = isset( $_GET['tl'] ) ? sanitize_key( $_GET['tl'] ) : '';
    ?>
    <h2 style="margin-top:24px;">Teamleader</h2>

    <?php if ( $flag === 'connected' ) : ?>
        <div class="mla-banner is-success">Connected to Teamleader.</div>
    <?php elseif ( $flag === 'error' ) : ?>
        <div class="mla-banner is-danger">Teamleader connection failed. Check the client ID/secret and redirect URI, then try again.</div>
    <?php elseif ( $flag === 'retried' ) : ?>
        <div class="mla-banner is-success">Queued bookings re-sent.</div>
    <?php endif; ?>

    <?php
    $account_line = '';
    if ( $connected ) {
        try {
            $me = ml_tl_test_connection();
            $who = trim( ( $me['first_name'] ?? '' ) . ' ' . ( $me['last_name'] ?? '' ) );
            $account_line = $who ?: ( $me['email'] ?? '' );
        } catch ( \Throwable $e ) {
            $account_line = '';
        }
    }
    ?>
    <div class="mla-banner <?php echo $connected ? 'is-success' : ''; ?>">
        <strong>Status:</strong> <?php echo $connected ? '● Connected' : '○ Not connected'; ?>
        <?php if ( $connected && $account_line ) : ?> — <?php echo esc_html( $account_line ); ?><?php endif; ?>
        <?php if ( $connected && ! $account_line ) : ?> — <em>token present but test call failed; try Disconnect + Connect</em><?php endif; ?>
    </div>

    <form class="mla-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'ml_tl_save' ); ?>
        <input type="hidden" name="action" value="ml_tl_save">

        <div class="mla-form-row">
            <label>Redirect URI</label>
            <div>
                <code style="font-size:12px;"><?php echo esc_html( ml_tl_redirect_uri() ); ?></code>
                <div class="help">Paste this exact value into your Teamleader integration's redirect URI.</div>
            </div>
        </div>
        <div class="mla-form-row">
            <label>Client ID</label>
            <div><input type="text" name="tl_client_id" value="<?php echo esc_attr( $cid ); ?>" placeholder="from Teamleader Marketplace"></div>
        </div>
        <div class="mla-form-row">
            <label>Client secret</label>
            <div><input type="text" name="tl_client_secret" value="<?php echo $has_secret ? '••••••••' : ''; ?>" placeholder="<?php echo $has_secret ? 'leave blank to keep current' : 'from Teamleader Marketplace'; ?>">
                <div class="help">Stored in WP options (not autoloaded).</div>
            </div>
        </div>

        <div style="margin-top:16px;display:flex;gap:8px;">
            <button class="mla-btn mla-btn--primary" type="submit">Save credentials</button>
            <?php if ( $cid && $has_secret ) : ?>
                <button class="mla-btn mla-btn--secondary" type="submit" name="tl_action" value="connect"><?php echo $connected ? 'Reconnect' : 'Connect Teamleader'; ?></button>
            <?php endif; ?>
            <?php if ( $connected ) : ?>
                <button class="mla-btn mla-btn--danger" type="submit" name="tl_action" value="disconnect">Disconnect</button>
            <?php endif; ?>
        </div>
    </form>

    <?php if ( $connected ) :
        $phases   = ml_tl_list_phases();
        $cur_phase = (string) get_option( ML_TL_OPT_PHASE_ID, '' );
        $queued    = ml_tl_queue_count();
    ?>
        <form class="mla-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px;">
            <?php wp_nonce_field( 'ml_tl_save' ); ?>
            <input type="hidden" name="action" value="ml_tl_save">
            <input type="hidden" name="tl_action" value="sync_settings">

            <div class="mla-form-row">
                <label>New deals land in phase</label>
                <div>
                    <?php if ( $phases ) : ?>
                        <select name="tl_phase_id">
                            <?php foreach ( $phases as $ph ) : ?>
                                <option value="<?php echo esc_attr( $ph['id'] ); ?>" <?php selected( $cur_phase, $ph['id'] ); ?>><?php echo esc_html( $ph['name'] ?? $ph['id'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <em>Could not load deal phases (the first available phase will be used automatically).</em>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mla-form-row">
                <label>Queued bookings</label>
                <div>
                    <strong><?php echo (int) $queued; ?></strong> waiting to sync
                    <?php if ( $queued ) : ?>
                        <button class="mla-btn mla-btn--secondary" type="submit" name="tl_action" value="retry" style="margin-left:8px;">Retry now</button>
                    <?php endif; ?>
                    <div class="help">Bookings captured while disconnected (or after an API error) are retried automatically every 15 min.</div>
                </div>
            </div>
            <div style="margin-top:12px;"><button class="mla-btn mla-btn--primary" type="submit">Save sync settings</button></div>
        </form>
    <?php endif; ?>
    <?php
}

add_action( 'admin_post_ml_tl_save', function () {
    if ( ! current_user_can( ML_CAP_MANAGE ) && ! current_user_can( 'manage_options' ) ) wp_die();
    check_admin_referer( 'ml_tl_save' );

    $back   = home_url( '/admin/settings' );
    $action = sanitize_key( $_POST['tl_action'] ?? 'save' );

    if ( $action === 'disconnect' ) {
        ml_tl_disconnect();
        wp_safe_redirect( $back );
        exit;
    }

    if ( $action === 'retry' ) {
        ml_tl_process_queue();
        wp_safe_redirect( add_query_arg( 'tl', 'retried', $back ) );
        exit;
    }

    if ( $action === 'sync_settings' ) {
        if ( isset( $_POST['tl_phase_id'] ) ) {
            update_option( ML_TL_OPT_PHASE_ID, sanitize_text_field( wp_unslash( $_POST['tl_phase_id'] ) ), false );
        }
        wp_safe_redirect( $back );
        exit;
    }

    // Save credentials.
    update_option( ML_TL_OPT_CLIENT_ID, sanitize_text_field( wp_unslash( $_POST['tl_client_id'] ?? '' ) ), false );
    $secret = trim( (string) wp_unslash( $_POST['tl_client_secret'] ?? '' ) );
    if ( $secret !== '' && strpos( $secret, '•' ) === false ) {
        update_option( ML_TL_OPT_CLIENT_SECRET, $secret, false );
    }

    if ( $action === 'connect' ) {
        wp_redirect( ml_tl_authorize_url() ); // external redirect to Teamleader
        exit;
    }
    wp_safe_redirect( $back );
    exit;
} );
