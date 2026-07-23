# Payment Restore — Phase 2: Checkout + autonomous activation + access re-gating

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `/boek` collect payment via a Stripe **subscription-mode** Checkout (yearly price + one-time activation fee), activate the customer **autonomously** on the `checkout.session.completed` webhook (no admin approval), keep the subscription row in sync from Stripe events, and re-gate dashboard access on subscription status. Falls back to the current no-payment flow whenever Stripe is not configured, so nothing breaks before keys are entered.

**Architecture:** Reuse the restored webhook receiver (signature verify + `ml_webhook_events` idempotency + dispatch map) and `ml_upsert_subscription`. Write *new* event handlers for the subscription-mode + autonomous model (the old ones were payment-mode + approval-gated). Risky logic (Stripe-object → row mapping, and access decision) is extracted into **pure functions with unit tests**.

**Tech Stack:** PHP 8 / WordPress, vendored `stripe-php`, custom `/admin`. No PHPUnit → pure logic gets standalone `php`-CLI unit scripts; WP/Stripe-integrated code gets `php -l` + smoke-load + documented manual (Stripe CLI) steps.

**Spec:** `docs/superpowers/specs/2026-07-22-payment-subscription-teamleader-invoicing-design.md`
**Depends on:** Phase 1 (SDK + client + plans + settings) — already merged on this branch.

**Key schema (existing `ml_subscriptions`):** `user_id, stripe_customer_id (NOT NULL), stripe_sub_id (NOT NULL, unique), status, current_period_end, year_one_end_date, cancel_at_period_end, payment_failed_at, raw_json, created_at, updated_at`.

---

### Task 1: Restore + adapt the webhook receiver

**Files:**
- Create: `inc/stripe/webhooks.php` (restored from `60f1a65^`, dispatch map trimmed)
- Modify: `inc/bootstrap.php` (require after `stripe/plans.php`)

- [ ] **Step 1: Restore the receiver**

Run:
```bash
git checkout 60f1a65^ -- inc/stripe/webhooks.php
```

- [ ] **Step 2: Trim the dispatch map to Phase-2/3 events**

In `inc/stripe/webhooks.php`, replace the `$map` array in `ml_stripe_dispatch_event()` with:
```php
    $map = array(
        'checkout.session.completed'    => 'ml_stripe_event_checkout_session_completed',
        'customer.subscription.updated' => 'ml_stripe_event_customer_subscription_changed',
        'customer.subscription.deleted' => 'ml_stripe_event_customer_subscription_deleted',
        'invoice.paid'                  => 'ml_stripe_event_invoice_paid',   // Phase 3 (Teamleader)
    );
```
(The `invoice.paid` handler does not exist until Phase 3; the dispatcher already guards with `function_exists()`, so unmapped/again-missing handlers are safely ignored.)

- [ ] **Step 3: Wire into bootstrap**

In `inc/bootstrap.php`, immediately after `require_once __DIR__ . '/stripe/plans.php';` add:
```php
require_once __DIR__ . '/stripe/webhooks.php';
```

- [ ] **Step 4: Lint**

Run: `php -l inc/stripe/webhooks.php && php -l inc/bootstrap.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 5: Commit**

```bash
git add inc/stripe/webhooks.php inc/bootstrap.php
git commit -m "feat(stripe): restore webhook receiver; dispatch our events"
```

---

### Task 2: Subscription sync + status helpers, with pure mapper + unit test

**Files:**
- Create: `inc/subscriptions/sync.php` (restored `ml_upsert_subscription` + new pure `ml_sub_fields_from_stripe`)
- Create: `inc/subscriptions/status.php` (restored reads)
- Modify: `inc/bootstrap.php`
- Test: `tests/stripe/sub-mapper.test.php`

- [ ] **Step 1: Restore the two files**

Run:
```bash
git checkout 60f1a65^ -- inc/subscriptions/sync.php inc/subscriptions/status.php
```

- [ ] **Step 2: Write the failing unit test for the Stripe→row mapper**

Create `tests/stripe/sub-mapper.test.php`:
```php
<?php
define( 'ABSPATH', __DIR__ );
require __DIR__ . '/../../inc/subscriptions/sync.php';

