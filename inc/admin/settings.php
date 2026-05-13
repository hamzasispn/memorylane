<?php
/**
 * Memory Lane — Settings page (tabbed: Stripe, Matterport, Access, Booking, Emails, General).
 */
defined( 'ABSPATH' ) || exit;

function ml_admin_render_settings() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();

    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'stripe';
    $tabs = array(
        'stripe'     => __( 'Stripe', 'memorylane' ),
        'matterport' => __( 'Matterport', 'memorylane' ),
        'access'     => __( 'Access', 'memorylane' ),
        'booking'    => __( 'Booking', 'memorylane' ),
        'emails'     => __( 'Emails', 'memorylane' ),
        'general'    => __( 'General', 'memorylane' ),
    );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Memory Lane — Settings', 'memorylane' ); ?></h1>
        <nav class="nav-tab-wrapper">
            <?php foreach ( $tabs as $k => $label ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=memorylane-settings&tab=' . $k ) ); ?>" class="nav-tab <?php echo $active_tab === $k ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
            <?php endforeach; ?>
        </nav>

        <?php
        switch ( $active_tab ) {
            case 'matterport': ml_admin_settings_matterport(); break;
            case 'access':     ml_admin_settings_access(); break;
            case 'booking':    ml_admin_settings_booking(); break;
            case 'emails':     ml_admin_settings_emails(); break;
            case 'general':    ml_admin_settings_general(); break;
            case 'stripe':
            default:           ml_admin_settings_stripe(); break;
        }
        ?>
    </div>
    <?php
}

/* ───────────────────────── STRIPE TAB ───────────────────────── */

