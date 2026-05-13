# Memory Lane V2 — Design Spec

**Date:** 2026-05-14
**Author:** brainstorming session, hafeeznafeez070@gmail.com
**Status:** Approved — ready for implementation plan
**Supersedes parts of:** [2026-05-13-memory-lane-backend-design.md](2026-05-13-memory-lane-backend-design.md)
**Source of truth:** Client brief (pasted in chat 2026-05-14) + answers to clarifying questions.

This spec captures the changes to the V1 backend that came out of the 2026-05-14 review against the client's brief. Where V1 and V2 disagree, V2 wins.

---

## 1. Changes at a glance

| # | Area | V1 (current) | V2 (this spec) |
|---|---|---|---|
| 1 | First-purchase checkout | One line item: Setup + Year 1 | **Two line items**: Setup + Year 1 **and** Matterport activation fee |
| 2 | Reactivation fee | Separate "reactivation fee" SKU | **Same SKU as Matterport activation fee.** One amount, used at both moments |
| 3 | Slot creation | Admin creates slots manually one by one | Admin sets **working hours + blocked dates**, system **auto-generates** slots |
| 4 | Booking UI | Plain list of slots | **Date grid (8 cols) + time slots (4 cols)**. Times dimmed until date picked |
| 5 | Customer dashboard | Tours + Subscription + Booking | Adds **Invoices** + **Billing details** (card, address, VAT) |
| 6 | Admin UI | WordPress admin sub-pages | **Custom `/admin` panel** outside WP admin, custom UI |
| 7 | Cancel → archive | Period-end revoke (configurable grace) | Period-end revoke, fixed **7-day grace** after payment failure |
| 8 | Tables | No pagination | All admin tables (customers, tours, bookings, slots, subs, invoices, logs) **paginated** |

Everything not listed here is unchanged from V1.

---

## 2. Pricing & fees

### 2.1 Three configurable amounts in admin Settings

| Setting | Used when | Stripe type |
|---|---|---|
| Setup + Year 1 | First purchase | One-time price |
| Matterport activation fee | First purchase **and** every reactivation (same amount both times) | One-time price |
| Monthly subscription | After 365-day free year, then every month forever | Recurring price |

Renaming: V1's admin setting "Reactivation fee" is renamed to **"Matterport activation fee"** so it reads correctly in both contexts. Same Stripe Price ID is used at first purchase and at reactivation.

### 2.2 First-purchase Stripe Checkout

One Stripe Checkout Session in `mode=payment` with **two line items**:

```
line_items = [
  { price: setup_year1_price_id,           quantity: 1 },
  { price: matterport_activation_price_id, quantity: 1 },
]
```

Customer sees one total, pays once.

`metadata.ml_intent = 'initial_purchase_with_slot'` — unchanged from V1.

### 2.3 Reactivation Stripe Checkout

One Stripe Checkout Session, one line item (Matterport activation fee). On payment success:
- Tour status → "Reactivating (≤ 8h)"
- Admin emailed to flip Matterport
- Monthly subscription restarts when admin approves reactivation

(Detailed flow inherited from §13 of V1 reactivation cycle.)

### 2.4 Subscription creation (unchanged from V1)

Subscription is **not** created at checkout. It's created later in the Stripe API when admin clicks "Approve access," with `trial_period_days = 365`. After the trial, Stripe auto-bills monthly.

---

## 3. Working hours & slot generation

### 3.1 Replace V1 manual slot creation

V1: admin creates each slot row by hand.
V2: admin sets the rules, system generates slots.

### 3.2 Admin settings (in /admin → "Slots & working hours")

| Setting | Type | Example |
|---|---|---|
| `working_hours[Mon..Sun]` | per-day: `enabled` + `start` + `end` | Mon 09:00–17:00, Sat 10:00–14:00, Sun closed |
| `slot_length_minutes` | int | 60 |
| `capacity_per_slot` | int | 1 |
| `booking_window_days` | int | 60 |
| `blocked_dates[]` | array of `YYYY-MM-DD` | `["2026-12-25", "2027-01-01"]` |

