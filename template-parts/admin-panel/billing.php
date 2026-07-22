<?php
/** Memory Lane admin — Stripe billing settings (Phase 1: inert config). */
defined( 'ABSPATH' ) || exit;

$mode       = ml_stripe_mode();
$plan       = ml_plan_get();
$configured = ml_stripe_is_configured();
$post_url   = esc_url( admin_url( 'admin-post.php' ) );
?>
<div class="mla-card">
    <h2>Stripe — <?php echo esc_html( ucfirst( $mode ) ); ?> mode
        <span class="mla-pill <?php echo $configured ? 'mla-pill--success' : 'mla-pill--warning'; ?>">
            <?php echo $configured ? 'Configured' : 'Not configured'; ?>
        </span>
    </h2>
    <form method="post" action="<?php echo $post_url; ?>">
        <?php wp_nonce_field( 'ml_ap_billing_save' ); ?>
        <input type="hidden" name="action" value="ml_ap_billing_save">
        <div class="mla-form-row">
            <label>Secret key</label>
            <div><input type="text" name="secret_key" value="<?php echo esc_attr( ml_stripe_opt( 'secret_key' ) ); ?>" placeholder="sk_test_…"></div>
        </div>
        <div class="mla-form-row">
            <label>Publishable key</label>
            <div><input type="text" name="publishable_key" value="<?php echo esc_attr( ml_stripe_opt( 'publishable_key' ) ); ?>" placeholder="pk_test_…"></div>
        </div>
        <div class="mla-form-row">
            <label>Webhook signing secret</label>
            <div><input type="text" name="webhook_secret" value="<?php echo esc_attr( ml_stripe_opt( 'webhook_secret' ) ); ?>" placeholder="whsec_…"></div>
        </div>
        <div class="mla-form-row">
            <label>Activation amount (€)</label>
            <div><input type="text" name="plan_year_one_amount" value="<?php echo esc_attr( ml_from_minor_units( $plan['activation_amount'] ) ); ?>" placeholder="299.00"></div>
        </div>
        <div class="mla-form-row">
            <label>Yearly renewal (€)</label>
            <div><input type="text" name="plan_annual_amount" value="<?php echo esc_attr( ml_from_minor_units( $plan['yearly_amount'] ) ); ?>" placeholder="99.00"></div>
        </div>
        <div style="margin-top:16px;"><button class="mla-btn mla-btn--primary" type="submit">Save</button></div>
    </form>
</div>

<div class="mla-card">
    <h2>Plan sync</h2>
    <p class="mla-muted">Push the amounts above to Stripe (creates the Product + Prices). Last synced:
        <?php echo $plan['synced_at'] ? esc_html( gmdate( 'Y-m-d H:i', $plan['synced_at'] ) . ' UTC' ) : 'never'; ?>.</p>
    <form method="post" action="<?php echo $post_url; ?>">
        <?php wp_nonce_field( 'ml_ap_billing_sync' ); ?>
        <input type="hidden" name="action" value="ml_ap_billing_sync">
        <button class="mla-btn mla-btn--secondary" type="submit" <?php echo ml_stripe_secret() ? '' : 'disabled'; ?>>Sync plan to Stripe</button>
    </form>
</div>
