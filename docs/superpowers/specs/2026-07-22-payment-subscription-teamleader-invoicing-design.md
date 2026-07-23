# Restore payment + subscriptions, with Teamleader as the invoice source

**Date:** 2026-07-22
**Status:** Design for approval — NOT yet implemented
**Branch:** `feature/payment-subscription-teamleader`

## Goal

Re-introduce paid, self-serve subscriptions into the current **booking-only**
Memory Lane site (the 2026-06-19 redesign stripped Stripe/subscriptions out).
The client (Ottto) chose **Option A**: payment happens **inside the `/boek`
booking flow**, activation and yearly renewals run **fully autonomously** (no
manual admin approval), and **Teamleader Focus** issues the paid invoice as the
**single source of truth** (Teamleader's native Exact Online link then carries
it to the accounting system — we never call Exact directly).

## Confirmed decisions

| Topic | Decision |
| --- | --- |
| Payment provider | **Restore Stripe** — reuse the deleted SDK + plumbing (all 500 files recoverable from `60f1a65^`). |
| Billing model | **Activation fee + yearly auto-renew.** First invoice = activation/setup + year 1; then a recurring **yearly** price. |
| Activation | **Automatic on payment.** No admin-approval / Matterport-acceptance gate. The tour appears when an admin uploads it. |
| Invoicing | **Teamleader only.** Stripe collects the money; Teamleader creates + books + marks-paid the invoice. Drop the old Stripe/dashboard invoice UI. |
| Renewals engine | **Native Stripe Billing** yearly subscription — Stripe auto-charges the saved card and handles retries/dunning. No custom renewal cron. |

## Architecture — selective restore + native Stripe subscriptions

We do **not** `git revert` the strip commit (that would drag back monthly plans,
the reactivation cycle, slot capacity, and legacy wp-admin pages, and collide
with the current booking-only `routes.php` / `access-gate.php` / `bootstrap.php`).
Instead we restore what is safe to reuse and adapt the rest:

**Restore as-is**
- `inc/stripe-php-master/` — vendored Stripe PHP SDK (no conflicts; pure library).

**Restore + adapt (trim to activation + yearly)**
- `inc/stripe/client.php` — Stripe client bootstrap + config (keys, mode).
- `inc/stripe/plans.php` — Product + Price sync. **Trim to two prices:** a
  one-time **activation** price and a recurring **yearly** price. Drop the
  monthly and reactivation prices.
- `inc/stripe/webhooks.php` + `inc/stripe/events/*` — signature-verified webhook
  endpoint. **Keep:** `checkout.session.completed`, `invoice.paid`,
  `customer.subscription.updated`, `customer.subscription.deleted`. **Drop:**
  reactivation events, monthly-specific logic.
- `inc/subscriptions/status.php` + `sync.php` — read/write the `ml_subscriptions`
  row from Stripe events. Adapt to the yearly phase model.

**Add / rewrite**
- `inc/booking/boek-checkout.php` — restore the Stripe branch, but create a
  **Checkout Session in `mode: subscription`** (yearly price) with the activation
  fee added to the first invoice (`add_invoice_items` / one-time line). Save the
  card for renewals. Keep the current no-capacity booking model (no soft-hold).
- `inc/subscriptions/access-gate.php` — re-gate `ml_user_has_access()` on
  subscription status (see below), replacing "any logged-in customer = access".
- `inc/teamleader/invoicing.php` (new) — `ml_tl_push_invoice()` driven by
  `invoice.paid`: find/create Contact → create Deal (first time only) →
  `invoices.create` → `invoices.book` → `invoices.registerPayment`. Reuses the
  existing `ml_tl_request()` client and the retry queue in `booking-sync.php`.
- `inc/teamleader/settings.php` — add **department_id** + **VAT/tax-rate** pickers
  and the `invoices` scope note.
- Admin panel: **Subscriptions** (read-mostly list) + **Stripe plan settings**
  (activation amount, yearly amount, currency, keys) sections, in the
  already-rethemed shell.
- Dashboard: restore a **Subscription** section (status, next renewal, cancel,
  Stripe billing-portal link to update card). **Remove** the old Invoices page.