### 3.3 Generation strategy

**Lazy generation, not pre-materialised.** The booking page asks the backend "what slots are available for date X?" The backend computes them on the fly from `working_hours[weekday(X)]` minus `blocked_dates` minus already-booked slots.

A booked slot lives in a real `bookings` row tied to a `slot_id`. But the slot record itself only exists once someone tries to book it. Until then, slots are virtual (computed from rules).

This avoids:
- Stale slot rows if admin changes working hours.
- Cron jobs to generate slots N days ahead.
- Re-generation when blocked dates change.

### 3.4 Soft-hold (unchanged from V1)

When a customer enters Stripe Checkout, a real row is inserted in `availability_slots` (if not yet present) with `booked_count = 1`. Released after 1h if customer never returns from Stripe (`ml_cron_release_stale_holds`).

### 3.5 Schema impact

V1 `availability_slots` table stays. New `ml_settings` rows under prefix `booking_*` for the rules above.

---

## 4. Booking UI (`/boek` and `/dashboard/booking`)

### 4.1 Layout (desktop, ≥ md)

```
+-------------------------------------------------------+
|  Pick your appointment                                |
+-------------------------------------+-----------------+
|  DATES GRID  (8 of 12 cols)         |  TIMES          |
|                                     |  (4 of 12 cols) |
|  [Mon 18] [Tue 19] [Wed 20]         |                 |
|  [Thu 21] [Fri 22] [Sat 23]         |  09:00          |
|  [Mon 25] [Tue 26] [Wed 27] ...     |  10:00          |
|                                     |  11:00          |
|                                     |  ...            |
|                                     |  16:00          |
+-------------------------------------+-----------------+
|  Name • Email • Phone • Address • Notes               |
|  [ Continue to payment ]                              |
+-------------------------------------------------------+
```

Mobile (`< md`): stack — dates on top, times below.

### 4.2 Interaction states

| State | Times block |
|---|---|
| No date picked | Dimmed (opacity ~ 40 %), not clickable, label "Pick a date first" |
| Date picked | Bright, clickable. Times at capacity shown as struck-through "taken" |
| Date + time picked | Selected time highlighted; form below becomes interactable |
| Past date | Date card greyed, not clickable |
| Blocked date | Date card greyed with small "closed" label |

### 4.3 Backend endpoint

New REST endpoint:

```
GET /wp-json/memorylane/v1/booking/slots?date=YYYY-MM-DD
→ { date, times: [ { time: "09:00", available: true|false } ] }
```

Returns the list of times for that date, with availability per time.

Existing `POST /wp-json/memorylane/v1/boek` (public first purchase) stays. New similar endpoint for logged-in `POST /wp-json/memorylane/v1/dashboard/booking` already exists.

### 4.4 Both flows use the same UI component

`/boek` (public, pre-purchase) and `/dashboard/booking` (logged-in, post-purchase, e.g. if rescheduling) render the same Twig/PHP partial for the date+time picker.

---

## 5. Customer dashboard

### 5.1 Cards on `/dashboard`

| Card | Always shown? | Content |
|---|---|---|
| **Tour** | Yes | One card per assigned tour. Big "View tour" CTA if active. Big "Reactivate" CTA if archived. "Activating (≤ 8h)" badge if pending. |
| **Subscription** | Yes | Status pill, next billing date, monthly amount, "Manage" link to Stripe customer portal, "Cancel subscription" button (period-end). When archived, shows "Reactivate" CTA. |
| **Next appointment** | Yes | Booking details (date, time) and status (Requested / Confirmed / Completed). "Book scan" CTA if none. |
| **Invoices** | Yes | List of last 12 invoices. Columns: date, amount, status (paid/open/failed), "Download PDF". Pagination beyond 12. |
| **Billing details** | Yes | Card on file (brand + last 4), billing address, optional company name + BTW number. "Update card" + "Edit details" buttons. |