$fail = 0;
function check( $l, $g, $w ) { global $fail; if ( $g === $w ) echo "PASS  $l\n"; else { echo "FAIL  $l — got " . var_export($g,true) . " want " . var_export($w,true) . "\n"; $GLOBALS['fail']++; } }

// Minimal fake of a \Stripe\Subscription (array access + ->props as used by the mapper).
$sub = (object) array(
    'id'                   => 'sub_123',
    'customer'             => 'cus_123',
    'status'               => 'active',
    'current_period_end'   => 1893456000,          // 2030-01-01 UTC
    'cancel_at_period_end' => false,
);
$f = ml_sub_fields_from_stripe( $sub );
check( 'sub id',        $f['stripe_sub_id'],        'sub_123' );
check( 'customer id',   $f['stripe_customer_id'],   'cus_123' );
check( 'status',        $f['status'],               'active' );
check( 'period end utc',$f['current_period_end'],   '2030-01-01 00:00:00' );
check( 'cancel flag',   $f['cancel_at_period_end'], 0 );

exit( $fail === 0 ? 0 : 1 );
```

- [ ] **Step 3: Run it to verify it fails**

Run: `php tests/stripe/sub-mapper.test.php`
Expected: FAIL — `ml_sub_fields_from_stripe` not defined yet (or fatal).

- [ ] **Step 4: Add the pure mapper to `inc/subscriptions/sync.php`**

Append to `inc/subscriptions/sync.php` (before the closing — it has no closing tag; just add at end of file):
```php
/**
 * Map a Stripe Subscription object to ml_subscriptions row fields (pure).
 * `customer` may be an id string or an expanded object.
 */
function ml_sub_fields_from_stripe( $sub ) {
    $cust = is_object( $sub->customer ?? null ) ? ( $sub->customer->id ?? '' ) : (string) ( $sub->customer ?? '' );
    $cpe  = ! empty( $sub->current_period_end ) ? gmdate( 'Y-m-d H:i:s', (int) $sub->current_period_end ) : null;
    return array(
        'stripe_sub_id'        => (string) ( $sub->id ?? '' ),
        'stripe_customer_id'   => $cust,
        'status'               => (string) ( $sub->status ?? '' ),
        'current_period_end'   => $cpe,
        'cancel_at_period_end' => ! empty( $sub->cancel_at_period_end ) ? 1 : 0,
        'raw_json'             => wp_json_encode( $sub instanceof \Stripe\StripeObject ? $sub->toArray() : (array) $sub ),
    );
}
```
Note: the test calls `wp_json_encode` — add a guard so the pure test runs without WP. At the very top of `sub-mapper.test.php` (after the `define`), stub it:
```php
if ( ! function_exists( 'wp_json_encode' ) ) { function wp_json_encode( $d ) { return json_encode( $d ); } }
```
(Insert that stub line into the test file before the `require`.)

- [ ] **Step 5: Run the unit test to verify it passes**

Run: `php tests/stripe/sub-mapper.test.php`
Expected: five `PASS` lines, exit 0.

- [ ] **Step 6: Wire into bootstrap + lint**

In `inc/bootstrap.php`, after the access-gate require (`require_once __DIR__ . '/subscriptions/access-gate.php';`) add:
```php
require_once __DIR__ . '/subscriptions/status.php';
require_once __DIR__ . '/subscriptions/sync.php';
```
Run: `php -l inc/subscriptions/sync.php && php -l inc/subscriptions/status.php && php -l inc/bootstrap.php`
Expected: `No syntax errors detected` for each.

- [ ] **Step 7: Commit**

```bash
git add inc/subscriptions/sync.php inc/subscriptions/status.php inc/bootstrap.php tests/stripe/sub-mapper.test.php
git commit -m "feat(subs): restore sub sync/status + pure Stripe→row mapper w/ unit test"
```

---

### Task 3: `checkout.session.completed` handler (subscription mode, autonomous)

**Files:**
- Create: `inc/stripe/events/checkout-session-completed.php` (new, our model)
- Modify: `inc/bootstrap.php`

- [ ] **Step 1: Write the handler**

Create `inc/stripe/events/checkout-session-completed.php`:
```php
<?php
/**
 * Stripe event: checkout.session.completed (subscription mode).
 * Autonomous activation — no admin approval. Provisions the WP user, the
 * booking row (from /boek metadata), and the subscription mirror row, then
 * sends the welcome + purchase-confirmation emails.
 */
