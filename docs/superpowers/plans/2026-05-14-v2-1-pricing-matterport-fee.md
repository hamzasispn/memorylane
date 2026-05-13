# V2-1 Pricing — Matterport activation fee at first purchase

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Charge the Matterport activation fee at first purchase too (not just at reactivation), by adding it as a second line item on the `/boek` Stripe Checkout Session. Same Stripe Price ID is used both moments.

**Architecture:** No new infrastructure. The `reactivation_price_id` already exists in the Plan-sync system. We add it as a second `line_items` entry on the public checkout, rename the admin UI label to "Matterport activation fee" so it reads correctly in both contexts, and update first-purchase email copy.

**Tech Stack:** PHP / WordPress theme, Stripe PHP SDK.

**Spec reference:** [2026-05-14-memory-lane-v2-design.md §2](../specs/2026-05-14-memory-lane-v2-design.md)

---

## File map

| File | Change |
|---|---|
| `inc/admin/settings.php` | Rename "Reactivation fee" UI label → "Matterport activation fee" (3 spots). Update description. |
| `inc/booking/boek-checkout.php` | Add `reactivation_price_id` as second `line_items` entry. Require it in config check. |
| `inc/stripe/client.php` | Update `ml_stripe_is_configured()` to also require `reactivation_price_id` (since `/boek` now needs it). |
| `inc/emails/templates/welcome.php` *(or wherever first-purchase confirmation is)* | Mention Matterport activation fee in the payment summary line. |

No DB migration. No test files (theme has no PHPUnit harness; verification is manual via Stripe Test Mode).

---

### Task 1: Rename admin label "Reactivation fee" → "Matterport activation fee"

**Files:**
- Modify: `inc/admin/settings.php:154`
- Modify: `inc/admin/settings.php:221-225`

- [ ] **Step 1: Update the synced-status table label**

In `inc/admin/settings.php` find line 154:

```php
<?php foreach ( array( 'setup' => 'Setup + Year 1 (one-time)', 'monthly' => 'Monthly hosting', 'annual' => 'Annual hosting (reactivation only)', 'reactivation' => 'Reactivation fee (one-time)' ) as $k => $label ) : ?>
```

Replace with:

```php
<?php foreach ( array( 'setup' => 'Setup + Year 1 (one-time)', 'monthly' => 'Monthly hosting', 'annual' => 'Annual hosting (reactivation only)', 'reactivation' => 'Matterport activation fee (one-time)' ) as $k => $label ) : ?>
```

- [ ] **Step 2: Update the form field label**

In `inc/admin/settings.php:221`:

```php
<th scope="row"><label><?php esc_html_e( 'Reactivation fee', 'memorylane' ); ?></label></th>
```

Replace with:

```php
<th scope="row"><label><?php esc_html_e( 'Matterport activation fee', 'memorylane' ); ?></label></th>
```

- [ ] **Step 3: Update the field description**

In `inc/admin/settings.php:224`:

```php
<p class="description"><?php esc_html_e( 'One-time fee to reactivate an archived tour.', 'memorylane' ); ?></p>
```

Replace with:

```php
<p class="description"><?php esc_html_e( 'One-time fee charged at first purchase and at every reactivation. Covers Matterport activation cost.', 'memorylane' ); ?></p>
```

- [ ] **Step 4: Manual verify**

Open `wp-admin → Memory Lane → Settings → Stripe`. Confirm the field renders as **"Matterport activation fee"** with the new description, and the synced-status table label reads **"Matterport activation fee (one-time)"**.

- [ ] **Step 5: Commit**

```bash
git add inc/admin/settings.php
git commit -m "refactor(admin): rename 'Reactivation fee' label to 'Matterport activation fee'"
```

---

### Task 2: Require Matterport activation price ID in `/boek` config check

**Files:**
- Modify: `inc/booking/boek-checkout.php:32-34`

The current check is:

```php
if ( ! ml_stripe_is_configured() || ! ml_stripe_setup_price_id() ) {
    return new WP_REST_Response( array( 'ok' => false, 'error' => 'payments_not_configured' ), 503 );
}
```

`/boek` will fail at runtime if the Matterport activation fee price ID is missing, so we should fail fast at the config check.

- [ ] **Step 1: Tighten the config check**

In `inc/booking/boek-checkout.php:32-34`, replace:

```php
if ( ! ml_stripe_is_configured() || ! ml_stripe_setup_price_id() ) {
    return new WP_REST_Response( array( 'ok' => false, 'error' => 'payments_not_configured' ), 503 );
}
```

with:

```php
if ( ! ml_stripe_is_configured() || ! ml_stripe_setup_price_id() || ! ml_stripe_reactivation_price_id() ) {
    return new WP_REST_Response( array( 'ok' => false, 'error' => 'payments_not_configured' ), 503 );
}
```

- [ ] **Step 2: Commit (still works — no behaviour change yet, just stricter precondition)**

```bash
git add inc/booking/boek-checkout.php
git commit -m "feat(boek): require matterport activation price id before opening checkout"
```

---

### Task 3: Add Matterport activation fee as second line item on `/boek` checkout

**Files:**
- Modify: `inc/booking/boek-checkout.php:77-98`

The current `line_items` is one entry:

```php
'line_items'  => array( array( 'price' => ml_stripe_setup_price_id(), 'quantity' => 1 ) ),
```

We add the activation fee as a second entry.

- [ ] **Step 1: Update line_items to include both prices**

In `inc/booking/boek-checkout.php:79`, replace:

```php
'line_items'  => array( array( 'price' => ml_stripe_setup_price_id(), 'quantity' => 1 ) ),
```

with:

```php
'line_items'  => array(
    array( 'price' => ml_stripe_setup_price_id(),        'quantity' => 1 ),
    array( 'price' => ml_stripe_reactivation_price_id(), 'quantity' => 1 ),
),
```

- [ ] **Step 2: Add metadata flag so admin notification email can list what was paid**

In the same `sessions->create()` call, find the `metadata` array around line 88. Replace:

```php
'metadata' => array(
    'ml_intent'   => 'initial_purchase_with_slot',
    'ml_lang'     => ml_current_lang(),
    'ml_slot_id'  => (string) $slot_id,
    'ml_name'     => substr( $name, 0, 200 ),
    'ml_phone'    => substr( $phone, 0, 80 ),
    'ml_address'  => substr( $address, 0, 200 ),
    'ml_notes'    => substr( $notes, 0, 400 ),
),
```

with:

```php
'metadata' => array(
    'ml_intent'                => 'initial_purchase_with_slot',
    'ml_includes_matterport'   => '1',
    'ml_lang'                  => ml_current_lang(),
    'ml_slot_id'               => (string) $slot_id,
    'ml_name'                  => substr( $name, 0, 200 ),
    'ml_phone'                 => substr( $phone, 0, 80 ),
    'ml_address'               => substr( $address, 0, 200 ),
    'ml_notes'                 => substr( $notes, 0, 400 ),
),
```

- [ ] **Step 3: Manual verify in Stripe Test Mode**

1. Open `/boek` in browser.
2. Pick any slot, fill name/email/phone/address, click continue.
3. On the Stripe Checkout page, confirm the order summary shows **two line items**:
   - Memory Lane Setup + Year 1 — €299.00 *(or your configured amount)*
   - Memory Lane Reactivation — €49.00 *(or your configured amount)*
   - Total = sum of both
4. Complete payment with `4242 4242 4242 4242`.
5. Confirm redirect to `/checkout/success`.
6. In Stripe Dashboard → Payments, confirm the resulting PaymentIntent has both line items and metadata `ml_includes_matterport=1`.

- [ ] **Step 4: Commit**

```bash
git add inc/booking/boek-checkout.php
git commit -m "feat(boek): charge Matterport activation fee as second line item at first purchase"
```

---

