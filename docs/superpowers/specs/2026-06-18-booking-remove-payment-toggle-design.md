# Booking — toggle-able payment removal (`/boek`)

**Date:** 2026-06-18
**Status:** Approved design, pending implementation plan

## Goal

The client wants to remove payment from the public booking flow **for now**. A
visitor should be able to pick a slot and request a booking on `/boek` without
being sent to Stripe. The change must be easily reversible: a single admin
toggle restores the exact current paid flow with no code changes.

## Background

There are two booking flows today:

1. **Public `/boek`** (`template-parts/public/boek.php` + `inc/booking/boek-checkout.php`):
   new visitor picks a slot, fills name/email/phone/address, is redirected to a
   Stripe Checkout session (mode=payment, setup + Matterport fee). On
   `checkout.session.completed` the webhook
   (`inc/stripe/events/checkout-session-completed.php`) creates the WP user,
   records the payment, sets `ML_META_SETUP_STATE = pending_approval`, and
   inserts the `bookings` row. **This is the only flow with payment.**
2. **Dashboard booking** (`inc/booking/bookings.php`, `ml_booking_request`):
   existing logged-in customer requests a slot — already no payment. Out of
   scope; untouched.

This spec changes only flow #1.

## Decisions (confirmed with client)

- **Reversibility:** admin toggle, not a hard removal.
- **Money display:** hidden entirely on `/boek` when payment is off.
- **Account state:** new bookings land in `pending_approval` (admin still does
  the scan + "Approve & activate access" step).
- **Welcome email / login:** **not** sent in the no-payment path. The user row
  is still created under the hood (the `bookings` table needs a `user_id`), but
  no set-password welcome email goes out — the admin handles access manually.
  A booking-confirmation email still goes to the customer; admins are notified.
- **Toggle default:** **OFF** (payment not required). Delivers the desired
  state immediately on deploy; flip ON to restore Stripe.

## Design

### 1. Setting + helper

- New option constant `ML_OPT_BOOKING_REQUIRE_PAYMENT = 'ml_booking_require_payment'`
  in `inc/config.php`.
- Helper `ml_booking_payment_required(): bool` — reads the option, **default
  `false`** (payment not required).
- UI: a checkbox in the `/admin` settings panel
  (`template-parts/admin-panel/settings.php`), under a new **"Booking"**
  sub-heading near the existing cancel/reschedule hour fields:
  *"Require online payment for new bookings"*. Persisted in the existing
  `ml_ap_settings_save` handler (`inc/admin-panel/handlers.php`). A checkbox is
  unchecked-means-off, so save logic stores `1` when present, `0` otherwise.
- The legacy wp-admin settings page (`inc/admin/settings.php`) is retired and is
  **not** updated.

### 2. `/boek` REST endpoint — branch on the toggle

In `ml_rest_boek` (`inc/booking/boek-checkout.php`):

- **Payment required (ON):** unchanged — the `ml_stripe_is_configured()` gate,
  soft-hold, and Stripe Checkout session creation all run as today.
- **Payment not required (OFF):** skip the Stripe-configured gate. After the
  same nonce/rate-limit/field/slot validation and `ml_increment_slot_booked`
  soft-hold, call the new provisioning helper and return
  `{ ok: true, url: home_url('/checkout/success?booking=1') }`. On failure,
  release the soft-hold (`ml_decrement_slot_booked`) exactly like the Stripe
  path.

### 3. No-payment provisioning helper

New function `ml_boek_provision_no_payment( array $data, object $slot ): void`
(or returns a result), in `inc/booking/boek-checkout.php`. It mirrors the
webhook's user+booking creation, minus money:

- Find or create the WP user (reuse `ml_unique_username`), assign
  `ML_ROLE_CUSTOMER`, save phone / address / lang user-meta.
- Set `ML_META_SETUP_STATE = ML_SETUP_STATE_PENDING`,
  `ML_META_SETUP_AMOUNT = 0`, currency from the plan; no payment-intent meta.
- Insert the `bookings` row (`service_type = 'initial_scan'`,
  `status = 'requested'`, `customer_notes`, `scheduled_for`), idempotent per
  `(user_id, slot_id, service_type)` — same guard as the webhook. Do **not**
  double-increment `booked_count` (the soft-hold already did).
- Emails: send **`booking_requested`** to the customer (confirms the slot, no
  password link) and **`admin_booking_requested`** to `ml_admin_recipients()`.
  Do **not** send `welcome_set_password` or `purchase_confirmation` — the user
  row is created under the hood, but no usable login is provisioned now; the
  admin grants access manually at approval time.

The duplicated user-creation block between this helper and the webhook is small;
if it reads cleanly we may extract a shared `ml_find_or_create_customer()` later,
but that is not required for this change.

### 4. `/boek` page when payment is OFF

In `template-parts/public/boek.php`, gate on `ml_booking_payment_required()`:

- Hide the "Te betalen nu" price/total card (the `ml-card` block) entirely.
- Swap copy: subtitle and the submit button ("Bevestig en betaal" → e.g.
  "Bevestig je boeking") via new i18n strings in `inc/i18n/strings/{nl,en}.php`
  (`boek.cta_no_pay`, `boek.subtitle_no_pay`). The price-injected `$fee`/`$cur`
  lines are simply not rendered.
- The form's submit JS is unchanged — it already redirects to the returned
  `url`.

### 5. Thank-you page

Reuse the existing `/checkout/success` route (no new rewrite rule, no permalink
flush). In `template-parts/auth/checkout-success.php`, when `?booking=1` is
present, render booking-appropriate copy instead of the payment copy:

- Title: "Bedankt voor je boeking" / "Thanks for your booking".
- Body: "We hebben je boeking ontvangen. We nemen binnenkort contact op om te
  bevestigen." (no "we received your payment", no set-password instruction).
- Drop the "Log in" button for this variant (the visitor has no account access
  yet); keep a link back home.
- New i18n strings for these (`checkout.booking_success.*`).

## Out of scope

- Dashboard booking flow (already free).
- Reactivation / subscription flows.
- Removing or refactoring any Stripe code — it stays intact behind the toggle.

## Reversibility summary

| Toggle | `/boek` behavior |
| --- | --- |
| OFF (default) | Free booking: validate → create user + booking (pending_approval) → confirmation emails → thank-you page. No Stripe. |
| ON | Current behavior: validate → Stripe Checkout → webhook creates user + booking on payment. |