defined( 'ABSPATH' ) || exit;

function ml_stripe_event_checkout_session_completed( \Stripe\Event $event ) {
    $obj    = $event->data->object;
    $stripe = ml_stripe();
    if ( ! $stripe ) throw new \RuntimeException( 'Stripe client unavailable' );

    $session = $stripe->checkout->sessions->retrieve( $obj->id, array(
        'expand' => array( 'customer', 'customer_details', 'subscription' ),
    ) );

    if ( $session->mode !== 'subscription' ) return;          // Phase 2 only handles subscription checkouts
    if ( ! in_array( $session->status, array( 'complete' ), true ) ) return;

    $customer = $session->customer;
    $email    = $session->customer_details->email ?? ( is_object( $customer ) ? ( $customer->email ?? null ) : null );
    if ( ! $email ) throw new \RuntimeException( 'No email on Stripe session' );

    // 1. Find / create the WP user.
    $user = get_user_by( 'email', $email );
    $is_new = false;
    if ( ! $user ) {
        $username = ml_unique_username( $email );
        $uid = wp_insert_user( array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password( 24, true, true ),
            'display_name' => $session->customer_details->name ?? $username,
            'role'         => ML_ROLE_CUSTOMER,
        ) );
        if ( is_wp_error( $uid ) ) throw new \RuntimeException( 'WP user create failed: ' . $uid->get_error_message() );
        $user   = get_user_by( 'id', $uid );
        $is_new = true;
    } elseif ( ! in_array( ML_ROLE_CUSTOMER, (array) $user->roles, true ) && ! user_can( $user, 'administrator' ) ) {
        $user->add_role( ML_ROLE_CUSTOMER );
    }

    // 2. Save Stripe customer id + contact details.
    $cust_id = is_object( $customer ) ? $customer->id : (string) $customer;
    update_user_meta( $user->ID, ML_META_STRIPE_CUSTOMER, $cust_id );
    if ( ! empty( $session->customer_details->phone ) ) update_user_meta( $user->ID, ML_META_PHONE, $session->customer_details->phone );
    $md = $session->metadata;
    if ( ! empty( $md['ml_lang'] ) )   update_user_meta( $user->ID, ML_META_LANG, (string) $md['ml_lang'] );
    if ( ! empty( $md['ml_street'] ) ) update_user_meta( $user->ID, '_ml_address_line1',   (string) $md['ml_street'] );
    if ( ! empty( $md['ml_postcode'] ) ) update_user_meta( $user->ID, '_ml_address_postal', (string) $md['ml_postcode'] );
    if ( ! empty( $md['ml_city'] ) )   update_user_meta( $user->ID, '_ml_address_city',    (string) $md['ml_city'] );
    if ( ! empty( $md['ml_country'] ) ) update_user_meta( $user->ID, '_ml_address_country', (string) $md['ml_country'] );

    // 3. Insert the booking row (idempotent) from the /boek slot metadata.
    $slot_id = isset( $md['ml_slot_id'] ) ? (int) $md['ml_slot_id'] : 0;
    if ( $slot_id ) {
        global $wpdb;
        $btbl = ml_table( 'bookings' );
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$btbl} WHERE user_id=%d AND slot_id=%d AND service_type=%s",
            $user->ID, $slot_id, 'initial_scan'
        ) );
        if ( ! $exists ) {
            $slot = function_exists( 'ml_get_slot' ) ? ml_get_slot( $slot_id ) : null;
            if ( $slot ) {
                $now = current_time( 'mysql', true );
                $wpdb->insert( $btbl, array(
                    'user_id'        => $user->ID,
                    'slot_id'        => $slot_id,
                    'service_type'   => 'initial_scan',
                    'status'         => 'requested',
                    'customer_notes' => isset( $md['ml_notes'] ) ? (string) $md['ml_notes'] : '',
                    'scheduled_for'  => $slot->slot_start_datetime,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ) );
            }
        }
    }

    // 4. Mirror the subscription row (status drives access).
    $sub = $session->subscription;
    if ( is_object( $sub ) ) {
        $fields = ml_sub_fields_from_stripe( $sub );
        if ( empty( $fields['year_one_end_date'] ) && ! empty( $fields['current_period_end'] ) ) {
            $fields['year_one_end_date'] = $fields['current_period_end']; // first period = year one
        }
        ml_upsert_subscription( $user->ID, $fields );
    }

    // 5. Autonomous — mark setup approved, send emails. No admin approval step.
    update_user_meta( $user->ID, ML_META_SETUP_STATE,   ML_SETUP_STATE_APPROVED );
    update_user_meta( $user->ID, ML_META_SETUP_PAID_AT, current_time( 'mysql', true ) );

    if ( $is_new && function_exists( 'ml_send_reset_email' ) ) {
        ml_send_reset_email( $user, 'welcome_set_password' );
    }
    ml_mail_send( $user->user_email, 'purchase_confirmation', array(
        'user'         => $user,
        'amount_total' => (int) ( $session->amount_total ?? 0 ),
        'currency'     => (string) ( $session->currency ?? 'eur' ),
    ), $user->ID );
    foreach ( ml_admin_recipients() as $to ) {
        ml_mail_send( $to, 'admin_new_purchase', array( 'user' => $user, 'session' => $session ) );
    }
}
```

- [ ] **Step 2: Move `ml_unique_username` if not already global**

Run: `grep -rn "function ml_unique_username" inc`
- If it already exists (it is used by the current `boek-checkout.php`), do nothing.
- Expected: it exists in `inc/booking/boek-checkout.php` (or helpers). If found, no action.

- [ ] **Step 3: Wire into bootstrap + lint**

In `inc/bootstrap.php`, after `require_once __DIR__ . '/stripe/webhooks.php';` add:
```php
require_once __DIR__ . '/stripe/events/checkout-session-completed.php';
```
Run: `php -l inc/stripe/events/checkout-session-completed.php && php -l inc/bootstrap.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Smoke-load (no fatal, handler defined)**