function ml_admin_settings_stripe() {
    $mode = ml_stripe_mode();
    $connected_at  = (int) get_option( ML_OPT_STRIPE_CONNECTED_AT );
    $account_name  = get_option( ML_OPT_STRIPE_ACCOUNT_NAME );
    $account_id    = get_option( ML_OPT_STRIPE_ACCOUNT_ID );
    $publishable   = ml_stripe_publishable();
    $secret        = ml_stripe_secret();
    $webhook       = ml_stripe_webhook_secret();
    $plan          = ml_plan_get();

    $is_connected  = ml_stripe_is_connected();
    $msg           = $_GET['ml_msg']   ?? '';
    $err           = $_GET['ml_err']   ?? '';
    $plan_msg      = $_GET['ml_plan_msg'] ?? '';
    $plan_err      = $_GET['ml_plan_err'] ?? '';
    ?>
    <h2><?php esc_html_e( 'Connect with Stripe', 'memorylane' ); ?></h2>
    <p class="description"><?php esc_html_e( 'Memory Lane uses Stripe to handle payments. Enter your API keys + price IDs below, then click "Connect with Stripe" to verify the connection.', 'memorylane' ); ?></p>

    <div style="background:<?php echo $is_connected ? '#ECFDF5' : '#FEF2F2'; ?>;border:1px solid <?php echo $is_connected ? '#A7F3D0' : '#FCA5A5'; ?>;border-radius:8px;padding:16px;margin:16px 0;max-width:760px;">
        <strong>
            <?php if ( $is_connected ) : ?>
                ● <?php esc_html_e( 'Connected', 'memorylane' ); ?>
            <?php else : ?>
                ● <?php esc_html_e( 'Not connected', 'memorylane' ); ?>
            <?php endif; ?>
        </strong>
        <?php if ( $is_connected ) : ?>
            <div style="margin-top:6px;color:#065F46;">
                <?php echo esc_html( $account_name ); ?>
                <code style="background:rgba(0,0,0,.04);padding:2px 6px;border-radius:4px;font-size:12px;"><?php echo esc_html( $account_id ); ?></code>
                · <?php esc_html_e( 'Mode:', 'memorylane' ); ?> <strong><?php echo esc_html( strtoupper( $mode ) ); ?></strong>
                · <?php esc_html_e( 'Last verified:', 'memorylane' ); ?> <?php echo esc_html( ml_format_datetime( $connected_at ) ); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ( $msg ) : ?><div class="notice notice-success"><p><?php echo esc_html( wp_unslash( $msg ) ); ?></p></div><?php endif; ?>
    <?php if ( $err ) : ?><div class="notice notice-error"><p><?php echo esc_html( wp_unslash( $err ) ); ?></p></div><?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:760px;">
        <?php wp_nonce_field( 'ml_stripe_save' ); ?>
        <input type="hidden" name="action" value="ml_stripe_save">

        <h3 style="margin-top:24px;"><?php esc_html_e( 'Mode', 'memorylane' ); ?></h3>
        <p>
            <label><input type="radio" name="mode" value="test" <?php checked( $mode, 'test' ); ?>> <?php esc_html_e( 'Test mode', 'memorylane' ); ?></label> &nbsp;
            <label><input type="radio" name="mode" value="live" <?php checked( $mode, 'live' ); ?>> <?php esc_html_e( 'Live mode', 'memorylane' ); ?></label>
        </p>
        <p class="description"><?php esc_html_e( 'Each mode has its own keys + price IDs. Switching the mode reads the credentials for that mode.', 'memorylane' ); ?></p>

        <h3 style="margin-top:24px;"><?php esc_html_e( 'API keys', 'memorylane' ); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Publishable key', 'memorylane' ); ?></label></th>
                <td><input type="text" name="publishable_key" class="regular-text" value="<?php echo esc_attr( $publishable ); ?>" placeholder="pk_<?php echo esc_attr( $mode ); ?>_..."></td>
            </tr>
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Secret key', 'memorylane' ); ?></label></th>
                <td><input type="password" name="secret_key" class="regular-text" value="<?php echo esc_attr( $secret ); ?>" placeholder="sk_<?php echo esc_attr( $mode ); ?>_..." autocomplete="new-password">
                <p class="description"><?php esc_html_e( 'Stored encrypted in WP options (autoload off). Never logged.', 'memorylane' ); ?></p></td>
            </tr>
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Webhook signing secret', 'memorylane' ); ?></label></th>
                <td><input type="password" name="webhook_secret" class="regular-text" value="<?php echo esc_attr( $webhook ); ?>" placeholder="whsec_..." autocomplete="new-password">
                <p class="description"><?php esc_html_e( 'Find this in Stripe Dashboard → Developers → Webhooks → your endpoint.', 'memorylane' ); ?></p>
                <p class="description"><strong><?php esc_html_e( 'Webhook URL:', 'memorylane' ); ?></strong> <code><?php echo esc_html( rest_url( 'memorylane/v1/stripe-webhook' ) ); ?></code></p>
                </td>
            </tr>
        </table>

        <p style="margin-top:24px;">
            <button type="submit" name="ml_action" value="save" class="button button-secondary"><?php esc_html_e( 'Save keys', 'memorylane' ); ?></button>
            <button type="submit" name="ml_action" value="connect" class="button button-primary" style="margin-left:8px;">
                ⚡ <?php esc_html_e( 'Connect with Stripe', 'memorylane' ); ?>
            </button>
            <?php if ( $is_connected ) : ?>
                <button type="submit" name="ml_action" value="disconnect" class="button" style="margin-left:8px;color:#B91C1C;border-color:#FCA5A5;" onclick="return confirm('<?php echo esc_js( __( 'Disconnect Stripe? Existing subscriptions in Stripe are unaffected.', 'memorylane' ) ); ?>')"><?php esc_html_e( 'Disconnect', 'memorylane' ); ?></button>
            <?php endif; ?>
        </p>
    </form>

    <?php /* ─────────────────────── PLAN SECTION ─────────────────────── */ ?>
    <hr style="margin:32px 0;">
    <h2><?php esc_html_e( 'Subscription plan', 'memorylane' ); ?></h2>
    <p class="description"><?php esc_html_e( 'Define your plan amounts here. Click "Sync with Stripe" to create or update the Stripe Product + Prices automatically. No need to paste price IDs manually.', 'memorylane' ); ?></p>

    <?php if ( $plan_msg ) : ?><div class="notice notice-success"><p><?php echo esc_html( wp_unslash( $plan_msg ) ); ?></p></div><?php endif; ?>
    <?php if ( $plan_err ) : ?><div class="notice notice-error"><p><?php echo esc_html( wp_unslash( $plan_err ) ); ?></p></div><?php endif; ?>

    <?php if ( $plan['synced_at'] ) :
        $state = ml_plan_fetch_state();
    ?>
        <div style="background:#F4F4F5;border:1px solid #E4E4E7;border-radius:8px;padding:14px 16px;margin:16px 0;max-width:760px;">
            <strong><?php esc_html_e( 'Synced status', 'memorylane' ); ?></strong>
            <span style="color:#71717A;">· <?php esc_html_e( 'Last sync:', 'memorylane' ); ?> <?php echo esc_html( ml_format_datetime( $plan['synced_at'] ) ); ?></span>

            <table style="margin-top:8px;font-size:13px;width:100%;">
                <tr>
                    <td style="padding:4px 0;color:#71717A;width:200px;"><?php esc_html_e( 'Product', 'memorylane' ); ?></td>
                    <td>
                        <?php if ( ! empty( $state['product'] ) ) : ?>
                            <strong><?php echo esc_html( $state['product']->name ); ?></strong>
                            <code style="font-size:11px;color:#71717A;"><?php echo esc_html( $state['product']->id ); ?></code>
                        <?php else : ?>
                            <em style="color:#B91C1C;"><?php esc_html_e( 'Not in Stripe yet', 'memorylane' ); ?></em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php foreach ( array( 'setup' => 'Setup + Year 1 (yearly)', 'monthly' => 'Monthly hosting', 'reactivation' => 'Reactivation (one-time)' ) as $k => $label ) : ?>
                <tr>
                    <td style="padding:4px 0;color:#71717A;"><?php echo esc_html( $label ); ?></td>
                    <td>
                        <?php if ( ! empty( $state['prices'][ $k ] ) ) :
                            $pr = $state['prices'][ $k ];
                            $amt = number_format( $pr->unit_amount / 100, 2 );
                        ?>
                            <strong><?php echo esc_html( strtoupper( $pr->currency ) . ' ' . $amt ); ?></strong>
                            <code style="font-size:11px;color:#71717A;"><?php echo esc_html( $pr->id ); ?></code>
                            <?php if ( ! $pr->active ) : ?><span style="color:#B91C1C;"> (archived)</span><?php endif; ?>
                        <?php else : ?>
                            <em style="color:#71717A;">—</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:760px;">
        <?php wp_nonce_field( 'ml_plan_save' ); ?>
        <input type="hidden" name="action" value="ml_plan_save">

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Product name', 'memorylane' ); ?></label></th>
                <td><input type="text" name="plan_name" class="regular-text" value="<?php echo esc_attr( $plan['product_name'] ); ?>" placeholder="Memory Lane"></td>
            </tr>
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Description (optional)', 'memorylane' ); ?></label></th>
                <td><textarea name="plan_description" rows="2" class="large-text"><?php echo esc_textarea( $plan['product_description'] ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Shown to customers on Stripe receipts and Customer Portal.', 'memorylane' ); ?></p></td>
            </tr>
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Currency', 'memorylane' ); ?></label></th>
                <td>
                    <select name="plan_currency">
                        <?php foreach ( array( 'eur' => 'EUR — Euro', 'usd' => 'USD — US Dollar', 'gbp' => 'GBP — British Pound' ) as $code => $label ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $plan['currency'], $code ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Setup + Year 1 amount', 'memorylane' ); ?></label></th>
                <td>
                    <input type="text" name="plan_year_one_amount" value="<?php echo esc_attr( $plan['year_one_amount'] ? ml_from_minor_units( $plan['year_one_amount'] ) : '' ); ?>" placeholder="299.00" class="small-text" style="width:140px;">
                    <p class="description"><?php esc_html_e( 'Charged once up front; includes 12 months of access.', 'memorylane' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Monthly hosting amount', 'memorylane' ); ?></label></th>
                <td>
                    <input type="text" name="plan_monthly_amount" value="<?php echo esc_attr( $plan['monthly_amount'] ? ml_from_minor_units( $plan['monthly_amount'] ) : '' ); ?>" placeholder="9.00" class="small-text" style="width:140px;">
                    <p class="description"><?php esc_html_e( 'Recurring monthly charge after Year 1.', 'memorylane' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Reactivation fee', 'memorylane' ); ?></label></th>
                <td>
                    <input type="text" name="plan_reactivation_amount" value="<?php echo esc_attr( $plan['reactivation_amount'] ? ml_from_minor_units( $plan['reactivation_amount'] ) : '' ); ?>" placeholder="49.00" class="small-text" style="width:140px;">
                    <p class="description"><?php esc_html_e( 'One-time fee to reactivate an archived tour.', 'memorylane' ); ?></p>
                </td>
            </tr>
        </table>

        <p style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:6px;padding:10px 14px;color:#78350F;font-size:13px;max-width:760px;">
            <strong><?php esc_html_e( 'How it works:', 'memorylane' ); ?></strong>
            <?php esc_html_e( 'Stripe Prices are immutable. When you change an amount and re-sync, a NEW Price is created and the old one is archived. Existing customer subscriptions keep their old price — only new checkouts use the new one.', 'memorylane' ); ?>
        </p>

        <p style="margin-top:20px;">
            <button type="submit" name="plan_action" value="save" class="button button-secondary"><?php esc_html_e( 'Save (no sync)', 'memorylane' ); ?></button>
            <button type="submit" name="plan_action" value="sync" class="button button-primary" style="margin-left:8px;">
                ⇅ <?php esc_html_e( 'Sync with Stripe', 'memorylane' ); ?>
            </button>
        </p>
    </form>
    <?php
}

add_action( 'admin_post_ml_stripe_save', 'ml_handle_stripe_save' );

function ml_handle_stripe_save() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    check_admin_referer( 'ml_stripe_save' );

    $action = sanitize_key( $_POST['ml_action'] ?? 'save' );
    $mode   = ( $_POST['mode'] ?? 'test' ) === 'live' ? 'live' : 'test';

    update_option( ML_OPT_STRIPE_MODE, $mode, false );

    $fields = array(
        'publishable_key' => sanitize_text_field( wp_unslash( $_POST['publishable_key'] ?? '' ) ),
        'secret_key'      => sanitize_text_field( wp_unslash( $_POST['secret_key'] ?? '' ) ),
        'webhook_secret'  => sanitize_text_field( wp_unslash( $_POST['webhook_secret'] ?? '' ) ),
    );
    foreach ( $fields as $k => $v ) {
        update_option( "ml_stripe_{$mode}_{$k}", $v, false );
    }

    $redirect = admin_url( 'admin.php?page=memorylane-settings&tab=stripe' );

    if ( $action === 'disconnect' ) {
        // Clear all per-mode keys (including plan IDs).
        $clear = array( 'publishable_key', 'secret_key', 'webhook_secret', 'product_id', 'setup_price_id', 'monthly_price_id', 'reactivation_price_id', 'plan_synced_at' );
        foreach ( $clear as $k ) {
            delete_option( "ml_stripe_{$mode}_{$k}" );
        }
        delete_option( ML_OPT_STRIPE_CONNECTED_AT );
        delete_option( ML_OPT_STRIPE_ACCOUNT_ID );
        delete_option( ML_OPT_STRIPE_ACCOUNT_NAME );
        wp_safe_redirect( add_query_arg( 'ml_msg', rawurlencode( __( 'Disconnected.', 'memorylane' ) ), $redirect ) );
        exit;
    }

    if ( $action === 'connect' ) {
        if ( ! ml_stripe_secret() ) {
            wp_safe_redirect( add_query_arg( 'ml_err', rawurlencode( __( 'Please fill in publishable + secret keys first.', 'memorylane' ) ), $redirect ) );
            exit;
        }
        try {
            $stripe  = ml_stripe();
            $account = $stripe->accounts->retrieve();

            update_option( ML_OPT_STRIPE_CONNECTED_AT, time(), false );
            update_option( ML_OPT_STRIPE_ACCOUNT_ID, $account->id, false );
            update_option( ML_OPT_STRIPE_ACCOUNT_NAME, $account->business_profile->name ?? $account->email ?? $account->id, false );

            wp_safe_redirect( add_query_arg( 'ml_msg', rawurlencode( __( 'Connected to Stripe successfully. Now define your plan below and click "Sync with Stripe".', 'memorylane' ) ), $redirect ) );
            exit;
        } catch ( \Throwable $e ) {
            delete_option( ML_OPT_STRIPE_CONNECTED_AT );
            wp_safe_redirect( add_query_arg( 'ml_err', rawurlencode( $e->getMessage() ), $redirect ) );
            exit;
        }
    }

    wp_safe_redirect( add_query_arg( 'ml_msg', rawurlencode( __( 'Saved.', 'memorylane' ) ), $redirect ) );
    exit;
}

/* ─────────────── PLAN SAVE / SYNC HANDLER ─────────────── */

add_action( 'admin_post_ml_plan_save', 'ml_handle_plan_save' );

function ml_handle_plan_save() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    check_admin_referer( 'ml_plan_save' );

    $action = sanitize_key( $_POST['plan_action'] ?? 'save' );

    // Save plan fields (amounts as cents).
    ml_plan_save_raw( array(
        'plan_name'                 => sanitize_text_field( wp_unslash( $_POST['plan_name']        ?? '' ) ),
        'plan_description'          => sanitize_textarea_field( wp_unslash( $_POST['plan_description'] ?? '' ) ),
        'plan_currency'             => strtolower( sanitize_text_field( wp_unslash( $_POST['plan_currency'] ?? 'eur' ) ) ),
        'plan_year_one_amount'      => ml_to_minor_units( wp_unslash( $_POST['plan_year_one_amount']     ?? '' ) ),
        'plan_monthly_amount'       => ml_to_minor_units( wp_unslash( $_POST['plan_monthly_amount']      ?? '' ) ),
        'plan_reactivation_amount'  => ml_to_minor_units( wp_unslash( $_POST['plan_reactivation_amount'] ?? '' ) ),
    ) );

    $redirect = admin_url( 'admin.php?page=memorylane-settings&tab=stripe' );

    if ( $action === 'sync' ) {
        $result = ml_plan_sync_to_stripe();
        if ( ! $result['ok'] ) {
            wp_safe_redirect( add_query_arg( 'ml_plan_err', rawurlencode( $result['error'] ?? 'sync failed' ), $redirect ) );
            exit;
        }
        $msg = __( 'Plan synced to Stripe.', 'memorylane' );
        if ( ! empty( $result['changes'] ) ) {
            $msg .= ' (' . implode( ', ', $result['changes'] ) . ')';
        }
        wp_safe_redirect( add_query_arg( 'ml_plan_msg', rawurlencode( $msg ), $redirect ) );
        exit;
    }

    wp_safe_redirect( add_query_arg( 'ml_plan_msg', rawurlencode( __( 'Plan saved (not yet synced).', 'memorylane' ) ), $redirect ) );
    exit;
}

/* ───────────────────────── OTHER TABS ───────────────────────── */

function ml_admin_settings_matterport() {
    if ( isset( $_POST['ml_save'] ) && check_admin_referer( 'ml_mp_save' ) ) {
        update_option( ML_OPT_EMBED_DOMAIN_ALLOW, sanitize_textarea_field( wp_unslash( $_POST['embed_domains'] ?? '' ) ), false );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'memorylane' ) . '</p></div>';
    }
    $val = get_option( ML_OPT_EMBED_DOMAIN_ALLOW, "my.matterport.com\nmatterport.com" );
    ?>
    <h2><?php esc_html_e( 'Matterport', 'memorylane' ); ?></h2>
    <p class="description"><?php esc_html_e( 'Memory Lane uses embed-only integration. Admin manually pastes the Matterport iframe code per tour. List allowed iframe domains below (one per line).', 'memorylane' ); ?></p>
    <form method="post">
        <?php wp_nonce_field( 'ml_mp_save' ); ?>
        <textarea name="embed_domains" rows="6" class="large-text code"><?php echo esc_textarea( $val ); ?></textarea>
        <p><button type="submit" name="ml_save" value="1" class="button button-primary"><?php esc_html_e( 'Save', 'memorylane' ); ?></button></p>
    </form>
    <?php
}

function ml_admin_settings_access() {
    if ( isset( $_POST['ml_save'] ) && check_admin_referer( 'ml_access_save' ) ) {
        update_option( ML_OPT_PAST_DUE_GRACE_DAYS, max( 0, (int) ( $_POST['grace_days'] ?? 7 ) ), false );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'memorylane' ) . '</p></div>';
    }
    $g = (int) get_option( ML_OPT_PAST_DUE_GRACE_DAYS, 7 );
    ?>
    <h2><?php esc_html_e( 'Access policy', 'memorylane' ); ?></h2>
    <form method="post">
        <?php wp_nonce_field( 'ml_access_save' ); ?>
        <table class="form-table"><tr><th><?php esc_html_e( 'Past-due grace (days)', 'memorylane' ); ?></th>
        <td><input type="number" name="grace_days" value="<?php echo esc_attr( $g ); ?>" min="0" max="60" class="small-text"></td></tr></table>
        <p><button type="submit" name="ml_save" value="1" class="button button-primary"><?php esc_html_e( 'Save', 'memorylane' ); ?></button></p>
    </form>
    <?php
}

