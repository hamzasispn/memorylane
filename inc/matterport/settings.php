<?php
/**
 * Memory Lane — Matterport tour-import settings (rendered inside the slim /admin).
 * Two modes: Manual (paste embeds, current behaviour) or Automatic (Matterport API).
 */
defined( 'ABSPATH' ) || exit;

const ML_MP_OPT_API_KEY    = 'ml_mp_api_key';
const ML_MP_OPT_API_SECRET = 'ml_mp_api_secret';
const ML_MP_OPT_MODE       = 'ml_mp_mode'; // 'manual' | 'auto'

function ml_mp_mode()          { return get_option( ML_MP_OPT_MODE, 'manual' ) === 'auto' ? 'auto' : 'manual'; }
function ml_mp_api_key()       { return (string) get_option( ML_MP_OPT_API_KEY, '' ); }
function ml_mp_api_secret()    { return (string) get_option( ML_MP_OPT_API_SECRET, '' ); }
function ml_mp_is_configured() { return ml_mp_api_key() !== '' && ml_mp_api_secret() !== ''; }

function ml_mp_render_settings() {
    $mode       = ml_mp_mode();
    $has_secret = ml_mp_api_secret() !== '';
    $last       = (int) get_option( 'ml_mp_last_sync', 0 );
    $flag       = isset( $_GET['mp'] ) ? sanitize_key( $_GET['mp'] ) : '';
    ?>
    <h2 style="margin-top:24px;">Tours — Matterport</h2>

    <?php if ( $flag === 'saved' ) : ?><div class="mla-banner is-success">Saved.</div>
    <?php elseif ( $flag === 'synced' ) : ?><div class="mla-banner is-success">Matterport sync run — check Tours.</div>
    <?php elseif ( $flag === 'sync_failed' ) : ?><div class="mla-banner is-danger">Matterport sync failed. Check the API key/secret.</div><?php endif; ?>

    <form class="mla-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'ml_mp_save' ); ?>
        <input type="hidden" name="action" value="ml_mp_save">

        <div class="mla-form-row">
            <label>How tours are added</label>
            <div>
                <label style="display:block;margin-bottom:6px;">
                    <input type="radio" name="mp_mode" value="manual" <?php checked( $mode, 'manual' ); ?>>
                    <strong>Manual</strong> — you paste the Matterport embed per customer (current behaviour).
                </label>
                <label style="display:block;">
                    <input type="radio" name="mp_mode" value="auto" <?php checked( $mode, 'auto' ); ?>>
                    <strong>Automatic</strong> — import tours from your Matterport account by the customer's email in the model name.
                </label>
            </div>
        </div>

        <div class="mla-form-row">
            <label>Matterport API key</label>
            <div><input type="text" name="mp_api_key" value="<?php echo esc_attr( ml_mp_api_key() ); ?>" placeholder="from Matterport → Settings → Developer Tools"></div>
        </div>
        <div class="mla-form-row">
            <label>Matterport API secret</label>
            <div><input type="text" name="mp_api_secret" value="<?php echo $has_secret ? '••••••••' : ''; ?>" placeholder="<?php echo $has_secret ? 'leave blank to keep current' : 'API token secret'; ?>">
                <div class="help">Automatic mode: each Matterport model's <em>name</em> must contain the customer's email so we can link it.</div>
            </div>
        </div>

        <div style="margin-top:16px;display:flex;gap:8px;">
            <button class="mla-btn mla-btn--primary" type="submit">Save</button>
            <?php if ( ml_mp_is_configured() ) : ?>
                <button class="mla-btn mla-btn--secondary" type="submit" name="mp_action" value="sync">Sync now</button>
            <?php endif; ?>
        </div>
        <?php if ( $last ) : ?><p class="mla-muted" style="margin-top:8px;">Last sync: <?php echo esc_html( gmdate( 'Y-m-d H:i', $last ) ); ?> UTC.</p><?php endif; ?>
    </form>
    <?php
}

add_action( 'admin_post_ml_mp_save', function () {
    if ( ! current_user_can( ML_CAP_MANAGE ) && ! current_user_can( 'manage_options' ) ) wp_die( 'forbidden', 403 );
    check_admin_referer( 'ml_mp_save' );
    $back = home_url( '/admin/settings' );

    update_option( ML_MP_OPT_MODE, ( ( $_POST['mp_mode'] ?? 'manual' ) === 'auto' ) ? 'auto' : 'manual', false );
    update_option( ML_MP_OPT_API_KEY, sanitize_text_field( wp_unslash( $_POST['mp_api_key'] ?? '' ) ), false );
    $secret = trim( (string) wp_unslash( $_POST['mp_api_secret'] ?? '' ) );
    if ( $secret !== '' && strpos( $secret, '•' ) === false ) {
        update_option( ML_MP_OPT_API_SECRET, $secret, false );
    }

    if ( ( $_POST['mp_action'] ?? '' ) === 'sync' ) {
        $res = ml_mp_sync();
        wp_safe_redirect( add_query_arg( 'mp', $res['ok'] ? 'synced' : 'sync_failed', $back ) );
        exit;
    }
    wp_safe_redirect( add_query_arg( 'mp', 'saved', $back ) );
    exit;
} );