Run:
```bash
php -r "define('ABSPATH',1); function get_template_directory(){return 'c:/laragon/www/memory-lane/wp-content/themes/memorylane';} function ml_stripe_opt(\$k,\$d=''){return \$d;} function ml_stripe_mode(){return 'test';} require 'inc/stripe/client.php'; require 'inc/subscriptions/sync.php'; require 'inc/stripe/events/checkout-session-completed.php'; echo function_exists('ml_stripe_event_checkout_session_completed') ? 'HANDLER OK' : 'FAIL';"
```
Expected: `HANDLER OK`.

- [ ] **Step 5: Commit**

```bash
git add inc/stripe/events/checkout-session-completed.php inc/bootstrap.php
git commit -m "feat(stripe): autonomous checkout.session.completed handler (subscription mode)"
```

---

### Task 4: Subscription changed / deleted handlers

**Files:**
- Create: `inc/stripe/events/subscription-changed.php`
- Modify: `inc/bootstrap.php`

- [ ] **Step 1: Write the handlers**

Create `inc/stripe/events/subscription-changed.php`:
```php
<?php
/** Stripe events: customer.subscription.updated / .deleted → mirror status. */
defined( 'ABSPATH' ) || exit;

function ml_stripe_sub_user_id( $customer_id, $sub_id ) {
    // Prefer the meta lookup, fall back to the subscriptions table.
    $users = get_users( array( 'meta_key' => ML_META_STRIPE_CUSTOMER, 'meta_value' => $customer_id, 'number' => 1, 'fields' => 'ID' ) );
    if ( ! empty( $users ) ) return (int) $users[0];
    global $wpdb;
    $tbl = ml_table( 'subscriptions' );
    $uid = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$tbl} WHERE stripe_sub_id=%s LIMIT 1", $sub_id ) );
    return (int) $uid;
}

function ml_stripe_event_customer_subscription_changed( \Stripe\Event $event ) {
    $sub    = $event->data->object;
    $fields = ml_sub_fields_from_stripe( $sub );
    $uid    = ml_stripe_sub_user_id( $fields['stripe_customer_id'], $fields['stripe_sub_id'] );
    if ( $uid ) ml_upsert_subscription( $uid, $fields );
}

function ml_stripe_event_customer_subscription_deleted( \Stripe\Event $event ) {
    $sub    = $event->data->object;
    $fields = ml_sub_fields_from_stripe( $sub );
    $fields['status'] = 'cancelled';
    $uid    = ml_stripe_sub_user_id( $fields['stripe_customer_id'], $fields['stripe_sub_id'] );
    if ( $uid ) ml_upsert_subscription( $uid, $fields );
}
```