function ml_admin_settings_booking() {
    if ( isset( $_POST['ml_save'] ) && check_admin_referer( 'ml_booking_save' ) ) {
        update_option( ML_OPT_BOOKING_RESCHED_HOURS, max( 0, (int) ( $_POST['resched_hours'] ?? 48 ) ), false );
        update_option( ML_OPT_BOOKING_CANCEL_HOURS,  max( 0, (int) ( $_POST['cancel_hours']  ?? 24 ) ), false );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'memorylane' ) . '</p></div>';
    }
    ?>
    <h2><?php esc_html_e( 'Booking rules', 'memorylane' ); ?></h2>
    <form method="post">
        <?php wp_nonce_field( 'ml_booking_save' ); ?>
        <table class="form-table">
            <tr><th><?php esc_html_e( 'Reschedule cutoff (hours before)', 'memorylane' ); ?></th>
            <td><input type="number" name="resched_hours" value="<?php echo esc_attr( get_option( ML_OPT_BOOKING_RESCHED_HOURS, 48 ) ); ?>" class="small-text"></td></tr>
            <tr><th><?php esc_html_e( 'Cancellation cutoff (hours before)', 'memorylane' ); ?></th>
            <td><input type="number" name="cancel_hours" value="<?php echo esc_attr( get_option( ML_OPT_BOOKING_CANCEL_HOURS, 24 ) ); ?>" class="small-text"></td></tr>
        </table>
        <p><button type="submit" name="ml_save" value="1" class="button button-primary"><?php esc_html_e( 'Save', 'memorylane' ); ?></button></p>
    </form>
    <?php
}

