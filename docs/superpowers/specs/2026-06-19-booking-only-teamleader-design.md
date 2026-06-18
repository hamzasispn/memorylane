# Memory Lane → booking-only site with Teamleader hand-off

**Date:** 2026-06-19
**Status:** Design for approval — NOT yet implemented

## Goal

Strip Memory Lane down from a full payment/subscription SaaS to a **booking
lead-capture site**. A visitor books a recording (any day, working hours), the
booking is saved in WordPress **and** pushed to **Teamleader Focus** (Belgian
CRM) as a **Contact + Deal** for the team to follow up. Payment, subscriptions,
and the large admin panel are removed. Customer **login + dashboard stay** (so
existing customers can view their Matterport tours).

## Confirmed decisions

| Topic | Decision |
| --- | --- |
| Teamleader object | Create/update a **Contact**, then create a **Deal** linked to it. |
| Removal scope | Remove **payment (Stripe)**, **subscriptions**, **big admin panel**. Keep **customer login + dashboard**. |
| Availability | **No slots / no capacity.** Every day is bookable (full rolling calendar); times = working hours only. |
| Code | **Fully delete** Stripe / subscription / legacy-admin files (recoverable via git history). |
| Admin | Keep a **slim admin**: Matterport tours + working hours + Teamleader settings. Delete payment/subscription/billing/customers/slots/invoices/logs admin. |
| Teamleader creds | Client does **not** have them yet → ship a setup guide; integration is built but inert until creds are entered. |
| Booking storage | Save in **WP DB and** send to Teamleader (WP is the safety net if the API call fails). |

## What gets DELETED (files removed + unloaded from bootstrap)

- `inc/stripe-php-master/` — entire Stripe PHP SDK.
- `inc/stripe/` — client, checkout, webhooks, schedule, customer-portal, plans, `events/`.
- `inc/subscriptions/` — `status.php`, `sync.php`, `reactivation.php`,
  `reactivation-routes.php`, `billing.php`. **(access-gate.php is kept — see below.)**
- `inc/admin/` — all legacy wp-admin pages (`menu`, `settings`, `customers`,
  `subscriptions-page`, `notifications-log`, `webhooks-log`, `manual-actions`,
  `approve-access`, `reactivations-page`).
- Payment/subscription crons: `check-expirations`, `send-renewal-warnings`,
  `retry-failed-webhooks`, `orphan-payment-check`, `finalize-schedules`,
  `pending-approval-reminder`, `reactivation-overdue`, `revoke-overdue`.
- Admin-panel sections: `invoices`, `subscriptions`, `customers`,
  `customers-detail`, `slots`, `logs`.
- Dashboard sections: `subscription.php`, `reactivate.php`.
- `inc/booking/slots.php` + `availability.php` capacity logic, and the
  Stripe-dependent path in `boek-checkout.php`.
- Payment/subscription email templates (left on disk is harmless, but their
  senders are removed). Booking emails are kept.

## What is KEPT

- **Auth**: login, logout, password reset, welcome — unchanged.
- **Customer dashboard**: `overview`, `tours`, `tour-viewer`, `booking`,
  `settings`. Nav loses Subscription + Reactivate.
- **Tours subsystem** (`inc/tours/*`): CPT, admin meta, viewer — unchanged.
- **Booking** (`inc/booking/*`): kept but simplified (below).
- **Emails mailer** + booking templates.
- `inc/booking/countries.php`, `boek-checkout.php` (rewritten), `booking-rest.php`
  (simplified), `working-hours.php`, `bookings.php`.

## What is SIMPLIFIED

### Access gate (critical)
`inc/subscriptions/access-gate.php` is **kept but gutted** to remove all
subscription/Stripe lookups. New behaviour:
- `ml_user_has_access($id)` → true for admins and any logged-in customer.
- `ml_user_can_book($id)` → true for any logged-in customer.
- `ml_user_access_state($id)` → `'active'` for customers (kept so callers in
  the dashboard keep working without edits).
- `ml_user_is_pending_approval()` → false.

This keeps `dashboard/overview|tours|tour-viewer|booking` and
`inc/booking/bookings.php` working with no changes to those files.

### Booking availability (no slots)
- The calendar shows **every day** in a rolling window (default 60 days /
  "elke maand"), minus admin-blocked dates and non-working weekdays.
- Times come from the **working-hours** config only (e.g. 09:00–17:00 at a fixed
  interval), all selectable — **no capacity, no "taken" state**.
