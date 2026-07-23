# Payment Restore — Phase 1: Stripe plumbing + settings (inert) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restore the Stripe PHP SDK, a trimmed Stripe client, and a two-price plan-sync (one-time activation + recurring yearly), plus a `/admin` Billing settings section — with **no change to site behaviour** until keys are entered.

**Architecture:** Selective restore from git commit `60f1a65^` (not a revert). Reuse the existing `config.php` helpers (`ml_stripe_opt`, `ml_stripe_mode`) which survived the strip. Everything is inert: booking stays no-payment; this phase only makes Stripe *configurable and sync-able*.

**Tech Stack:** PHP 8 / WordPress theme, vendored `stripe-php` SDK, custom `/admin` panel. No PHPUnit in the repo → pure functions get standalone `php`-CLI unit scripts; WP-integrated code is verified with `php -l` + documented manual checks.

**Spec:** `docs/superpowers/specs/2026-07-22-payment-subscription-teamleader-invoicing-design.md`

---

### Task 1: Restore the Stripe PHP SDK

**Files:**
- Restore: `inc/stripe-php-master/` (≈450 files, from `60f1a65^`)

- [ ] **Step 1: Restore the SDK directory from git history**

Run:
```bash
git checkout 60f1a65^ -- inc/stripe-php-master
```

- [ ] **Step 2: Verify the entry point and main client class exist**

Run:
```bash
test -f inc/stripe-php-master/init.php && echo "init OK"
test -f inc/stripe-php-master/lib/StripeClient.php && echo "client OK"
php -l inc/stripe-php-master/init.php
```
Expected: `init OK`, `client OK`, and `No syntax errors detected`.

- [ ] **Step 3: Confirm the SDK autoloads in isolation**

Create a throwaway check and run it:
```bash
php -r "define('ABSPATH',1); require 'inc/stripe-php-master/init.php'; echo class_exists('\\Stripe\\StripeClient') ? 'AUTOLOAD OK' : 'FAIL';"
```
Expected: `AUTOLOAD OK`.

- [ ] **Step 4: Commit**

```bash
git add inc/stripe-php-master
git commit -m "feat(stripe): restore vendored stripe-php SDK from history"
```

---

### Task 2: Restore + trim the Stripe client

**Files:**
- Create: `inc/stripe/client.php` (restored from `60f1a65^`, then trimmed)

The old client had helpers for monthly + reactivation prices we no longer use. Keep only activation (`setup_price_id`) + yearly (`annual_price_id`).

- [ ] **Step 1: Restore the old client file**

Run:
```bash
git checkout 60f1a65^ -- inc/stripe/client.php
```

- [ ] **Step 2: Trim to the activation + yearly model**

In `inc/stripe/client.php`:
- **Delete** the functions `ml_stripe_monthly_price_id()` and `ml_stripe_reactivation_price_id()`.
- **Replace** `ml_stripe_is_configured()` body so it no longer requires monthly/reactivation prices:

```php
function ml_stripe_is_configured() {
    return (bool) (
        ml_stripe_secret()
        && ml_stripe_publishable()
        && ml_stripe_setup_price_id()
        && ml_stripe_annual_price_id()
    );
}
```
- Require the SDK at the top of the file (right after the `defined( 'ABSPATH' )` guard):

```php
require_once get_template_directory() . '/inc/stripe-php-master/init.php';
```

- [ ] **Step 3: Lint**

Run: `php -l inc/stripe/client.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Grep to confirm no dangling references to the removed helpers**

Run:
```bash
grep -rn "ml_stripe_monthly_price_id\|ml_stripe_reactivation_price_id" inc template-parts || echo "no dangling refs"
```
Expected: `no dangling refs`.

- [ ] **Step 5: Commit**

```bash
git add inc/stripe/client.php
git commit -m "feat(stripe): restore + trim client to activation + yearly"
```

---

### Task 3: Restore + trim the plan sync, with a real unit test for money conversion

**Files:**
- Create: `inc/stripe/plans.php` (restored, trimmed to 2 prices)
- Test: `tests/stripe/money-units.test.php` (standalone PHP unit script)

- [ ] **Step 1: Write the failing unit test for the money-conversion helpers**

Create `tests/stripe/money-units.test.php`:
```php
<?php
// Standalone unit test — runs without WordPress.
define( 'ABSPATH', __DIR__ );
require __DIR__ . '/../../inc/stripe/plans.php';

$fail = 0;
function check( $label, $got, $want ) {
    global $fail;
    if ( $got === $want ) { echo "PASS  $label\n"; }
    else { echo "FAIL  $label — got " . var_export( $got, true ) . " want " . var_export( $want, true ) . "\n"; $GLOBALS['fail']++; }
}

check( 'euros to cents',        ml_to_minor_units( '299.00' ), 29900 );
check( 'comma decimal',         ml_to_minor_units( '9,50' ),    950 );
check( 'blank is zero',         ml_to_minor_units( '' ),          0 );
check( 'cents to euros string', ml_from_minor_units( 29900 ),  '299.00' );