### 5.2 Card change + billing edit

- **Update card** → opens Stripe Customer Portal in same window (Stripe handles PCI).
- **Edit billing details** → inline form. Submits to:
  ```
  POST /wp-json/memorylane/v1/dashboard/billing
  ```
  Server updates the Stripe Customer object via `stripe.customers.update(id, { name, address, tax_id_data: [...] })` and mirrors the change in WP user meta.

### 5.3 Invoices

- Pulled live from Stripe (`stripe.invoices.list({ customer })`).
- PDF download = Stripe-hosted PDF URL (`invoice.invoice_pdf`).
- Cached for 5 minutes in a transient to avoid repeated API hits.

### 5.4 BTW / company customers

Add to billing details form: optional "Bedrijfsnaam" + "BTW-nummer" fields. If filled, written to Stripe Customer `tax_id_data` so they appear on the invoice. Personal customers leave both blank.

---

## 6. Subscription lifecycle

### 6.1 Cancel flow

1. Customer clicks "Cancel subscription" in dashboard.
2. Backend calls `stripe.subscriptions.update(id, { cancel_at_period_end: true })`.
3. Stripe webhook `customer.subscription.updated` fires → local mirror flagged.
4. Customer keeps access until period end (Stripe handles the actual end timestamp).
5. At period end, `customer.subscription.deleted` webhook fires → tour status flips to "Archived", customer notified by email, admin notified by email ("please un-publish in Matterport").

### 6.2 Payment-failure flow

1. Stripe retries failed monthly charge 4 times over ~3 weeks (Stripe default schedule).
2. During retries, customer sees "Past due" pill in dashboard but keeps access.
3. After final retry fails, Stripe sets subscription status to `unpaid` and emits `customer.subscription.updated`.
4. Our handler records `failed_at = NOW()` in the local subscription mirror and emails the customer.
5. **7-day grace period** starts. Cron `ml_cron_revoke_overdue` runs hourly, archives tours whose `failed_at + 7 days < NOW()`.
6. On archive: customer + admin emails sent.

### 6.3 Reactivation flow (V1 §13, unchanged)

1. Archived customer logs in, sees "Reactivate" CTA on dashboard.
2. Click → Stripe Checkout for Matterport activation fee.
3. On payment, status flips to "Reactivating (≤ 8h)", admin emailed.
4. Admin manually re-publishes in Matterport, then clicks "Approve reactivation" in /admin.
5. Stripe subscription restarted (new subscription, fresh monthly recurring).
6. Tour visible again. Customer emailed.

### 6.4 What customer sees per state

| State | Tour viewer | Tour card in dashboard | Reactivate button |
|---|---|---|---|
| Pending activation (≤ 8h after first purchase) | Hidden | "Activating, max 8 hours" badge | No |
| Active | Visible | "Active" pill | No |
| Cancelled, in paid period | Visible until period end | "Ending DD-MM-YYYY" pill | No |
| Past due | Visible during retries | "Past due — update card" | No |
| Archived (cancelled or grace-expired) | 403 | "Archived" pill | **Yes** |
| Reactivating (≤ 8h after reactivation pay) | 403 | "Reactivating, max 8 hours" | No |

---

## 7. Admin panel (NEW — `/admin`)

### 7.1 Why

WP admin sub-pages get cramped with this many domains. Client wants one clean place. We build a custom panel under `/admin` (route renders a full-page template, not inside wp-admin chrome).

### 7.2 Auth

- Existing WordPress login (no parallel auth system).
- Route `/admin` requires user with capability `manage_options` (= admin role).
- Non-admins → 403.
- Logged-out → redirect to `/login?redirect=/admin`.

### 7.3 Pages