### Task 4: Tighten `ml_stripe_is_configured()` to include Matterport activation price

**Files:**
- Modify: `inc/stripe/client.php:51-58`

Once `/boek` requires the Matterport activation price ID, the whole site should treat it as a required field — so the "Stripe not yet connected" banner in `wp-admin` triggers if it's missing.

- [ ] **Step 1: Update the function**

In `inc/stripe/client.php:51-58`, replace:

```php
function ml_stripe_is_configured() {
    return (bool) (
        ml_stripe_secret()
        && ml_stripe_publishable()
        && ml_stripe_setup_price_id()
        && ml_stripe_monthly_price_id()
    );
}
```

with:

```php
function ml_stripe_is_configured() {
    return (bool) (
        ml_stripe_secret()
        && ml_stripe_publishable()
        && ml_stripe_setup_price_id()
        && ml_stripe_monthly_price_id()
        && ml_stripe_reactivation_price_id()
    );
}
```

- [ ] **Step 2: Manual verify**

1. In `wp-admin → Memory Lane → Settings → Stripe`, temporarily clear the "Matterport activation fee" amount and click Save (no sync).
2. Look at the overview page banner — it should now show "Stripe not connected / configured" because the price ID is empty.
3. Restore the amount, click Sync. Banner clears.

- [ ] **Step 3: Commit**

```bash
git add inc/stripe/client.php
git commit -m "feat(stripe): treat matterport activation price as required for full config"
```

---

### Task 5: Update first-purchase confirmation email copy

**Files:**
- Modify: whichever email template renders the welcome / first-purchase confirmation (locate via grep).

- [ ] **Step 1: Locate the template**

Run:

```bash
grep -rln "Setup + Year 1\|welcome\|set your password" inc/emails/ template-parts/
```

Open the matching file. Most likely `inc/emails/templates/welcome-purchase.php` or similar.

- [ ] **Step 2: Update the payment summary block**

Find the line that mentions "Setup + Year 1" (or the amount line). Below it, add a line mentioning the Matterport activation fee.

Example before:

```php
<p>Setup + Year 1: <strong><?php echo esc_html( $year_one_amount_formatted ); ?></strong></p>
```

Example after:

```php
<p>Setup + Year 1: <strong><?php echo esc_html( $year_one_amount_formatted ); ?></strong></p>
<p>Matterport activation fee: <strong><?php echo esc_html( $matterport_amount_formatted ); ?></strong></p>
<p>Total paid: <strong><?php echo esc_html( $total_amount_formatted ); ?></strong></p>
```

(If the template doesn't currently itemise — i.e. just shows a total — leave the total as-is and add a single line: "Includes Matterport activation fee.")

If no such email template exists yet (V1 was still in flight), skip this task and log it in §15 of the V2 spec as a follow-up.

- [ ] **Step 3: Manual verify**

Trigger a test purchase. Check the welcome email lists the Matterport fee or mentions it as part of the total.

- [ ] **Step 4: Commit**

```bash
git add inc/emails/
git commit -m "docs(emails): mention Matterport activation fee in first-purchase confirmation"
```

---

## Verification checklist (run after all tasks)

- [ ] `wp-admin → Memory Lane → Settings → Stripe` shows "Matterport activation fee" label and updated description.
- [ ] `/boek` Stripe Checkout shows two line items with correct totals.
- [ ] Stripe Dashboard → resulting PaymentIntent shows two line items and metadata `ml_includes_matterport=1`.
- [ ] Welcome email mentions the activation fee.
- [ ] Reactivation flow still works (no regression) — pay reactivation, admin approves, monthly sub restarts.

---

## Done definition

V2-1 is shipped when:

- A new customer paying on `/boek` is charged Setup+Year1 **plus** Matterport activation fee in one Stripe Checkout session.
- The admin UI calls the fee "Matterport activation fee" everywhere.
- The reactivation flow (V1 §13) continues to work without any code change.
- A returning admin can re-sync prices without errors.
