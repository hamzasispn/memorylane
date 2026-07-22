# Payment Restore — Phase 3: Teamleader paid-invoice on `invoice.paid`

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** On every Stripe `invoice.paid` (activation + each yearly renewal), create a **booked, paid** invoice in Teamleader (`invoices.create` → `invoices.book` → `invoices.registerPayment`), idempotent per Stripe invoice id, so Teamleader's native Exact link carries it to accounting. Also create the Teamleader **Deal** on paid activation (the no-payment flow did this; the paid flow didn't).

**Architecture:** Reuse the existing `ml_tl_request()` client + the `ml_cron_tl_retry` cron. Add a Teamleader **invoicing** module with a *pure* invoice-body builder (unit-tested), an idempotency guard keyed by Stripe invoice id, and a durable retry queue (Stripe webhook dedup means we can't lean on Stripe retries for failed pushes). A thin Stripe event adapter maps `invoice.paid` → the module.

**Tech Stack:** PHP 8 / WordPress, `stripe-php`, Teamleader Focus REST API (`https://api.focus.teamleader.eu`). Verify: pure builder via `php`-CLI unit script; rest via `php -l` + smoke-load + documented manual (Stripe CLI + Teamleader sandbox) steps.

**Spec:** `docs/superpowers/specs/2026-07-22-payment-subscription-teamleader-invoicing-design.md`
**Depends on:** Phases 1–2 (already on this branch). `invoice.paid` is already mapped to `ml_stripe_event_invoice_paid` in `inc/stripe/webhooks.php` (handler added here).

**VAT approach:** Stripe prices are treated as **VAT-inclusive** (typical EU consumer). The Teamleader line uses `"tax": "including"` with the Stripe gross amount + the configured tax rate, so the Teamleader invoice total equals the Stripe charge to the cent.

**Teamleader endpoints used:** `departments.list`, `taxRates.list` (filter by department), `contacts.list`/`contacts.add`, `deals.create` (via existing `ml_tl_push_booking`), `invoices.create`, `invoices.book`, `invoices.registerPayment`.

---

### Task 1: Create the Teamleader Deal on paid activation

**Files:**
- Modify: `inc/stripe/events/checkout-session-completed.php`

The no-payment flow pushes a Contact+Deal via `ml_tl_push_booking()`; the paid webhook must do the same so a Deal exists and `_ml_tl_contact_id` is set for invoicing.

- [ ] **Step 1: Add the Teamleader push at the end of the handler**

In `inc/stripe/events/checkout-session-completed.php`, immediately before the final closing `}` of `ml_stripe_event_checkout_session_completed()`, add:
```php
    // Push Contact + Deal to Teamleader (mirrors the no-payment booking flow).
    if ( function_exists( 'ml_tl_push_booking' ) ) {
        $tl_data = array(
            'name'          => $session->customer_details->name ?? $user->display_name,
            'phone'         => (string) ( $session->customer_details->phone ?? ( $md['ml_phone'] ?? '' ) ),
            'street'        => (string) ( $md['ml_street'] ?? '' ),
            'postcode'      => (string) ( $md['ml_postcode'] ?? '' ),
            'city'          => (string) ( $md['ml_city'] ?? '' ),
            'country'       => (string) ( $md['ml_country'] ?? '' ),
            'notes'         => (string) ( $md['ml_notes'] ?? '' ),
            'scheduled_for' => isset( $slot ) && $slot ? $slot->slot_start_datetime : '',
        );
        try { ml_tl_push_booking( $user, $tl_data ); }
        catch ( \Throwable $e ) { error_log( '[memorylane] TL push on activation failed: ' . $e->getMessage() ); }
    }
```
(Note: `$slot` is only set inside the `if ( $slot_id )` block; add `$slot = null;` at the top of that block's scope — declare `$slot = null;` right after the `$slot_id = ...` line so it's always defined.)

- [ ] **Step 2: Declare `$slot` safely**

In the same file, change the booking block opener from:
```php
    $slot_id = isset( $md['ml_slot_id'] ) ? (int) $md['ml_slot_id'] : 0;
    if ( $slot_id ) {
```
to:
```php
    $slot_id = isset( $md['ml_slot_id'] ) ? (int) $md['ml_slot_id'] : 0;
    $slot    = null;
    if ( $slot_id ) {
```
and inside, assign `$slot = function_exists( 'ml_get_slot' ) ? ml_get_slot( $slot_id ) : null;` (already present) — keep it, and remove the inner `$slot =` redeclaration duplication if any.

- [ ] **Step 3: Lint**

Run: `php -l inc/stripe/events/checkout-session-completed.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add inc/stripe/events/checkout-session-completed.php
git commit -m "feat(stripe): create Teamleader Contact+Deal on paid activation"
```

---

### Task 2: Teamleader invoice settings (department + VAT rate)

**Files:**
- Modify: `inc/teamleader/settings.php` (add department + tax-rate pickers + save)

- [ ] **Step 1: Add list helpers + option constants**

At the top of `inc/teamleader/settings.php` (after the `defined( 'ABSPATH' )` line), add:
```php
const ML_TL_OPT_DEPARTMENT_ID = 'ml_tl_department_id';
const ML_TL_OPT_TAX_RATE_ID   = 'ml_tl_tax_rate_id';

function ml_tl_list_departments() {
    if ( ! ml_tl_is_connected() ) return array();
    try { $d = ml_tl_request( 'departments.list' ); return is_array( $d ) ? $d : array(); }
    catch ( \Throwable $e ) { return array(); }
}

function ml_tl_list_tax_rates() {
    if ( ! ml_tl_is_connected() ) return array();
    $dept = (string) get_option( ML_TL_OPT_DEPARTMENT_ID, '' );
    $body = $dept ? array( 'filter' => array( 'department_id' => $dept ) ) : array();
    try { $t = ml_tl_request( 'taxRates.list', $body ); return is_array( $t ) ? $t : array(); }
    catch ( \Throwable $e ) { return array(); }
}

function ml_tl_department_id() { return (string) get_option( ML_TL_OPT_DEPARTMENT_ID, '' ); }
function ml_tl_tax_rate_id()   { return (string) get_option( ML_TL_OPT_TAX_RATE_ID, '' ); }
function ml_tl_invoicing_ready() { return ml_tl_is_connected() && ml_tl_department_id() && ml_tl_tax_rate_id(); }
```

- [ ] **Step 2: Add the pickers to the connected "sync settings" form**

In `ml_tl_render_settings()`, inside the `if ( $connected )` sync form (after the deal-phase `mla-form-row`, before "Queued bookings"), add:
```php
            <?php $depts = ml_tl_list_departments(); $rates = ml_tl_list_tax_rates(); ?>
            <div class="mla-form-row">
                <label>Invoice department</label>
                <div>
                    <?php if ( $depts ) : ?>
                        <select name="tl_department_id">
                            <option value="">— select —</option>
                            <?php foreach ( $depts as $d ) : ?>
                                <option value="<?php echo esc_attr( $d['id'] ); ?>" <?php selected( ml_tl_department_id(), $d['id'] ); ?>><?php echo esc_html( $d['name'] ?? $d['id'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?><em>Could not load departments.</em><?php endif; ?>
                    <div class="help">The legal entity that issues invoices (syncs to Exact).</div>
                </div>
            </div>
            <div class="mla-form-row">
                <label>VAT rate</label>
                <div>
                    <?php if ( $rates ) : ?>
                        <select name="tl_tax_rate_id">
                            <option value="">— select —</option>
                            <?php foreach ( $rates as $r ) : ?>
                                <option value="<?php echo esc_attr( $r['id'] ); ?>" <?php selected( ml_tl_tax_rate_id(), $r['id'] ); ?>><?php echo esc_html( ( isset( $r['rate'] ) ? ( (float) $r['rate'] * 100 ) . '% ' : '' ) . ( $r['description'] ?? $r['id'] ) ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?><em>Select a department first, then save to load its VAT rates.</em><?php endif; ?>
                </div>
            </div>
```

- [ ] **Step 3: Persist them in the `sync_settings` handler branch**

In the `admin_post_ml_tl_save` handler, inside `if ( $action === 'sync_settings' )`, before the redirect, add:
```php
        if ( isset( $_POST['tl_department_id'] ) ) {
            update_option( ML_TL_OPT_DEPARTMENT_ID, sanitize_text_field( wp_unslash( $_POST['tl_department_id'] ) ), false );
        }
        if ( isset( $_POST['tl_tax_rate_id'] ) ) {
            update_option( ML_TL_OPT_TAX_RATE_ID, sanitize_text_field( wp_unslash( $_POST['tl_tax_rate_id'] ) ), false );
        }
```

- [ ] **Step 4: Lint + commit**

Run: `php -l inc/teamleader/settings.php`
Expected: `No syntax errors detected`.
```bash
git add inc/teamleader/settings.php
git commit -m "feat(teamleader): invoice settings — department + VAT rate pickers"
```

---

### Task 3: Invoicing module — pure builder + push + idempotency + queue

**Files:**
- Create: `inc/teamleader/invoicing.php`
- Modify: `inc/bootstrap.php`
- Test: `tests/stripe/invoice-body.test.php`

- [ ] **Step 1: Write the failing unit test for the pure body builder**

Create `tests/stripe/invoice-body.test.php`:
```php
<?php
define( 'ABSPATH', __DIR__ );
require __DIR__ . '/../../inc/teamleader/invoicing.php';

$fail = 0;
function check( $l, $g, $w ) { global $fail; if ( $g === $w ) echo "PASS  $l\n"; else { echo "FAIL  $l — got " . var_export($g,true) . " want " . var_export($w,true) . "\n"; $GLOBALS['fail']++; } }

$b = ml_tl_build_invoice_body( 'contact_1', 'dept_1', 'tax_1', 'Memory Lane — Activation', 29900, 'eur' );
check( 'invoicee contact', $b['invoicee']['customer']['id'],   'contact_1' );
check( 'invoicee type',    $b['invoicee']['customer']['type'], 'contact' );
check( 'department',       $b['department_id'],                'dept_1' );
$li = $b['grouped_lines'][0]['line_items'][0];
check( 'qty',              $li['quantity'],                    1 );
check( 'description',      $li['description'],                 'Memory Lane — Activation' );
check( 'amount decimal',   $li['unit_price']['amount'],        '299.00' );
check( 'currency upper',   $li['unit_price']['currency'],      'EUR' );
check( 'tax including',    $li['unit_price']['tax'],           'including' );
check( 'tax rate id',      $li['tax_rate_id'],                 'tax_1' );

exit( $fail === 0 ? 0 : 1 );
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php tests/stripe/invoice-body.test.php`
Expected: FAIL — file/function not present.

- [ ] **Step 3: Write the invoicing module**

Create `inc/teamleader/invoicing.php`:
```php
<?php
/**
 * Memory Lane — Teamleader invoicing on Stripe invoice.paid.
 * Creates a booked, paid invoice in Teamleader (single source of truth; Exact
 * sync is Teamleader's own integration). Idempotent per Stripe invoice id.
 */
defined( 'ABSPATH' ) || exit;

const ML_TL_OPT_INVOICE_QUEUE = 'ml_tl_invoice_queue';

/**
 * Build the invoices.create body (pure; VAT-inclusive amount).
 */
function ml_tl_build_invoice_body( $contact_id, $department_id, $tax_rate_id, $description, $amount_cents, $currency ) {
    return array(
        'invoicee'      => array( 'customer' => array( 'type' => 'contact', 'id' => (string) $contact_id ) ),
        'department_id' => (string) $department_id,
        'payment_term'  => array( 'type' => 'cash' ),
        'grouped_lines' => array( array(
            'line_items' => array( array(
                'quantity'    => 1,
                'description' => (string) $description,
                'unit_price'  => array(
                    'amount'   => number_format( ( (int) $amount_cents ) / 100, 2, '.', '' ),
                    'currency' => strtoupper( (string) $currency ),
                    'tax'      => 'including',
                ),
                'tax_rate_id' => (string) $tax_rate_id,
            ) ),
        ) ),
    );
}

/** Idempotency: TL invoice id already recorded for this Stripe invoice? */
function ml_tl_invoice_done_key( $stripe_invoice_id ) { return '_ml_tl_inv_' . preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $stripe_invoice_id ); }

/**
 * Push one paid invoice to Teamleader. $data keys:
 *   stripe_invoice_id, amount_cents, currency, description
 * Throws on API failure (caller decides retry).
 */
function ml_tl_push_invoice( $user, array $data ) {
    $sid = (string) ( $data['stripe_invoice_id'] ?? '' );
    if ( $sid && get_user_meta( $user->ID, ml_tl_invoice_done_key( $sid ), true ) ) {
        return; // already invoiced — idempotent no-op
    }
    if ( ! function_exists( 'ml_tl_invoicing_ready' ) || ! ml_tl_invoicing_ready() ) {
        throw new \RuntimeException( 'Teamleader invoicing not configured (department/VAT).' );
    }

    $contact_id = ml_tl_resolve_contact_id( $user, $data );
    if ( ! $contact_id ) throw new \RuntimeException( 'No Teamleader contact for invoice.' );

    $body = ml_tl_build_invoice_body(
        $contact_id, ml_tl_department_id(), ml_tl_tax_rate_id(),
        (string) ( $data['description'] ?? 'Memory Lane' ),
        (int) ( $data['amount_cents'] ?? 0 ),
        (string) ( $data['currency'] ?? 'eur' )
    );

    $created = ml_tl_request( 'invoices.create', $body );
    $inv_id  = $created['id'] ?? '';
    if ( ! $inv_id ) throw new \RuntimeException( 'invoices.create returned no id.' );

    ml_tl_request( 'invoices.book', array( 'id' => $inv_id, 'on' => gmdate( 'Y-m-d' ) ) );
    ml_tl_request( 'invoices.registerPayment', array(
        'id'      => $inv_id,
        'payment' => array(
            'amount'  => array( 'amount' => number_format( ( (int) ( $data['amount_cents'] ?? 0 ) ) / 100, 2, '.', '' ), 'currency' => strtoupper( (string) ( $data['currency'] ?? 'eur' ) ) ),
            'paid_at' => gmdate( 'Y-m-d\TH:i:sP' ),
        ),
    ) );

    if ( $sid ) update_user_meta( $user->ID, ml_tl_invoice_done_key( $sid ), $inv_id );
}

/** Reuse the stored contact id, else find/create by email. */
function ml_tl_resolve_contact_id( $user, array $data ) {
    $cid = (string) get_user_meta( $user->ID, '_ml_tl_contact_id', true );
    if ( $cid ) return $cid;
    try {
        $found = ml_tl_request( 'contacts.list', array(
            'filter' => array( 'email' => array( 'type' => 'primary', 'email' => $user->user_email ) ),
            'page'   => array( 'size' => 1 ),
        ) );
        if ( ! empty( $found[0]['id'] ) ) { update_user_meta( $user->ID, '_ml_tl_contact_id', $found[0]['id'] ); return $found[0]['id']; }
    } catch ( \Throwable $e ) {}
    $parts = preg_split( '/\s+/', trim( (string) ( $data['name'] ?? $user->display_name ) ), 2 );
    $created = ml_tl_request( 'contacts.add', array(
        'first_name' => $parts[0] ?? $user->display_name,
        'last_name'  => $parts[1] ?? '',
        'emails'     => array( array( 'type' => 'primary', 'email' => $user->user_email ) ),
    ) );
    $cid = $created['id'] ?? '';
    if ( $cid ) update_user_meta( $user->ID, '_ml_tl_contact_id', $cid );
    return $cid;
}

/* ---- durable retry queue (drained by ml_cron_tl_retry) ---- */
function ml_tl_invoice_queue() { $q = get_option( ML_TL_OPT_INVOICE_QUEUE, array() ); return is_array( $q ) ? $q : array(); }

function ml_tl_invoice_enqueue( $user_id, array $data ) {
    $q = ml_tl_invoice_queue();
    $q[] = array( 'user_id' => (int) $user_id, 'data' => $data, 'attempts' => 0, 'queued_at' => time() );
    if ( count( $q ) > 500 ) $q = array_slice( $q, -500 );
    update_option( ML_TL_OPT_INVOICE_QUEUE, $q, false );
}

function ml_tl_process_invoice_queue() {
    $q = ml_tl_invoice_queue();
    if ( empty( $q ) ) return;
    $remaining = array();
    foreach ( $q as $item ) {
        $user = get_user_by( 'id', (int) ( $item['user_id'] ?? 0 ) );
        if ( ! $user ) continue;
        try { ml_tl_push_invoice( $user, (array) ( $item['data'] ?? array() ) ); }
        catch ( \Throwable $e ) {
            $item['attempts'] = (int) ( $item['attempts'] ?? 0 ) + 1;
            if ( $item['attempts'] < 8 ) $remaining[] = $item;
            else error_log( '[memorylane] TL invoice dropped after max attempts: ' . $e->getMessage() );
        }
    }
    update_option( ML_TL_OPT_INVOICE_QUEUE, $remaining, false );
}
add_action( 'ml_cron_tl_retry', 'ml_tl_process_invoice_queue' );
```

- [ ] **Step 4: Run the unit test to verify it passes**

Run: `php tests/stripe/invoice-body.test.php`
Expected: ten `PASS` lines, exit 0.

- [ ] **Step 5: Wire into bootstrap + lint**

In `inc/bootstrap.php`, after `require_once __DIR__ . '/teamleader/settings.php';` add:
```php
require_once __DIR__ . '/teamleader/invoicing.php';
```
Run: `php -l inc/teamleader/invoicing.php && php -l inc/bootstrap.php`
Expected: `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add inc/teamleader/invoicing.php inc/bootstrap.php tests/stripe/invoice-body.test.php
git commit -m "feat(teamleader): invoice module — pure builder + push + idempotency + queue"
```

---

### Task 4: `invoice.paid` Stripe event adapter

**Files:**
- Create: `inc/stripe/events/invoice-paid.php`
- Modify: `inc/bootstrap.php`

- [ ] **Step 1: Write the adapter**

Create `inc/stripe/events/invoice-paid.php`:
```php
<?php
/** Stripe event: invoice.paid → push a paid invoice to Teamleader. */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_invoice_paid( \Stripe\Event $event ) {
    $inv = $event->data->object;
    $customer_id = is_object( $inv->customer ?? null ) ? ( $inv->customer->id ?? '' ) : (string) ( $inv->customer ?? '' );
    if ( ! $customer_id ) return;

    // Map Stripe customer → WP user.
    $users = get_users( array( 'meta_key' => ML_META_STRIPE_CUSTOMER, 'meta_value' => $customer_id, 'number' => 1, 'fields' => 'ID' ) );
    if ( empty( $users ) ) { error_log( '[memorylane] invoice.paid: no user for customer ' . $customer_id ); return; }
    $user = get_user_by( 'id', (int) $users[0] );
    if ( ! $user ) return;

    $reason = (string) ( $inv->billing_reason ?? '' );
    $desc   = ( $reason === 'subscription_create' ) ? 'Memory Lane — Activatie' : 'Memory Lane — Jaarlijkse verlenging';
    $data = array(
        'stripe_invoice_id' => (string) ( $inv->id ?? '' ),
        'amount_cents'      => (int) ( $inv->amount_paid ?? 0 ),
        'currency'          => (string) ( $inv->currency ?? 'eur' ),
        'description'       => $desc,
        'name'              => $user->display_name,
    );

    try { ml_tl_push_invoice( $user, $data ); }
    catch ( \Throwable $e ) {
        error_log( '[memorylane] invoice.paid TL push failed, queued: ' . $e->getMessage() );
        ml_tl_invoice_enqueue( $user->ID, $data ); // durable retry via ml_cron_tl_retry
    }
}
```
(The handler swallows failures into the durable queue so the webhook returns 200 — the receiver's event-id dedup would otherwise block Stripe re-delivery.)

- [ ] **Step 2: Wire into bootstrap + lint**

In `inc/bootstrap.php`, after the `subscription-changed.php` require add:
```php
require_once __DIR__ . '/stripe/events/invoice-paid.php';
```
Run: `php -l inc/stripe/events/invoice-paid.php && php -l inc/bootstrap.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add inc/stripe/events/invoice-paid.php inc/bootstrap.php
git commit -m "feat(stripe): invoice.paid -> Teamleader paid invoice (durable retry)"
```

---

### Task 5: Full smoke + lint + manual checklist

- [ ] **Step 1: All unit tests**

Run:
```bash
php tests/stripe/money-units.test.php && php tests/stripe/sub-mapper.test.php && php tests/stripe/access-gate.test.php && php tests/stripe/invoice-body.test.php
```
Expected: all PASS, exit 0.

- [ ] **Step 2: Lint touched files**

Run:
```bash
for f in inc/teamleader/settings.php inc/teamleader/invoicing.php inc/stripe/events/invoice-paid.php inc/stripe/events/checkout-session-completed.php inc/bootstrap.php; do php -l "$f"; done
```
Expected: `No syntax errors detected` for each.

- [ ] **Step 3: Smoke-load the invoicing + adapter chain**

Run:
```bash
php -r "define('ABSPATH',1); function add_action(){} require 'inc/teamleader/invoicing.php'; require 'inc/stripe/events/invoice-paid.php'; echo (function_exists('ml_tl_build_invoice_body') && function_exists('ml_tl_push_invoice') && function_exists('ml_stripe_event_invoice_paid') && function_exists('ml_tl_process_invoice_queue')) ? 'CHAIN OK' : 'FAIL';"
```
Expected: `CHAIN OK`.

- [ ] **Step 4: Manual live test (Stripe test + Teamleader sandbox)**

1. Connect Teamleader (Settings) with the `invoices` scope; pick department + VAT rate.
2. Complete a `/boek` test checkout (Phase 2). On `invoice.paid`: a booked invoice appears in Teamleader, marked paid, total == Stripe amount; a Deal + Contact exist.
3. Re-send the same `invoice.paid` event → no duplicate invoice (idempotent).
4. Simulate a renewal invoice → a second Teamleader invoice is created + paid.
5. With Teamleader disconnected, pay an invoice → it lands in the invoice queue and syncs on the next `ml_cron_tl_retry`.

---

## Self-review notes

- **Spec coverage:** invoice on `invoice.paid` for activation + renewals (T3+T4), Deal on paid activation (T1), department/VAT config (T2), idempotency by Stripe invoice id (T3), durable retry (T3+T4). Refund→credit-note is Phase 4.
- **Placeholders:** none — full code + commands + expected output throughout.
- **Type consistency:** `ml_tl_build_invoice_body()` output shape is asserted by the unit test and consumed only by `ml_tl_push_invoice()`; `ml_tl_invoicing_ready()`/`ml_tl_department_id()`/`ml_tl_tax_rate_id()` defined in T2 and used in T3.
- **Inert/safe:** `ml_tl_push_invoice()` throws (→ queue) unless `ml_tl_invoicing_ready()`; `invoice.paid` only fires once Stripe is live; nothing runs before keys/Teamleader are configured.
```