function ml_admin_settings_emails() {
    if ( isset( $_POST['ml_save'] ) && check_admin_referer( 'ml_email_save' ) ) {
        update_option( ML_OPT_EMAIL_FROM_NAME,    sanitize_text_field( wp_unslash( $_POST['from_name'] ?? 'Memory Lane' ) ), false );
        update_option( ML_OPT_EMAIL_FROM_ADDRESS, sanitize_email( wp_unslash( $_POST['from_email'] ?? '' ) ), false );
        update_option( ML_OPT_ADMIN_RECIPIENTS,   sanitize_text_field( wp_unslash( $_POST['admin_to'] ?? '' ) ), false );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'memorylane' ) . '</p></div>';
    }
    $from = ml_email_from();
    ?>
    <h2><?php esc_html_e( 'Emails', 'memorylane' ); ?></h2>
    <form method="post">
        <?php wp_nonce_field( 'ml_email_save' ); ?>
        <table class="form-table">
            <tr><th><?php esc_html_e( 'From name', 'memorylane' ); ?></th>
            <td><input type="text" name="from_name" value="<?php echo esc_attr( $from['name'] ); ?>" class="regular-text"></td></tr>
            <tr><th><?php esc_html_e( 'From email', 'memorylane' ); ?></th>
            <td><input type="email" name="from_email" value="<?php echo esc_attr( $from['address'] ); ?>" class="regular-text"></td></tr>
            <tr><th><?php esc_html_e( 'Admin recipients (comma separated)', 'memorylane' ); ?></th>
            <td><input type="text" name="admin_to" value="<?php echo esc_attr( get_option( ML_OPT_ADMIN_RECIPIENTS, get_option( 'admin_email' ) ) ); ?>" class="large-text"></td></tr>
        </table>
        <p><button type="submit" name="ml_save" value="1" class="button button-primary"><?php esc_html_e( 'Save', 'memorylane' ); ?></button></p>
    </form>
    <?php
}

function ml_admin_settings_general() {
    ?>
    <h2><?php esc_html_e( 'General', 'memorylane' ); ?></h2>
    <table class="form-table">
        <tr><th><?php esc_html_e( 'Theme version', 'memorylane' ); ?></th><td><code><?php echo esc_html( ML_VERSION ); ?></code></td></tr>
        <tr><th><?php esc_html_e( 'DB version', 'memorylane' ); ?></th><td><code><?php echo esc_html( get_option( ML_OPT_DB_VERSION, 'unset' ) ); ?></code></td></tr>
        <tr><th><?php esc_html_e( 'REST routes', 'memorylane' ); ?></th><td>
            <code><?php echo esc_html( rest_url( 'memorylane/v1/stripe-webhook' ) ); ?></code><br>
            <code><?php echo esc_html( rest_url( 'memorylane/v1/checkout' ) ); ?></code>
        </td></tr>
    </table>
    <?php
}