- `booking-rest.php` `…/booking/slots` returns working-hour times for a date
  (no DB capacity check).
- A booking row is still inserted (`bookings` table). `slot_id` becomes
  optional/`NULL`; `scheduled_for` is stored directly from date + time. The
  soft-hold / `booked_count` / stale-hold cron are removed.

### Public `/boek`
- Keep the current marketing-wrapped form with structured address +
  intl-tel-input + Nominatim autocomplete (already built).
- On submit (no payment): insert booking row → **push to Teamleader** → land on
  the booking thank-you page. Payment branch + price box are gone entirely.

## What is ADDED — Teamleader integration

New module `inc/teamleader/`:

- `oauth.php` — OAuth2 Authorization-Code flow against
  `https://focus.teamleader.eu/oauth2/authorize|access_token`. Stores
  access+refresh tokens (encrypted WP options, autoload off) and auto-refreshes
  on 401/expiry. A `/admin` "Connect Teamleader" button starts the flow; a REST
  callback `memorylane/v1/teamleader/callback` completes it.
- `client.php` — thin API wrapper (`ml_tl_request($method, $endpoint, $body)`)
  hitting `https://api.focus.teamleader.eu`, attaching the bearer token.
- `booking-sync.php` — `ml_tl_push_booking($booking, $contact)`:
  1. `contacts.add` (or find existing by email via `contacts.list`) → contact id.
  2. `deals.create` with the contact as customer, title e.g.
     "Opname – {name} – {date}", and the booking details (address, phone,
     notes, chosen date/time) in the deal summary / a note.
  Failures are caught + logged; the WP booking row is the fallback so nothing is
  lost. A small retry (cron or manual "resend") may be added.
- `settings.php` (slim-admin tab) — connection status, Connect/Disconnect,
  client_id + client_secret fields, and the default Deal phase/pipeline pickers.

Credentials are entered in the slim admin; until connected, `ml_tl_push_booking`
is a logged no-op so the site still works.

## Slim admin (`/admin`)

Reduce the custom admin panel to three sections:
- **Bookings** — list of received bookings (kept, read-only-ish).
- **Tours** — add/manage Matterport tours per customer (kept).
- **Settings** — working hours + blocked dates + booking window, and the
  **Teamleader** connection.

`template-parts/admin-panel/shell.php` nav + `inc/admin-panel/handlers.php` are
trimmed to these. Overview is simplified or dropped.

## Data flow (new)

```
Visitor → /boek (pick day + working-hour time, fill details)
   → POST memorylane/v1/boek
       → insert bookings row (scheduled_for, contact details)
       → ml_tl_push_booking(): contacts.add/find → deals.create   [if connected]
       → redirect /checkout/success?booking=1 (thank-you)
Team works the Deal inside Teamleader.
Existing customers → /login → /dashboard → view their Matterport tours.
```

## Phasing

**Phase 1 — no credentials needed (build now):**
delete payment/subscription/legacy-admin; simplify access gate + booking
availability; slim the admin; build the Teamleader module + settings UI + the
booking hook (inert no-op until connected). Site fully functional as a booking
form + customer dashboard, storing bookings in WP.

**Phase 2 — after the client supplies Teamleader credentials:**
finish + live-test the OAuth connect flow and Contact+Deal creation; map the
correct pipeline/phase.

## Risks / notes

- **Destructive:** done on a branch (`feature/booking-only-teamleader`), staged
  commits, so it's reviewable and revertible.
- **DB:** `ml_subscriptions` and slot tables can stay in place (unused) to avoid
  data loss; only code is removed. Optional cleanup later.
- **Existing customers/tours** are untouched — only the access rule changes
  (now: logged-in customer = access).
- **Teamleader OAuth tokens** stored encrypted, never logged.
- Nominatim + intl-tel-input still load from CDN (need internet).

## Appendix — getting Teamleader API credentials (for the client)

1. Sign in to Teamleader Focus → **Marketplace → Build → "Build your own
   integration"** (Developer/API section), or go to
   `https://marketplace.teamleader.eu/`.
2. Create a **private integration**. Note the **Client ID** and **Client
   secret**.
3. Set the **Redirect URI** to:
   `https://<your-domain>/wp-json/memorylane/v1/teamleader/callback`
   (exact value shown on our /admin → Settings → Teamleader page).
4. Request the scopes we need: **contacts**, **deals** (and **companies** if
   business contacts are expected).
5. Paste Client ID + Client secret into /admin → Settings → Teamleader, then
   click **Connect Teamleader** and approve.
