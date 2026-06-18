<?php defined( 'ABSPATH' ) || exit;
$mode  = ml_stripe_mode();
$plan  = ml_plan_get();
$grace = (int) get_option( ML_OPT_PAST_DUE_GRACE_DAYS, 7 );
?>

<form class="mla-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <?php wp_nonce_field( 'ml_ap_settings_save' ); ?>
    <input type="hidden" name="action" value="ml_ap_settings_save">

    <h2>Stripe</h2>

    <div class="mla-form-row">
        <label>Mode</label>
        <div>
            <label style="margin-right:16px;"><input type="radio" name="stripe_mode" value="test" <?php checked( $mode, 'test' ); ?>> Test</label>
            <label><input type="radio" name="stripe_mode" value="live" <?php checked( $mode, 'live' ); ?>> Live</label>
        </div>
    </div>

    <?php foreach ( array( 'test', 'live' ) as $m ) :
        $pk = (string) get_option( "ml_stripe_{$m}_publishable_key", '' );
        $sk = (string) get_option( "ml_stripe_{$m}_secret_key",      '' );
        $wh = (string) get_option( "ml_stripe_{$m}_webhook_secret",  '' );
    ?>
        <h3 style="margin-top:16px;font-size:13px;text-transform:uppercase;letter-spacing:.04em;color:var(--mla-mut);"><?php echo strtoupper( $m ); ?> KEYS</h3>
        <div class="mla-form-row">
            <label>Publishable key</label>
            <div><input type="text" name="stripe_<?php echo $m; ?>_publishable_key" value="<?php echo esc_attr( $pk ); ?>" placeholder="pk_<?php echo $m; ?>_…"></div>
        </div>
        <div class="mla-form-row">
            <label>Secret key</label>
            <div><input type="text" name="stripe_<?php echo $m; ?>_secret_key" value="<?php echo esc_attr( $sk ? str_repeat( '•', 8 ) . substr( $sk, -4 ) : '' ); ?>" placeholder="sk_<?php echo $m; ?>_…">
                <div class="help">Leave blank to keep current. Showing last 4.</div>
            </div>
        </div>
        <div class="mla-form-row">
            <label>Webhook secret</label>
            <div><input type="text" name="stripe_<?php echo $m; ?>_webhook_secret" value="<?php echo esc_attr( $wh ? str_repeat( '•', 8 ) . substr( $wh, -4 ) : '' ); ?>" placeholder="whsec_…">
                <div class="help">Webhook endpoint: <code style="font-size:11px;"><?php echo esc_html( rest_url( 'memorylane/v1/stripe-webhook' ) ); ?></code></div>
            </div>
        </div>
    <?php endforeach; ?>

    <h2 style="margin-top:24px;">Prices</h2>
    <div class="mla-form-row">
        <label>Product name</label>
        <div><input type="text" name="plan_name" value="<?php echo esc_attr( $plan['product_name'] ); ?>" placeholder="Memory Lane"></div>
    </div>
    <div class="mla-form-row">
        <label>Description</label>
        <div><textarea name="plan_description" rows="2"><?php echo esc_textarea( $plan['product_description'] ); ?></textarea></div>
    </div>
    <div class="mla-form-row">
        <label>Currency</label>
        <div>
            <select name="plan_currency">
                <?php foreach ( array( 'eur' => 'EUR — Euro', 'usd' => 'USD — US Dollar', 'gbp' => 'GBP — British Pound' ) as $code => $lab ) : ?>
                    <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $plan['currency'], $code ); ?>><?php echo esc_html( $lab ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mla-form-row">
        <label>Setup + Year 1</label>
        <div><input type="text" name="plan_year_one_amount" value="<?php echo esc_attr( $plan['year_one_amount'] ? ml_from_minor_units( $plan['year_one_amount'] ) : '' ); ?>" placeholder="299.00" style="max-width:160px;"></div>
    </div>
    <div class="mla-form-row">
        <label>Monthly hosting</label>
        <div><input type="text" name="plan_monthly_amount" value="<?php echo esc_attr( $plan['monthly_amount'] ? ml_from_minor_units( $plan['monthly_amount'] ) : '' ); ?>" placeholder="9.00" style="max-width:160px;"></div>
    </div>
    <div class="mla-form-row">
        <label>Annual hosting (reactivation)</label>
        <div><input type="text" name="plan_annual_amount" value="<?php echo esc_attr( $plan['annual_amount'] ? ml_from_minor_units( $plan['annual_amount'] ) : '' ); ?>" placeholder="99.00" style="max-width:160px;">
            <div class="help">Optional. Leave blank to disable.</div>
        </div>
    </div>
    <div class="mla-form-row">
        <label>Matterport activation fee</label>
        <div><input type="text" name="plan_reactivation_amount" value="<?php echo esc_attr( $plan['reactivation_amount'] ? ml_from_minor_units( $plan['reactivation_amount'] ) : '' ); ?>" placeholder="49.00" style="max-width:160px;">
            <div class="help">Charged at first purchase and at every reactivation.</div>
        </div>
    </div>

    <h2 style="margin-top:24px;">Subscription behaviour</h2>
    <div class="mla-form-row">
        <label>Past-due grace (days)</label>
        <div><input type="number" name="grace_days" value="<?php echo (int) $grace; ?>" min="0" max="60" style="max-width:120px;">
            <div class="help">After Stripe gives up retrying, how many days before we archive the tour.</div>
        </div>
    </div>
    <h2 style="margin-top:24px;">Booking</h2>
    <div class="mla-form-row">
        <label>Require online payment</label>
        <div>
            <label><input type="checkbox" name="booking_require_payment" value="1" <?php checked( ml_booking_payment_required() ); ?>> Send new bookings through Stripe Checkout</label>
            <div class="help">Off = visitors book on /boek for free (no price shown, no payment). On = the current paid flow.</div>
        </div>
    </div>
    <div class="mla-form-row">
        <label>Booking cancel notice (hours)</label>
        <div><input type="number" name="cancel_hours" value="<?php echo (int) get_option( ML_OPT_BOOKING_CANCEL_HOURS, 24 ); ?>" min="0" style="max-width:120px;"></div>
    </div>
    <div class="mla-form-row">
        <label>Booking reschedule (hours)</label>
        <div><input type="number" name="reschedule_hours" value="<?php echo (int) get_option( ML_OPT_BOOKING_RESCHED_HOURS, 24 ); ?>" min="0" style="max-width:120px;"></div>
    </div>

    <h2 style="margin-top:24px;">Notifications</h2>
    <div class="mla-form-row">
        <label>Admin recipients</label>
        <div><input type="text" name="admin_recipients" value="<?php echo esc_attr( (string) get_option( ML_OPT_ADMIN_RECIPIENTS, get_option( 'admin_email' ) ) ); ?>" placeholder="alice@…, bob@…">
            <div class="help">Comma-separated.</div>
        </div>
    </div>
    <div class="mla-form-row">
        <label>From name</label>
        <div><input type="text" name="email_from_name" value="<?php echo esc_attr( (string) get_option( ML_OPT_EMAIL_FROM_NAME, 'Memory Lane' ) ); ?>"></div>
    </div>
    <div class="mla-form-row">
        <label>From email</label>
        <div><input type="email" name="email_from_address" value="<?php echo esc_attr( (string) get_option( ML_OPT_EMAIL_FROM_ADDRESS, '' ) ); ?>" placeholder="no-reply@yourdomain.com"></div>
    </div>

    <h2 style="margin-top:24px;">Embed allowlist</h2>
    <div class="mla-form-row">
        <label>Allowed iframe domains</label>
        <div><textarea name="embed_allowlist" rows="3"><?php echo esc_textarea( (string) get_option( ML_OPT_EMBED_DOMAIN_ALLOW, "my.matterport.com\nmatterport.com" ) ); ?></textarea>
            <div class="help">One domain per line. Only iframes from these domains are rendered.</div>
        </div>
    </div>

    <div style="margin-top:24px;display:flex;gap:8px;">
        <button class="mla-btn mla-btn--primary" type="submit">Save</button>
        <button class="mla-btn mla-btn--secondary" type="submit" name="sync_with_stripe" value="1">Save + sync prices to Stripe</button>
    </div>
</form>
