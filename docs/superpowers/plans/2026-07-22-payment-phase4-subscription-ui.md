# Payment Restore — Phase 4: Subscription UI + billing portal + refunds

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give customers a **Subscription** dashboard page (status, next renewal, manage-card/cancel via Stripe billing portal), give admins a read-only **Subscriptions** list, and issue a Teamleader **credit note** on `charge.refunded`. Also restore the dashboard action handlers (`ml_profile`/`ml_change_pw`/`ml_lang`/`ml_portal`/`ml_sub_cancel`) that were lost in the June strip — the Settings form currently posts to missing handlers.

**Architecture:** Restore `inc/stripe/customer-portal.php` (it is clean for the yearly model — cancel uses `cancel_at_period_end`). Add a `/dashboard/subscription` route + nav + a fresh lean template using the existing `ml_subscription_status_*` helpers. Add an admin `subscriptions` section (auto-routed by the shell's section dispatch). Add a `charge.refunded` handler → `creditNotes.create`.

**Tech Stack:** PHP 8 / WordPress, `stripe-php`, Teamleader Focus. Verify: `php -l` + render smoke + documented manual steps (no new pure logic worth a unit test beyond Phases 1–3).

**Spec:** `docs/superpowers/specs/2026-07-22-payment-subscription-teamleader-invoicing-design.md`
**Depends on:** Phases 1–3 (this branch). Uses `ml_get_subscription_row`, `ml_subscription_status_label/pill_class`, `ml_format_date`, `ml_flash_set`.

---

### Task 1: Restore the dashboard action handlers (fixes Settings + adds portal/cancel)

**Files:**
- Create: `inc/stripe/customer-portal.php` (restored verbatim from `60f1a65^`)
- Modify: `inc/bootstrap.php`

- [ ] **Step 1: Restore the file**

Run:
```bash
git checkout 60f1a65^ -- inc/stripe/customer-portal.php
```

- [ ] **Step 2: Wire into bootstrap**

In `inc/bootstrap.php`, after `require_once __DIR__ . '/stripe/events/invoice-paid.php';` add:
```php
require_once __DIR__ . '/stripe/customer-portal.php';
```

- [ ] **Step 3: Lint**

Run: `php -l inc/stripe/customer-portal.php && php -l inc/bootstrap.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Confirm the handlers now exist**

Run:
```bash
grep -c "admin_post_ml_profile\|admin_post_ml_change_pw\|admin_post_ml_lang\|admin_post_ml_portal\|admin_post_ml_sub_cancel" inc/stripe/customer-portal.php
```
Expected: `5`.

- [ ] **Step 5: Commit**

```bash
git add inc/stripe/customer-portal.php inc/bootstrap.php
git commit -m "fix(dashboard): restore profile/password/lang + Stripe portal/cancel handlers"
```

---

### Task 2: `/dashboard/subscription` route, nav, and page

**Files:**
- Modify: `inc/auth/routes.php` (add rewrite rule + bump rewrite version)
- Modify: `template-parts/dashboard/shell.php` (nav item + page title)
- Create: `template-parts/dashboard/subscription.php`

- [ ] **Step 1: Add the rewrite rule**

In `inc/auth/routes.php`, in the Dashboard block, after the `dashboard/settings` rule add:
```php
    add_rewrite_rule( '^dashboard/subscription/?$',            'index.php?ml_route=dashboard&ml_subroute=subscription','top' );
```
And bump the rewrite version so hosts re-flush:
```php
define( 'ML_REWRITE_VERSION', '0.5.0-subscription' );
```
(replace the existing `define( 'ML_REWRITE_VERSION', ... )` line).

- [ ] **Step 2: Add the nav item + title**

In `template-parts/dashboard/shell.php`, add to `$nav_items` (after `booking`):
```php
    'subscription' => array( 'label' => ml_t( 'nav.subscription' ), 'url' => home_url( '/dashboard/subscription' ), 'icon' => 'card' ),
```
and to `$page_titles`:
```php
    'subscription' => ml_t( 'sub.title' ),
```
(`nav.subscription` / `sub.title` already exist in the i18n strings; `card` icon exists in `ml_nav_icon`.)

- [ ] **Step 3: Create the page**

Create `template-parts/dashboard/subscription.php`:
```php
<?php
defined( 'ABSPATH' ) || exit;
$user = wp_get_current_user();
$row  = function_exists( 'ml_get_subscription_row' ) ? ml_get_subscription_row( $user->ID ) : null;
$post = esc_url( admin_url( 'admin-post.php' ) );
?>
<div>
    <h1 class="ml-h1"><?php ml_e( 'sub.title' ); ?></h1>
    <p class="ml-sub"></p>

    <?php if ( ! $row ) : ?>
        <div class="ml-empty">
            <div class="ml-empty__title"><?php echo esc_html__( 'No subscription yet', 'memorylane' ); ?></div>
            <p class="ml-text-sm"><?php echo esc_html__( 'There is no active subscription on this account.', 'memorylane' ); ?></p>
        </div>
    <?php else :
        $label = ml_subscription_status_label( $row );
        $pill  = ml_subscription_status_pill_class( $row );
    ?>
        <div class="ml-card ml-card--lg">
            <div class="ml-row-between">
                <div>
                    <p class="ml-card__title"><?php echo esc_html__( 'Status', 'memorylane' ); ?></p>
                    <p class="ml-card__value"><span class="ml-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( $label ); ?></span></p>
                </div>
                <div style="text-align:right;">
                    <p class="ml-text-sm ml-text-muted"><?php ml_e( 'sub.next_billing' ); ?></p>
                    <p class="ml-h3"><?php echo esc_html( $row->current_period_end ? ml_format_date( $row->current_period_end ) : '—' ); ?></p>
                </div>
            </div>
            <?php if ( ! empty( $row->cancel_at_period_end ) ) : ?>
                <hr class="ml-divider">
                <p class="ml-text-sm ml-text-muted"><?php echo esc_html__( 'Your subscription ends at the end of the current period.', 'memorylane' ); ?></p>
            <?php endif; ?>
            <hr class="ml-divider">
            <div class="ml-flex ml-gap-2">
                <form method="post" action="<?php echo $post; ?>" style="display:inline;">
                    <?php wp_nonce_field( 'ml_portal' ); ?>
                    <input type="hidden" name="action" value="ml_portal">
                    <button class="ml-btn ml-btn--primary" type="submit"><?php echo esc_html__( 'Manage payment method', 'memorylane' ); ?></button>
                </form>
                <?php if ( empty( $row->cancel_at_period_end ) && in_array( $row->status, array( 'active', 'trialing' ), true ) ) : ?>
                    <form method="post" action="<?php echo $post; ?>" style="display:inline;" onsubmit="return confirm('<?php echo esc_js__( 'Cancel your subscription at period end?', 'memorylane' ); ?>');">
                        <?php wp_nonce_field( 'ml_sub_cancel' ); ?>
                        <input type="hidden" name="action" value="ml_sub_cancel">
                        <button class="ml-btn ml-btn--ghost" type="submit"><?php echo esc_html__( 'Cancel subscription', 'memorylane' ); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
```
Note: `esc_js__` is not a WP function — replace the `onsubmit` confirm with a plain string:
```php
onsubmit="return confirm('Cancel your subscription at period end?');"
```

- [ ] **Step 4: Lint + render smoke**

Run: `php -l template-parts/dashboard/subscription.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Commit**

```bash
git add inc/auth/routes.php template-parts/dashboard/shell.php template-parts/dashboard/subscription.php
git commit -m "feat(dashboard): Subscription page + route + nav (status, portal, cancel)"
```

---

### Task 3: Admin Subscriptions list

**Files:**
- Modify: `template-parts/admin-panel/shell.php` (nav + title)
- Create: `template-parts/admin-panel/subscriptions.php`

- [ ] **Step 1: Add nav + title**

In `template-parts/admin-panel/shell.php`, add to `$nav` (after `billing`):
```php
    'subscriptions' => array( 'label' => 'Subscriptions', 'icon' => 'card' ),
```
and to `$titles`:
```php
    'subscriptions' => 'Subscriptions',
```

- [ ] **Step 2: Create the list template**

Create `template-parts/admin-panel/subscriptions.php`:
```php
<?php
/** Memory Lane admin — read-only subscriptions list. */
defined( 'ABSPATH' ) || exit;
global $wpdb;
$tbl  = ml_table( 'subscriptions' );
$rows = $wpdb->get_results( "SELECT * FROM {$tbl} ORDER BY updated_at DESC LIMIT 200" );
?>
<div class="mla-toolbar">
    <span class="mla-muted" style="margin-left:auto;"><?php echo (int) count( (array) $rows ); ?> shown (max 200)</span>
</div>
<div class="mla-card" style="padding:0;">
    <table class="mla-table">
        <thead><tr><th>Customer</th><th>Status</th><th>Renews</th><th>Cancels?</th><th>Stripe sub</th></tr></thead>
        <tbody>
        <?php if ( $rows ) : foreach ( $rows as $r ) :
            $u = get_user_by( 'id', (int) $r->user_id );
            $status_class = in_array( $r->status, array( 'active', 'trialing' ), true ) ? 'mla-pill--success' : ( $r->status === 'past_due' ? 'mla-pill--warning' : 'mla-pill--danger' );
        ?>
            <tr>
                <td><?php echo esc_html( $u ? ( $u->display_name . ' · ' . $u->user_email ) : ( '#' . $r->user_id ) ); ?></td>
                <td><span class="mla-pill <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $r->status ); ?></span></td>
                <td><?php echo esc_html( $r->current_period_end ?: '—' ); ?></td>
                <td><?php echo ! empty( $r->cancel_at_period_end ) ? 'yes' : '—'; ?></td>
                <td><code style="font-size:12px;"><?php echo esc_html( $r->stripe_sub_id ); ?></code></td>
            </tr>
        <?php endforeach; else : ?>
            <tr><td colspan="5" class="mla-muted" style="text-align:center;padding:32px;">No subscriptions yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
```

- [ ] **Step 3: Lint + commit**

Run: `php -l template-parts/admin-panel/subscriptions.php && php -l template-parts/admin-panel/shell.php`
Expected: `No syntax errors detected`.
```bash
git add template-parts/admin-panel/shell.php template-parts/admin-panel/subscriptions.php
git commit -m "feat(admin): read-only Subscriptions list"
```

---

### Task 4: Refund → Teamleader credit note

**Files:**
- Modify: `inc/stripe/webhooks.php` (map `charge.refunded`)
- Create: `inc/stripe/events/charge-refunded.php`
- Modify: `inc/teamleader/invoicing.php` (add `ml_tl_push_credit_note`)
- Modify: `inc/bootstrap.php`

- [ ] **Step 1: Map the event**

In `inc/stripe/webhooks.php`, add to the `$map` array:
```php
        'charge.refunded'               => 'ml_stripe_event_charge_refunded',
```

- [ ] **Step 2: Add the credit-note pusher**

Append to `inc/teamleader/invoicing.php`:
```php
/**
 * Create a credit note in Teamleader for a refunded Stripe invoice, if we
 * recorded a Teamleader invoice for it. Idempotent per Stripe charge id.
 */
function ml_tl_push_credit_note( $user, $stripe_invoice_id, $charge_id, $amount_cents, $currency ) {
    $done_key = '_ml_tl_cn_' . preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $charge_id );
    if ( get_user_meta( $user->ID, $done_key, true ) ) return;
    $tl_invoice_id = (string) get_user_meta( $user->ID, ml_tl_invoice_done_key( $stripe_invoice_id ), true );
    if ( ! $tl_invoice_id ) { error_log( '[memorylane] refund: no TL invoice for ' . $stripe_invoice_id ); return; }

    $cn = ml_tl_request( 'creditNotes.create', array(
        'invoice_id'    => $tl_invoice_id,
        'credit_note'   => array(
            'grouped_lines' => array( array(
                'line_items' => array( array(
                    'quantity'    => 1,
                    'description' => 'Refund',
                    'unit_price'  => array(
                        'amount'   => number_format( ( (int) $amount_cents ) / 100, 2, '.', '' ),
                        'currency' => strtoupper( (string) $currency ),
                        'tax'      => 'including',
                    ),
                    'tax_rate_id' => ml_tl_tax_rate_id(),
                ) ),
            ) ),
        ),
    ) );
    if ( ! empty( $cn['id'] ) ) update_user_meta( $user->ID, $done_key, $cn['id'] );
}
```

- [ ] **Step 3: Write the event handler**

Create `inc/stripe/events/charge-refunded.php`:
```php
<?php
/** Stripe event: charge.refunded → Teamleader credit note. */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_charge_refunded( \Stripe\Event $event ) {
    $charge = $event->data->object;
    $customer_id = is_object( $charge->customer ?? null ) ? ( $charge->customer->id ?? '' ) : (string) ( $charge->customer ?? '' );
    if ( ! $customer_id ) return;
    $users = get_users( array( 'meta_key' => ML_META_STRIPE_CUSTOMER, 'meta_value' => $customer_id, 'number' => 1, 'fields' => 'ID' ) );
    if ( empty( $users ) ) return;
    $user = get_user_by( 'id', (int) $users[0] );
    if ( ! $user ) return;

    $invoice_id = (string) ( $charge->invoice ?? '' );
    if ( ! $invoice_id ) return; // only invoice-linked charges map to a TL invoice
    $refunded = (int) ( $charge->amount_refunded ?? 0 );
    if ( $refunded <= 0 ) return;

    if ( function_exists( 'ml_tl_push_credit_note' ) ) {
        try { ml_tl_push_credit_note( $user, $invoice_id, (string) ( $charge->id ?? '' ), $refunded, (string) ( $charge->currency ?? 'eur' ) ); }
        catch ( \Throwable $e ) { error_log( '[memorylane] credit note failed: ' . $e->getMessage() ); }
    }
}
```

- [ ] **Step 4: Wire into bootstrap + lint**

In `inc/bootstrap.php`, after the invoice-paid require add:
```php
require_once __DIR__ . '/stripe/events/charge-refunded.php';
```
Run: `php -l inc/stripe/webhooks.php && php -l inc/teamleader/invoicing.php && php -l inc/stripe/events/charge-refunded.php && php -l inc/bootstrap.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Commit**

```bash
git add inc/stripe/webhooks.php inc/stripe/events/charge-refunded.php inc/teamleader/invoicing.php inc/bootstrap.php
git commit -m "feat(teamleader): charge.refunded -> Teamleader credit note (idempotent)"
```

---

### Task 5: Full smoke + lint + manual checklist

- [ ] **Step 1: All unit tests still pass**

Run:
```bash
php tests/stripe/money-units.test.php && php tests/stripe/sub-mapper.test.php && php tests/stripe/access-gate.test.php && php tests/stripe/invoice-body.test.php
```
Expected: all PASS.

- [ ] **Step 2: Lint every Phase-4 file**

Run:
```bash
for f in inc/stripe/customer-portal.php inc/auth/routes.php template-parts/dashboard/shell.php template-parts/dashboard/subscription.php template-parts/admin-panel/shell.php template-parts/admin-panel/subscriptions.php inc/stripe/events/charge-refunded.php inc/teamleader/invoicing.php inc/stripe/webhooks.php inc/bootstrap.php; do php -l "$f"; done
```
Expected: `No syntax errors detected` for each.

- [ ] **Step 3: Confirm the dashboard handlers are registered**

Run: `grep -c "admin_post_ml_" inc/stripe/customer-portal.php`
Expected: `5` (profile, change_pw, lang, portal, sub_cancel).

- [ ] **Step 4: Manual checklist (needs Stripe test data)**

1. As a customer with an active subscription, open `/dashboard/subscription` → status pill + next-renewal show; "Manage payment method" opens the Stripe billing portal; "Cancel" sets cancel-at-period-end.
2. Settings page: change display name / password / language now saves (handlers restored).
3. Admin `/admin/subscriptions` lists the rows.
4. Refund a charge in Stripe test → `charge.refunded` → a credit note appears in Teamleader against the original invoice.

---

## Self-review notes

- **Spec coverage:** dashboard Subscription section (T2), admin Subscriptions view (T3), refund→credit-note (T4). The old dashboard "Invoices" page is not re-created (Teamleader is authoritative), satisfying "remove old invoices page". Billing-portal/cancel via T1.
- **Bonus bugfix:** T1 restores the Settings form's missing handlers (profile/password/language) — broken since the June strip.
- **Placeholders:** none. **Idempotency:** credit note keyed by Stripe charge id.
- **Inert/safe:** subscription page shows an empty state without a row; portal/cancel need a Stripe customer; credit note is a no-op if no Teamleader invoice was recorded. Nothing runs before Stripe/Teamleader are live.
```