exit( $fail === 0 ? 0 : 1 );
```

- [ ] **Step 2: Run it to verify it fails (file not restored yet)**

Run: `php tests/stripe/money-units.test.php`
Expected: FAIL — `require`d `plans.php` does not exist yet (fatal error / no such file).

- [ ] **Step 3: Restore the plan-sync file**

Run:
```bash
git checkout 60f1a65^ -- inc/stripe/plans.php
```

- [ ] **Step 4: Trim the plan model to two prices**

In `inc/stripe/plans.php`, inside `ml_plan_get()`:
- **Remove** the `monthly_amount`, `annual_amount`, `monthly_price_id`, and `reactivation_amount` / `reactivation_price_id` array keys, and **rename** the recurring price to yearly. Final array:

```php
return array(
    'product_name'        => ml_stripe_opt( 'plan_name',        'Memory Lane' ),
    'product_description' => ml_stripe_opt( 'plan_description', '' ),
    'currency'            => strtolower( ml_stripe_opt( 'plan_currency', 'eur' ) ),
    'activation_amount'   => (int) ml_stripe_opt( 'plan_year_one_amount', 0 ),
    'yearly_amount'       => (int) ml_stripe_opt( 'plan_annual_amount',   0 ),
    'product_id'          => ml_stripe_opt( 'product_id',       '' ),
    'setup_price_id'      => ml_stripe_opt( 'setup_price_id',   '' ),
    'annual_price_id'     => ml_stripe_opt( 'annual_price_id',  '' ),
    'synced_at'           => (int) ml_stripe_opt( 'plan_synced_at', 0 ),
);
```
- In `ml_plan_sync_to_stripe()`, **keep** the Product block and the activation (one-time `setup_price_id`) block. **Replace** the "Monthly recurring price" block with a **yearly recurring** price and **delete** the reactivation-price block:

```php
// Yearly recurring price.
if ( $plan['yearly_amount'] > 0 ) {
    $price_id = ml_plan_ensure_price(
        $stripe, $product_id, $plan['annual_price_id'],
        $plan['yearly_amount'], $plan['currency'],
        array( 'interval' => 'year' ),   // recurring yearly
        'Memory Lane — Yearly'
    );
    if ( $price_id !== $plan['annual_price_id'] ) {
        ml_plan_save_raw( array( 'annual_price_id' => $price_id ) );
        $changes[] = 'annual_price:created';
    } else {
        $changes[] = 'annual_price:unchanged';
    }
}
```
Leave `ml_to_minor_units()` / `ml_from_minor_units()` / `ml_plan_ensure_price()` unchanged.

- [ ] **Step 5: Run the unit test to verify it passes**

Run: `php tests/stripe/money-units.test.php`
Expected: four `PASS` lines, exit 0.

- [ ] **Step 6: Lint**

Run: `php -l inc/stripe/plans.php`
Expected: `No syntax errors detected`.

- [ ] **Step 7: Commit**

```bash
git add inc/stripe/plans.php tests/stripe/money-units.test.php
git commit -m "feat(stripe): restore + trim plan sync to activation + yearly; unit test money conversion"
```

---

### Task 4: Wire the Stripe files into the bootstrap loader

**Files:**
- Modify: `inc/bootstrap.php` (add requires after the Teamleader block, before admin-panel)

- [ ] **Step 1: Add the require lines**

In `inc/bootstrap.php`, immediately after the Teamleader block (the line `require_once __DIR__ . '/teamleader/settings.php';`), insert:

```php

// Payments (Stripe) — inert until keys are entered.
require_once __DIR__ . '/stripe/client.php';
require_once __DIR__ . '/stripe/plans.php';
```

- [ ] **Step 2: Lint the bootstrap**

Run: `php -l inc/bootstrap.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Verify no fatal on a WordPress load**

Manual: open the site home page and `/admin` in a browser (logged in as admin). Expected: both render normally, no white screen, no "Fatal error" in the PHP error log. The site behaves exactly as before (still no payment in `/boek`).

- [ ] **Step 4: Commit**

```bash
git add inc/bootstrap.php
git commit -m "feat(stripe): load stripe client + plans in bootstrap (inert)"
```

---

### Task 5: `/admin` Billing settings section (keys + amounts + Sync to Stripe)

**Files:**
- Create: `template-parts/admin-panel/billing.php` (settings form)
- Modify: `template-parts/admin-panel/shell.php` (add "Billing" nav item + icon + title)
- Modify: `inc/admin-panel/handlers.php` (save keys/amounts; handle "sync plan" action)

- [ ] **Step 1: Add the Billing nav item + title to the admin shell**

In `template-parts/admin-panel/shell.php`, add to the `$nav` array (after `tours`):
```php
    'billing'  => array( 'label' => 'Billing',  'icon' => 'card'     ),
```
and to the `$titles` array:
```php
    'billing'  => 'Billing',
```
(The `card` icon already exists in `ml_ap_icon()`.)