| URL | Purpose |
|---|---|
| `/admin` | Overview — KPI cards (active subs, pending activations ≥ 8h, past-due, webhook errors), banner if Stripe disconnected, today's bookings list. |
| `/admin/customers` | Paginated table. Search by name/email. Row → customer detail. |
| `/admin/customers/{id}` | Profile, subscriptions, tours, bookings, invoices, actions (resend welcome, approve access, manual deactivate, open in Stripe). |
| `/admin/tours` | Paginated table. Add new (title + customer + embed code + status). Edit. |
| `/admin/bookings` | Paginated table. Filter by status. Confirm / Cancel / Complete / Reschedule per row. |
| `/admin/subscriptions` | Paginated table. Filter by status. Open in Stripe Dashboard link per row. |
| `/admin/invoices` | Paginated table. Pulled live from Stripe (cached). Filter by customer / status. |
| `/admin/slots` | Working-hours form, slot length, capacity, blocked-dates manager, booking window. (No per-slot rows shown — slots are virtual.) |
| `/admin/settings` | Stripe keys, three prices, email templates, languages, general. |
| `/admin/logs` | Webhooks log + emails log, paginated, with retry buttons. |

### 7.4 Pagination

Standard URL pattern: `?page=N&per_page=M` (defaults: page=1, per_page=25). Server-side pagination on every table. Apply to:

- `/admin/customers`
- `/admin/tours`
- `/admin/bookings`
- `/admin/subscriptions`
- `/admin/invoices`
- `/admin/logs` (webhooks + emails)
- Customer's own `/dashboard` invoices section (per_page=12)

All table queries use `SQL_CALC_FOUND_ROWS` (or two queries) for total count → render footer "Page 3 of 17 ← prev | next →".

### 7.5 UI style

- Sidebar nav (left), main content (right), breadcrumb header.
- Same brand palette as customer side, but denser layout (Linear / Stripe Dashboard vibe).
- Tailwind classes (already in theme).
- Mobile: sidebar collapses to hamburger.

### 7.6 V1 WP admin pages

Remove the duplicate "Memory Lane" sub-menu pages after `/admin` reaches feature parity. Don't run two admin surfaces in parallel.

---

## 8. Data model changes

### 8.1 Tables (delta from V1)

| Table | Change |
|---|---|
| `ml_settings` | New keys: `booking_working_hours_json`, `booking_slot_length_minutes`, `booking_capacity_per_slot`, `booking_window_days`, `booking_blocked_dates_json`, `grace_period_days` (default 7). Remove old `matterport_activation_fee_price_id` → renamed (same column, just keep the column, no migration needed — only the admin label changes). |
| `availability_slots` | Stays. Now populated lazily on first booking, not pre-generated. |
| `bookings` | Stays. No schema change. |
| `subscriptions` | New nullable column `payment_failed_at DATETIME` to power the 7-day grace cron. |
| `tours` | Stays. No schema change. |

### 8.2 User meta

- `ml_billing_company_name` (new, optional)
- `ml_billing_vat_number` (new, optional)
- These mirror what's in the Stripe Customer's `tax_id_data` so we don't hit Stripe on every dashboard render.

---

## 9. Cron jobs (delta from V1)

| Cron | What |
|---|---|
| `ml_cron_revoke_overdue` (hourly) | New. Find subs with `payment_failed_at IS NOT NULL AND payment_failed_at + grace_period_days < NOW()` → archive tour. |
| `ml_cron_release_stale_holds` (hourly) | Unchanged. |
| All other V1 crons | Unchanged. |

---

## 10. Emails (delta from V1)

V1 set already covers: welcome, payment success, payment failure, booking confirmation/reminder, cancel, year-1 ending, reactivation. **No new templates needed for V2**, but two existing ones get small wording tweaks:

- **First-purchase confirmation** — now mentions two line items (Setup + Matterport activation).
- **Reactivation confirmation** — now mentions Matterport activation fee by name.

---

## 11. Routes / endpoints (delta from V1)

### Public

- `GET /boek` — date+time picker (new layout).
- `POST /wp-json/memorylane/v1/boek` — unchanged signature, but Checkout Session now has two line items.

### Logged-in customer