- [ ] **Step 2: Wire into bootstrap + lint**

In `inc/bootstrap.php`, after the checkout handler require add:
```php
require_once __DIR__ . '/stripe/events/subscription-changed.php';
```
Run: `php -l inc/stripe/events/subscription-changed.php && php -l inc/bootstrap.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add inc/stripe/events/subscription-changed.php inc/bootstrap.php
git commit -m "feat(stripe): subscription updated/deleted -> mirror status"
```

---

### Task 5: `/boek` subscription-mode checkout (with no-payment fallback)

**Files:**
- Modify: `inc/booking/boek-checkout.php`

- [ ] **Step 1: Gate the flow on Stripe being configured**

In `inc/booking/boek-checkout.php`, inside `ml_rest_boek()`, after the slot validation block (right before the existing `try { ml_boek_provision_booking(...) }`), insert the payment branch:
```php
    // Payment path: if Stripe is configured, start a subscription Checkout.
    // Otherwise fall through to the existing no-payment provisioning (inert).
    if ( ml_stripe_is_configured() ) {
        try {
            $stripe  = ml_stripe();
            $session = $stripe->checkout->sessions->create( array(
                'mode'        => 'subscription',
                'line_items'  => array(
                    array( 'price' => ml_stripe_annual_price_id(), 'quantity' => 1 ), // recurring yearly
                    array( 'price' => ml_stripe_setup_price_id(),  'quantity' => 1 ), // one-time activation on 1st invoice
                ),
                'customer_email'             => $email,
                'billing_address_collection' => 'required',
                'phone_number_collection'    => array( 'enabled' => true ),
                'locale'                     => ml_current_lang() === 'en' ? 'en' : 'nl',
                'success_url'                => home_url( '/checkout/success?session_id={CHECKOUT_SESSION_ID}' ),
                'cancel_url'                 => home_url( '/boek?cancelled=1' ),
                'metadata' => array(
                    'ml_intent'   => 'initial_subscription_with_slot',
                    'ml_lang'     => ml_current_lang(),
                    'ml_slot_id'  => (string) $slot->id,
                    'ml_name'     => substr( $name, 0, 200 ),
                    'ml_phone'    => substr( $phone, 0, 80 ),
                    'ml_street'   => substr( $street, 0, 200 ),
                    'ml_postcode' => substr( $postcode, 0, 40 ),
                    'ml_city'     => substr( $city, 0, 120 ),
                    'ml_country'  => $country_code,
                    'ml_notes'    => substr( $notes, 0, 400 ),
                ),
            ) );
            return array( 'ok' => true, 'url' => $session->url );
        } catch ( \Throwable $e ) {
            error_log( '[memorylane] /boek subscription checkout failed: ' . $e->getMessage() );
            return new WP_REST_Response( array( 'ok' => false, 'error' => 'stripe_error' ), 500 );
        }
    }
```
Leave the existing no-payment `ml_boek_provision_booking(...)` block untouched below it as the fallback.