- [ ] **Step 2: Create the Billing settings template**

Create `template-parts/admin-panel/billing.php`:
```php
<?php
/** Memory Lane admin — Stripe billing settings (Phase 1: inert config). */
defined( 'ABSPATH' ) || exit;

$mode        = ml_stripe_mode();
$plan        = ml_plan_get();
$configured  = ml_stripe_is_configured();
$post_url    = esc_url( admin_url( 'admin-post.php' ) );
?>
<div class="mla-card">
    <h2>Stripe — <?php echo esc_html( ucfirst( $mode ) ); ?> mode
        <span class="mla-pill <?php echo $configured ? 'mla-pill--success' : 'mla-pill--warning'; ?>">
            <?php echo $configured ? 'Configured' : 'Not configured'; ?>
        </span>
    </h2>
    <form method="post" action="<?php echo $post_url; ?>">
        <?php wp_nonce_field( 'ml_billing_save' ); ?>
        <input type="hidden" name="action" value="ml_billing_save">
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
        <?php wp_nonce_field( 'ml_billing_sync' ); ?>
        <input type="hidden" name="action" value="ml_billing_sync">
        <button class="mla-btn mla-btn--secondary" type="submit" <?php echo ml_stripe_secret() ? '' : 'disabled'; ?>>Sync plan to Stripe</button>
    </form>
</div>
```

- [ ] **Step 3: Add the save + sync handlers**

In `inc/admin-panel/handlers.php`, append two `admin_post` handlers (match the file's existing handler style — capability check `ML_CAP_MANAGE`, nonce verify, redirect back with `?msg=`):
```php
add_action( 'admin_post_ml_billing_save', function () {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die( 'forbidden', 403 );
    check_admin_referer( 'ml_billing_save' );
    $mode = ml_stripe_mode();
    foreach ( array( 'secret_key', 'publishable_key', 'webhook_secret' ) as $k ) {
        update_option( "ml_stripe_{$mode}_{$k}", sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) ), false );
    }
    ml_plan_save_raw( array(
        'plan_year_one_amount' => ml_to_minor_units( wp_unslash( $_POST['plan_year_one_amount'] ?? '' ) ),
        'plan_annual_amount'   => ml_to_minor_units( wp_unslash( $_POST['plan_annual_amount'] ?? '' ) ),
    ) );
    wp_safe_redirect( home_url( '/admin/billing?msg=saved' ) );
    exit;
} );

add_action( 'admin_post_ml_billing_sync', function () {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die( 'forbidden', 403 );
    check_admin_referer( 'ml_billing_sync' );
    $res = ml_plan_sync_to_stripe();
    update_option( "ml_stripe_" . ml_stripe_mode() . "_plan_synced_at", time(), false );
    wp_safe_redirect( home_url( '/admin/billing?msg=' . ( $res['ok'] ? 'synced' : 'sync_failed' ) ) );
    exit;
} );
```

- [ ] **Step 4: Lint the changed PHP**

Run:
```bash
php -l template-parts/admin-panel/billing.php && php -l template-parts/admin-panel/shell.php && php -l inc/admin-panel/handlers.php
```
Expected: `No syntax errors detected` for each.

- [ ] **Step 5: Manual verification (test mode)**

1. Open `/admin/billing` — the Billing page renders in-theme with a "Not configured" pill.
2. Paste Stripe **test** keys + amounts, Save → redirects with `?msg=saved`, values persist, pill turns "Configured".
3. Click **Sync plan to Stripe** → redirects `?msg=synced`; in the Stripe test dashboard a "Memory Lane" Product with two Prices (one-time activation + yearly) appears.
4. Confirm `/boek` still behaves exactly as before (no payment step) — Phase 1 is inert.

- [ ] **Step 6: Commit**

```bash
git add template-parts/admin-panel/billing.php template-parts/admin-panel/shell.php inc/admin-panel/handlers.php
git commit -m "feat(admin): Stripe billing settings + plan sync section (inert)"
```

---

## Self-review notes

- **Spec coverage (Phase 1 rows only):** SDK restore (T1), trimmed client (T2), two-price plan sync (T3), bootstrap wiring (T4), admin plan/keys settings (T5). Checkout, webhooks, access-gate, Teamleader invoicing, dashboard/admin subscription UI are **Phases 2–4** (separate plans).
- **No placeholders:** every code step shows the exact code; every verify step shows the command + expected output.
- **Type consistency:** option keys reused verbatim — activation stored under the legacy `plan_year_one_amount` / `setup_price_id`, yearly under `plan_annual_amount` / `annual_price_id` (matches the surviving `ml_stripe_opt` naming so no migration).
- **Inert guarantee:** nothing calls the new code from the booking path in this phase; `ml_booking_payment_required()` still returns its stored value (0) and the current no-payment `boek-checkout.php` is untouched.