- `GET /dashboard` — adds Invoices + Billing details cards.
- `GET /wp-json/memorylane/v1/booking/slots?date=…` — **new**, used by booking pickers.
- `POST /wp-json/memorylane/v1/dashboard/billing` — **new**, update Stripe customer billing details.
- `POST /wp-json/memorylane/v1/dashboard/reactivate` — already in V1 §13.

### Admin

- `GET /admin/*` — **new** custom-rendered admin pages.
- `POST /wp-json/memorylane/v1/admin/*` — **new** server actions for each admin table (confirm booking, assign tour, approve access, save settings, etc.).

---

## 12. Acceptance criteria

A reviewer can tick these off the V2 implementation:

1. On `/boek`, a visitor sees a date grid (8 cols) and a dim times column. Clicking a date brightens the times. Clicking a time + filling the form + paying creates a Stripe Checkout with two line items.
2. After payment success, the customer gets a welcome email and a booking-pending email. The admin gets a "new purchase" email.
3. Admin in `/admin/customers` sees the new customer with status "Pending activation."
4. After admin approves access, the customer's dashboard shows the tour, the subscription card (status Active, next billing date 365 days out, monthly amount), an empty invoices list, and a billing-details card.
5. Customer in dashboard can click "Update card" → goes to Stripe portal → returns. New card is reflected.
6. Customer cancels subscription → status flips to "Ending DD-MM-YYYY". Tour stays viewable. At period end, tour archives, "Reactivate" CTA appears.
7. Customer clicks "Reactivate" → Stripe checkout for Matterport activation fee. On payment, status flips to "Reactivating (≤ 8h)". After admin approves, monthly sub restarts, tour visible again.
8. Admin in `/admin/slots` edits working hours → next visit to `/boek` reflects the change immediately (no cron).
9. All admin tables paginate at 25 rows per page with Prev/Next.
10. Payment-failure simulation: subscription set to `unpaid` → after 7 days, cron archives the tour.

---

## 13. Out of scope (V2)

- Matterport Cloud API integration (client said "API is plus, not mandatory" — manual stays).
- French / extra languages.
- SMS notifications.
- Custom invoice template / numbering (use Stripe invoice PDFs).
- Multiple subscriptions per customer (one customer = one subscription; can have multiple tours under it).
- Two-factor auth for admin (defer to a future phase).
- Mobile-native app.

---

## 14. Implementation phases (proposed for the plan)

The implementation plan will break V2 into roughly these phases. Order matters because each phase unblocks the next.

| Phase | What | Touches |
|---|---|---|
| V2-1 | Pricing change — add Matterport fee as second line item in /boek checkout; rename admin label | inc/booking/boek-checkout.php, admin settings page |
| V2-2 | Booking UI rewrite — date grid + time slots, dimmed-state, new REST endpoint, lazy slot model | pages, template-parts, inc/booking/ |
| V2-3 | Working hours config — replace manual slot creation in admin with rules form | inc/booking/admin.php (delete) → /admin/slots (new) |
| V2-4 | Dashboard additions — Invoices card, Billing details card with Stripe sync, BTW/company fields | dashboard template, inc/subscriptions/, new REST endpoint |
| V2-5 | Payment-failure grace — `payment_failed_at` column, `ml_cron_revoke_overdue`, dashboard "past due" pill | inc/subscriptions/, cron file |
| V2-6 | Admin panel scaffolding — `/admin` route, auth gate, layout shell, Overview page | new inc/admin/ folder |
| V2-7 | Admin pages — Customers, Tours, Bookings, Subscriptions, Invoices, Logs, Settings (all paginated) | inc/admin/ |
| V2-8 | Pagination on dashboard invoices | dashboard template |
| V2-9 | Remove old WP admin sub-pages once /admin is at parity | delete inc/admin/wp-admin-* |
| V2-10 | Email copy tweaks for new fee structure | inc/emails/ |

Each phase produces shippable, testable code. We commit after each phase.

---

## 15. Open items (none currently)

All client questions answered as of 2026-05-14. If a new ambiguity emerges during implementation, log it here.