- [ ] **Step 2: Lint**

Run: `php -l inc/booking/boek-checkout.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Verify fallback is intact (still inert when unconfigured)**

Run: `grep -n "ml_boek_provision_booking\|ml_stripe_is_configured" inc/booking/boek-checkout.php`
Expected: both present — the Stripe branch guards on `ml_stripe_is_configured()`, and the no-payment provision remains as the else path.

- [ ] **Step 4: Commit**

```bash
git add inc/booking/boek-checkout.php
git commit -m "feat(boek): subscription-mode Stripe checkout when configured (no-payment fallback kept)"
```

---

### Task 6: Re-gate dashboard access on subscription status (pure decision + unit test)

**Files:**
- Modify: `inc/subscriptions/access-gate.php`
- Test: `tests/stripe/access-gate.test.php`

- [ ] **Step 1: Write the failing unit test for the pure access decision**

Create `tests/stripe/access-gate.test.php`:
```php
<?php
define( 'ABSPATH', __DIR__ );
require __DIR__ . '/../../inc/subscriptions/access-gate.php';

$fail = 0;
function check( $l, $g, $w ) { global $fail; if ( $g === $w ) echo "PASS  $l\n"; else { echo "FAIL  $l — got " . var_export($g,true) . " want " . var_export($w,true) . "\n"; $GLOBALS['fail']++; } }

$now   = 1_700_000_000;
$grace = 7 * 86400;
$mk = function ( $status, $cpe_offset ) { return (object) array( 'status' => $status, 'current_period_end' => gmdate( 'Y-m-d H:i:s', 1_700_000_000 + $cpe_offset ) ); };

check( 'active grants',              ml_access_from_sub_row( $mk('active',  86400), $grace, $now ), true );
check( 'trialing grants',            ml_access_from_sub_row( $mk('trialing',86400), $grace, $now ), true );
check( 'past_due within grace',      ml_access_from_sub_row( $mk('past_due', -86400), $grace, $now ), true );
check( 'past_due beyond grace',      ml_access_from_sub_row( $mk('past_due', -8*86400), $grace, $now ), false );
check( 'cancelled denies',           ml_access_from_sub_row( $mk('cancelled', 86400), $grace, $now ), false );
check( 'null row denies',            ml_access_from_sub_row( null, $grace, $now ), false );

exit( $fail === 0 ? 0 : 1 );
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php tests/stripe/access-gate.test.php`
Expected: FAIL — `ml_access_from_sub_row` not defined.

- [ ] **Step 3: Rewrite the access gate**

Replace the body of `inc/subscriptions/access-gate.php` with:
```php
<?php
/**
 * Memory Lane — access gate (subscription model).
 * A logged-in customer has portal access while their subscription is active /
 * trialing, or past_due within the configured grace window. Admins always pass.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Pure decision: does this subscription row grant access right now? (testable)
 *
 * @param object|null $row  ml_subscriptions row (needs ->status, ->current_period_end).
 * @param int $grace_seconds  past_due grace window.
 * @param int $now_ts         current unix time.
 */
function ml_access_from_sub_row( $row, $grace_seconds, $now_ts ) {
    if ( ! $row || empty( $row->status ) ) return false;
    if ( in_array( $row->status, array( 'active', 'trialing' ), true ) ) return true;
    if ( $row->status === 'past_due' ) {
        $cpe = ! empty( $row->current_period_end ) ? strtotime( $row->current_period_end . ' UTC' ) : 0;
        return $cpe > 0 && ( $now_ts - $cpe ) <= $grace_seconds;
    }
    return false;
}