## Data flow

```
/boek submit
  → Stripe Checkout (mode=subscription: yearly price + one-time activation line)
  → redirect to Stripe hosted checkout → customer pays (card saved)

checkout.session.completed  (webhook)
  → create/activate WP user + insert booking row + mark subscription active
  → send welcome/set-password email   [NO admin approval]

invoice.paid  (webhook — fires at activation AND every yearly renewal)
  → ml_tl_push_invoice():
      contacts.find/add → deals.create (first time) →
      invoices.create → invoices.book → invoices.registerPayment
      (idempotent: keyed by Stripe invoice id; store _ml_tl_invoice_<stripe_id>)
  → Teamleader's native Exact link syncs it to Exact Online

customer.subscription.deleted / past_due  (webhook)
  → update ml_subscriptions.status → access gate reacts
```

The **single `invoice.paid` hook** is what makes activation *and* renewals
autonomous — Stripe drives the recurring charge; we just mirror each paid invoice
into Teamleader.

## Access gate (re-gated)

`ml_user_has_access($id)`:
- admins (`ML_CAP_MANAGE`) → always true.
- customers → true when `ml_subscriptions.status` ∈ {`active`, `trialing`} or
  `past_due` within a short grace window; false when `cancelled`/`unpaid`.
`ml_user_can_book()` stays open (anyone can start a booking; access is granted
once the activation payment succeeds). `ml_user_access_state()` keeps returning
the coarse labels existing dashboard callers already read.

## Data / DB

`inc/db/install.php` **still creates** `ml_subscriptions`, `ml_webhook_events`,
`ml_reactivations`, etc. (only code was deleted in the strip, not the schema),
and `ML_META_STRIPE_CUSTOMER` is still defined in `config.php`. So **no
migration is required** — the tables are ready. `ml_reactivations` stays unused.

## Phasing

1. **Plumbing (inert):** restore SDK + `inc/stripe/*`, wire into `bootstrap.php`,
   add Stripe plan/key settings + Teamleader invoice settings. Site behaviour
   unchanged until keys are entered.
2. **Checkout + autonomous activation:** `/boek` subscription checkout, the
   `checkout.session.completed` + subscription webhooks, and access re-gating.
3. **Teamleader invoicing:** `ml_tl_push_invoice()` on `invoice.paid` (activation
   + renewals), idempotent, via the retry queue.
4. **UI + edge cases:** dashboard Subscription section, admin Subscriptions view,
   remove old Invoices page, and refund → `creditNotes.create`.

## Prerequisites (before Phases 2–3 can be live-tested)

- **Stripe** test + live secret/publishable keys and webhook signing secret.
- **Teamleader**: re-authorise the private integration with the **`invoices`**
  scope; a **department_id** and a **tax rate** (e.g. 21% BE VAT).
- Amounts (activation, yearly) entered in the admin plan settings.

The build proceeds with settings fields as placeholders, so nothing is blocked
waiting on credentials.

## Out of scope (YAGNI — deliberately NOT restored)

- Monthly billing option; customer-driven **reactivation** cycle.
- Slot **capacity**/soft-holds (booking stays no-capacity).
- Legacy **wp-admin** pages (`inc/admin/*`) — the slim `/admin` panel replaces them.
- Old dashboard **Invoices** page (Teamleader is authoritative).

## Risks / notes

- **Function/name conflicts:** restored files may redefine helpers that changed
  since June. Each restored file is reviewed before wiring into `bootstrap.php`;
  restore is staged/committed per phase so it stays revertible.
- **Idempotency:** Stripe retries webhooks; every handler (activation, invoice
  push) must be safe to run twice (guarded by `ml_webhook_events` + stored ids).
- **VAT correctness:** the Teamleader line uses the configured tax rate;
  Stripe's charged amount and the Teamleader invoice total must match to the cent.
- **Refunds:** issue a Teamleader credit note (Phase 4); until then refunds are
  handled manually in Teamleader.
- Work happens on `feature/payment-subscription-teamleader` with staged commits.