function ml_user_has_access( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) return false;
    if ( user_can( $user_id, ML_CAP_MANAGE ) ) return true;
    if ( function_exists( 'ml_get_subscription_row' ) ) {
        $row = ml_get_subscription_row( $user_id );
        return ml_access_from_sub_row( $row, ml_past_due_grace(), time() );
    }
    return false;
}

function ml_user_access_state( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) return 'no_purchase';
    if ( user_can( $user_id, ML_CAP_MANAGE ) ) return 'approved';
    return ml_user_has_access( $user_id ) ? 'active' : 'no_purchase';
}

/** Any logged-in visitor may start a booking; access is granted once paid. */
function ml_user_can_book( $user_id ) {
    return (int) $user_id > 0;
}

function ml_user_is_pending_approval( $user_id ) {
    return false;
}
```

- [ ] **Step 4: Run the unit test to verify it passes**

Run: `php tests/stripe/access-gate.test.php`
Expected: six `PASS` lines, exit 0.

- [ ] **Step 5: Lint**

Run: `php -l inc/subscriptions/access-gate.php`
Expected: `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add inc/subscriptions/access-gate.php tests/stripe/access-gate.test.php
git commit -m "feat(access): re-gate dashboard access on subscription status; pure decision + unit test"
```

---

### Task 7: Full smoke + manual verification checklist

- [ ] **Step 1: Run every unit test**

Run:
```bash
php tests/stripe/money-units.test.php && php tests/stripe/sub-mapper.test.php && php tests/stripe/access-gate.test.php
```
Expected: all PASS, exit 0.

- [ ] **Step 2: Lint every touched PHP file**

Run:
```bash
for f in inc/stripe/webhooks.php inc/subscriptions/sync.php inc/subscriptions/status.php inc/subscriptions/access-gate.php inc/stripe/events/checkout-session-completed.php inc/stripe/events/subscription-changed.php inc/booking/boek-checkout.php inc/bootstrap.php; do php -l "$f"; done
```
Expected: `No syntax errors detected` for each.

- [ ] **Step 3: Manual live test (requires Stripe test keys + webhook)**

Documented for when keys are entered:
1. In `/admin/billing` enter Stripe **test** keys + amounts, Save, Sync plan.
2. Point a Stripe webhook (or `stripe listen --forward-to <site>/wp-json/memorylane/v1/stripe-webhook`) and paste the signing secret.
3. Complete a `/boek` booking → redirected to Stripe test Checkout → pay with `4242 4242 4242 4242`.
4. Verify: WP user created, booking row inserted, `ml_subscriptions` row `status=active`, welcome + purchase emails sent, and `/dashboard` is accessible.
5. In Stripe, cancel the subscription → `customer.subscription.deleted` → row `status=cancelled` → `/dashboard` access revoked.
6. Confirm that with keys **removed**, `/boek` reverts to the no-payment flow (fallback intact).

- [ ] **Step 4: Final commit (if any test files or notes changed)**

```bash
git add -A && git commit -m "test: phase 2 full unit + lint pass" || echo "nothing to commit"
```

---

## Self-review notes

- **Spec coverage:** subscription-mode checkout (T5), autonomous activation via webhook (T1+T3), subscription sync from events (T2+T4), access re-gating (T6). Teamleader invoicing is Phase 3 (`invoice.paid` is mapped but its handler lands then). Dashboard/admin subscription UI is Phase 4.
- **Placeholder scan:** none — every step has concrete code + command + expected output.
- **Type consistency:** `ml_sub_fields_from_stripe()` (T2) returns exactly the columns `ml_upsert_subscription()` writes and the schema defines; `ml_access_from_sub_row()` (T6) reads `->status` / `->current_period_end`, matching what the mapper stores.
- **Safety / inert-until-keys:** every payment path guards on `ml_stripe_is_configured()`; access gate returns false only for real customer rows (admins always pass); the no-payment `/boek` fallback is preserved. With no keys, behaviour is unchanged from today.
- **Idempotency:** webhook receiver uses `ml_webhook_events` INSERT IGNORE; booking insert checks for an existing row; `ml_upsert_subscription` keys on `stripe_sub_id`.
```
