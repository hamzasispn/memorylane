# Memory Lane Backend — Design Spec

**Date:** 2026-05-13
**Author:** brainstorming session, hafeeznafeez070@gmail.com
**Status:** Approved by chief — ready for implementation plan
**Scope of this spec:** Overall architecture + **Phase 1 (Auth + Dashboard shell)** + **Phase 2 (Stripe Checkout + Webhooks + Access gate)** in detail. **Phases 3–6** outlined; each will get its own spec + implementation plan before build.

---

## 1. Business context

Memory Lane is a Dutch / Belgian service: a camera team scans a customer's home with Matterport, produces a virtual tour, hosts it in a private customer portal. Customers pay an initial bundle (setup + 12 months of access) and then a monthly subscription to keep the tour live. Tours can be archived (paused) and reactivated for a one-time fee.

**Pricing (placeholders):**

| SKU | Price | Type |
|---|---|---|
| ML Setup + Year 1 | €XX | one-time (yearly interval, 1 iteration) |
| ML Monthly Hosting | €XX | recurring monthly (starts after year 1) |
| ML Reactivation Fee | €XX | one-time |

Public marketing pages already exist in the theme (`/`, `/waarom`, `/hoe-werkt-het`, `/tarieven`). This spec covers everything behind those pages: registration, payment, dashboard, tour assignment, booking, admin, automation.

---

## 2. Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Build order | Slice by user journey (Phase 1 → 6) | Each phase delivers testable value; reduces blast radius |
| Code home | All code inside the `memorylane` theme `inc/` folder | Chief directive |
| Booking purpose | Matterport scan appointment | Matches Memory Lane business model |
| Checkout flow | Pay first → Stripe webhook auto-creates WP user | Fewer pre-purchase steps for customer |
| Language | NL + EN with cookie switcher | NL primary; EN for broader reach |
| Dashboard look | Clean SaaS (Linear / Stripe vibe) | Intentionally distinct from emotional public site |
| Stripe model | **Subscription Schedule** with two phases (yearly → monthly) | Stripe-native; no cron risk for billing transition |

---

## 3. Overall architecture

### 3.1 File layout

```
wp-content/themes/memorylane/
├── inc/
│   ├── bootstrap.php                  # loads all subsystems below
│   ├── config.php                     # constants, settings reads
│   ├── helpers.php                    # (exists)
│   ├── stripe-php-master/             # (exists — Stripe SDK)
│   │
│   ├── auth/
│   │   ├── routes.php                 # custom rewrite rules
│   │   ├── handlers.php               # form POSTs, rate limiting, nonces
│   │   └── password-reset.php
│   │
│   ├── i18n/
│   │   ├── translator.php             # ml_t() helper, cookie switching
│   │   └── strings/{nl,en}.php
│   │
│   ├── stripe/
│   │   ├── client.php
│   │   ├── checkout.php               # create Checkout Session
│   │   ├── webhooks.php               # REST endpoint + dispatcher
│   │   ├── schedule.php               # convert subscription → schedule (Phase A→B)
│   │   ├── customer-portal.php
│   │   └── events/                    # one handler file per event type
│   │
│   ├── subscriptions/
│   │   ├── access-gate.php            # ml_user_has_access()
│   │   ├── status.php
│   │   └── sync.php                   # Stripe state → local mirror
│   │
│   ├── tours/                         # Phase 3
│   ├── booking/                       # Phase 4
│   ├── admin/                         # Phase 5
│   ├── emails/                        # Phase 6
│   ├── cron/                          # Phase 6
│   └── db/
│       └── install.php                # dbDelta on activation + version bump
│
├── template-parts/
│   ├── auth/                          # login, forgot-password, reset-password
│   └── dashboard/                     # shell, overview, tours, booking, subscription, settings
│
├── assets/src/
│   ├── css/dashboard.css              # NEW — SaaS design tokens
│   └── js/dashboard.js                # NEW — Alpine components
│
└── docs/superpowers/specs/            # this file lives here
```

### 3.2 Custom DB tables

All created via `dbDelta` on theme activation; version stored in `ml_db_version` option and checked at `admin_init`.

| Table | Purpose |
|---|---|
| `wp_ml_webhook_events` | Idempotency log: `event_id`, `type`, `status`, `payload`, `retry_count`, `processed_at` |
| `wp_ml_subscriptions` | Local mirror of Stripe state: `user_id`, `stripe_sub_id`, `stripe_schedule_id`, `status`, `current_period_end`, `year_one_end_date`, `cancel_at_period_end`, `raw_json`, timestamps |
| `wp_ml_bookings` | Phase 4: `user_id`, `slot_id`, `service_type`, `status`, `customer_notes`, `admin_notes`, timestamps |
| `wp_ml_availability_slots` | Phase 4: `slot_start_datetime`, `slot_end_datetime`, `capacity`, `booked_count`, `status` |
| `wp_ml_email_log` | Phase 6: `user_id`, `template`, `to_email`, `status`, `retry_count`, `sent_at` |
| `wp_ml_admin_actions_log` | Phase 5: manual override audit (`admin_id`, `target_user_id`, `action`, `before`, `after`, `reason`, timestamp) |

**Indexes:** `user_id`, `stripe_sub_id`, `event_id`, `slot_start_datetime`, `status`.

### 3.3 User model

- **Use WordPress `wp_users` + `wp_usermeta`** (built-in, secure, well-tested)
- **Custom role:** `memorylane_customer` — capabilities: `read` only. No backend access.
- **Admins:** standard `administrator` role; custom capability `manage_memorylane` granted via the role, required for all admin pages
- **User meta keys (prefix `_ml_`):** `stripe_customer_id`, `phone`, `address_line1`, `address_line2`, `address_city`, `address_postal`, `address_country`, `language`, `last_login_at`

### 3.4 Routing

Custom rewrite rules added in `inc/auth/routes.php`. Page templates render from `template-parts/`.

**Frontend URLs:**

| URL | Auth required | Template |
|---|---|---|
| `/login` | no | `template-parts/auth/login.php` |
| `/forgot-password` | no | `template-parts/auth/forgot-password.php` |
| `/reset-password/{token}` | no | `template-parts/auth/reset-password.php` |
| `/welcome/{token}` | no | first-time password set after purchase |
| `/dashboard` | yes | `template-parts/dashboard/overview.php` |
| `/dashboard/tours` | yes | `template-parts/dashboard/tours.php` |
| `/dashboard/tour/{slug}` | yes + access | `template-parts/dashboard/tour-viewer.php` |
| `/dashboard/booking` | yes + access | `template-parts/dashboard/booking.php` |
| `/dashboard/subscription` | yes | `template-parts/dashboard/subscription.php` |
| `/dashboard/settings` | yes | `template-parts/dashboard/settings.php` |
| `/checkout/start` | no | redirects to Stripe Checkout |
| `/checkout/success` | no | post-payment landing |
| `/checkout/cancel` | no | returns to /tarieven |
| `/logout` | yes | logout action |

**REST API:**

| Endpoint | Method | Auth |
|---|---|---|
| `/wp-json/memorylane/v1/checkout` | POST | public (rate-limited) |
| `/wp-json/memorylane/v1/stripe-webhook` | POST | Stripe signature |
| `/wp-json/memorylane/v1/subscription/cancel` | POST | logged in, nonce |
| `/wp-json/memorylane/v1/booking` | POST/DELETE | logged in + access, nonce |
| `/wp-json/memorylane/v1/lang` | POST | public (sets cookie) |

---

## 4. Phase 1 — Auth + Dashboard shell (detailed)

### 4.1 Goal
Customer can register (later auto-created by webhook), log in, reset password, see empty dashboard with side nav and language switcher. No Stripe / tours / booking integration yet — shell only.

### 4.2 Pages

**`/login`** — email + password fields, "remember me", forgot-password link, language switcher. Posts to handler that uses `wp_authenticate` + `wp_set_auth_cookie`. On success redirect `/dashboard` or `redirect_to`. On fail, generic error ("e-mail of wachtwoord onjuist") to avoid user enumeration.

**`/forgot-password`** — email field. Always shows generic success ("if address exists, check inbox"). Generates WP reset key via `get_password_reset_key()`, emails NL / EN template.

**`/reset-password/{token}`** — validates with `check_password_reset_key()`. New-password form with strength meter (client-side). On success `reset_password()`, invalidate all user sessions (`WP_Session_Tokens::destroy_all_for_user`), redirect `/login` with flash.

**`/welcome/{token}`** — same as reset-password but messaging is "set your password for your new account". Token is a one-time WP reset key generated when the webhook created the user.

**Dashboard shell:**
- Fixed left side nav (240px) on desktop; hamburger drawer on mobile
- Topbar: user avatar/menu (initials placeholder), language switcher
- Nav items: Overzicht / Tours / Boeking / Abonnement / Instellingen
- Empty states on every sub-page (will be filled by phases 2–4)

### 4.3 Security

| Concern | Defense |
|---|---|
| Brute-force login | Transient counter per IP+username, 5 attempts / 15 min → 1h lockout |
| Form CSRF | `wp_nonce_field()` + `check_admin_referer()` on every form |
| Bot signup | Honeypot field (`name="ml_hp"`); reject if filled |
| Session theft | `auth_cookie` HTTPS-only, HttpOnly, SameSite=Lax |
| User enumeration | Generic errors on login + forgot password |
| Reset token reuse | WP's built-in token is single-use |
| Password rules | Min 10 chars (no forced complexity theater) |

### 4.4 i18n (Phase 1)

- Cookie `ml_lang` ∈ `{nl, en}`; default `nl`
- Language switcher: small button in header, POST to `/wp-json/memorylane/v1/lang`, reload
- Helper `ml_t($key, $default_nl)` — never hardcode visible strings
- Strings in `inc/i18n/strings/nl.php` and `en.php` as `return [...]` arrays
- Stripe Checkout `locale` matches user language
- `wp_date()` for dates with locale

### 4.5 Dashboard design tokens (clean SaaS)

```css
:root {
  --ml-bg:        #FAFAFA;
  --ml-surface:   #FFFFFF;
  --ml-text:      #18181B;
  --ml-muted:     #71717A;
  --ml-border:    #E4E4E7;
  --ml-accent:    #2563EB;    /* placeholder — pick brand-aligned accent in impl review */
  --ml-success:   #10B981;
  --ml-warning:   #F59E0B;
  --ml-danger:    #EF4444;
  --ml-radius-sm: 6px;
  --ml-radius:    8px;
  --ml-radius-lg: 12px;
  /* spacing scale: 4 / 8 / 12 / 16 / 24 / 32 / 48 / 64 */
}
```
- Font: Inter (system fallback) for dashboard; **not** the public-site serif
- Components: Button, Input, Card, Pill (status), Table, SideNav, Modal, Toast — built once in `assets/src/css/dashboard.css` + Alpine micro-components in `dashboard.js`
- Status pills: active = emerald, warning = amber, danger = rose, neutral = zinc

### 4.6 Acceptance criteria (Phase 1)
1. `/login` renders, NL default, switcher toggles to EN, cookie persists
2. Wrong password → generic error, attempt counter increments (verifiable via transient)
3. 5 wrong attempts → locked out 1h (verifiable)
4. Forgot password → email sent (row in `wp_ml_email_log` once Phase 6 logging exists; for Phase 1 verify via mail log), reset link works, password updated, sessions invalidated
5. Logged-in customer at `/dashboard` → shell renders with side nav
6. Logged-out user at `/dashboard` → redirect `/login?redirect_to=/dashboard`
7. Mobile viewport: hamburger drawer works
8. `wp-admin` still works for administrators

---

## 5. Phase 2 — Stripe Checkout + Webhooks + Access gate (detailed)

### 5.1 Goal
"Start" button on `/tarieven` → Stripe-hosted Checkout → on success, webhook creates WP user with `memorylane_customer` role, creates Subscription Schedule (yearly phase A → monthly phase B), sends welcome email with password link, populates `wp_ml_subscriptions`. Customer logs in and sees subscription status active.

### 5.2 Stripe products (set up once in Stripe Dashboard)

| Product | Price ID stored as | Type | Interval |
|---|---|---|---|
| ML Setup + Year 1 | `ml_stripe_setup_price_id` | recurring | yearly, 1 iteration in schedule |
| ML Monthly Hosting | `ml_stripe_monthly_price_id` | recurring | monthly, indefinite |
| ML Reactivation Fee | `ml_stripe_reactivation_price_id` | one-time | n/a |

All four pieces of Stripe config (keys + price IDs) live in WP options table, written via Settings page (Phase 5; for Phase 2 a temporary `wp-cli option set` or admin filter is acceptable). `option_autoload = no`. Never logged.

### 5.3 Checkout flow

```
1. Visitor clicks "Start" on /tarieven
2. JS calls POST /wp-json/memorylane/v1/checkout
3. Server:
   - Generate idempotency key (uuid v4)
   - Create Stripe Checkout Session:
       mode: 'subscription'
       line_items: [{ price: setup_price_id, quantity: 1 }]
       customer_creation: 'always'
       billing_address_collection: 'required'
       phone_number_collection: { enabled: true }
       locale: ml_current_lang() === 'en' ? 'en' : 'nl'
       success_url: SITE/checkout/success?session_id={CHECKOUT_SESSION_ID}
       cancel_url:  SITE/tarieven
       metadata: { ml_intent: 'initial_purchase', idempotency_key: ... }
   - Return session.url
4. Browser redirects to Stripe-hosted Checkout
5. Customer pays
6. Stripe redirects to /checkout/success (shows "Check your email for login link")
7. Stripe sends webhook checkout.session.completed (server-side, authoritative)
```

`/checkout/success` is *only* a UI page; it does not grant access. Webhook is the source of truth.

### 5.4 Webhook handler

**Endpoint:** `POST /wp-json/memorylane/v1/stripe-webhook` (no auth header; verified by Stripe signature)

**Pipeline:**
1. Read raw body via `file_get_contents('php://input')` **before** any WP-applied filters
2. Read `Stripe-Signature` header
3. Verify with `\Stripe\Webhook::constructEvent($payload, $sig, $secret)`
4. Idempotency: `INSERT IGNORE INTO wp_ml_webhook_events (event_id, type, status='pending', payload, received_at)` — if `affected_rows === 0`, return 200 immediately (already processed)
5. Dispatch by `$event->type` to handler in `inc/stripe/events/`
6. On success → update row `status='processed'`, return 200
7. On exception → catch, log, set `status='failed'`, increment `retry_count`, return 500 (Stripe will retry)

**Events handled in Phase 2:**

| Event | Handler does |
|---|---|
| `checkout.session.completed` | Retrieve session expanded with subscription + customer. Find or create WP user by email. Assign `memorylane_customer` role. Save `_ml_stripe_customer_id`, address, phone. **Convert subscription to Subscription Schedule** (see 5.5). Insert into `wp_ml_subscriptions`. Generate password reset key, email welcome + welcome URL. Email admin "new purchase". |
| `customer.subscription.updated` | Sync state → `wp_ml_subscriptions` + user meta. Recompute access. |
| `customer.subscription.deleted` | Mark cancelled. If `cancel_at_period_end=true`, retain access until period end; else revoke immediately. Email customer + admin. |
| `invoice.payment_succeeded` | Refresh subscription state. After year 1, email monthly receipt. |
| `invoice.payment_failed` | Mark `past_due`. Email customer ("payment failed — retrying"). If `attempt_count >= final_attempt`, email admin. |
| `invoice.upcoming` | Email customer 7-day reminder (Stripe sends this 7 days before invoice). |

### 5.5 Phase A → Phase B schedule conversion

```php
$session      = \Stripe\Checkout\Session::retrieve($id, ['expand' => ['subscription']]);
$subscription = $session->subscription;
$year_one_end = $subscription->current_period_end; // unix ts

// Convert active subscription into a schedule
$schedule = \Stripe\SubscriptionSchedule::create([
    'from_subscription' => $subscription->id,
]);

// Append phase B (monthly forever)
\Stripe\SubscriptionSchedule::update($schedule->id, [
    'end_behavior' => 'release',
    'phases' => [
        [
            'items'      => [['price' => SETUP_PRICE_ID, 'quantity' => 1]],
            'start_date' => $schedule->phases[0]->start_date,
            'end_date'   => $year_one_end,
        ],
        [
            'items'      => [['price' => MONTHLY_PRICE_ID, 'quantity' => 1]],
            // no iterations = until cancelled
        ],
    ],
]);
```

Store `$schedule->id` in `wp_ml_subscriptions.stripe_schedule_id`. Stripe handles the monthly transition automatically — no cron required for billing lifecycle.

### 5.6 Access gate

```php
function ml_user_has_access(int $user_id): bool {
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT status, current_period_end FROM {$wpdb->prefix}ml_subscriptions
         WHERE user_id = %d ORDER BY id DESC LIMIT 1",
        $user_id
    ));
    if (!$row) return false;
    $allowed = ['active', 'trialing', 'past_due']; // past_due during grace
    if (!in_array($row->status, $allowed, true)) return false;
    return strtotime($row->current_period_end) > time();
}
```

- Past-due grace period configurable (default 7 days) — when `status = past_due` AND `current_period_end + grace_seconds > now`, return `true`
- Result cacheable per-request via `wp_cache_set('ml_access_' . $user_id, $result, '', 60)`
- Invalidated on every webhook write

### 5.7 Customer subscription management

`/dashboard/subscription` shows:
- Current status pill (Actief / Loopt af op X / Geannuleerd / Betaling mislukt)
- Phase (Jaar 1 inbegrepen / Maandelijks abonnement)
- Next billing date
- "Beheer in Stripe" → Stripe Customer Portal (invoices, payment method) — short-lived session via `\Stripe\BillingPortal\Session::create`
- "Abonnement opzeggen" → `/wp-json/memorylane/v1/subscription/cancel` → `\Stripe\Subscription::update($id, ['cancel_at_period_end' => true])`. Webhook syncs state.

### 5.8 Failure modes

| Failure | Behaviour |
|---|---|
| Payment succeeds, webhook fails to create user | Daily cron `ml_cron_orphan_payment_check` (Phase 6) queries Stripe checkout sessions last 24h, cross-refs `_ml_stripe_customer_id`, emails admin list of orphans |
| Webhook signature invalid | Return 400, log `status='invalid_signature'` |
| Email send fails | Log to `wp_ml_email_log` `status='failed'`; cron `ml_cron_retry_emails` retries |
| Customer email already exists as WP user | If existing user has matching `_ml_stripe_customer_id` → treat as repeat purchase. Else → log conflict, email admin |
| Stripe API outage during schedule conversion | Catch exception → record `wp_ml_subscriptions.status='schedule_pending'`; cron `ml_cron_finalize_schedules` retries (Phase 6) |

### 5.9 Acceptance criteria (Phase 2)
1. Stripe Settings page saves keys + price IDs
2. "Start" → redirects to Stripe Checkout (test mode)
3. Pay with `4242 4242 4242 4242` → return to `/checkout/success`
4. Webhook received → WP user created with `memorylane_customer` role
5. Welcome email sent with password setup link
6. `wp_ml_subscriptions` row inserted; Subscription Schedule visible in Stripe Dashboard with two phases
7. Customer sets password, logs in, `/dashboard/subscription` shows Active + year-one end date + next billing
8. Cancel button → Stripe shows `cancel_at_period_end=true`, customer email sent, dashboard pill changes to "Loopt af op …"
9. Replay same webhook event → no duplicate user, no duplicate email (idempotency proven)
10. Force `invoice.payment_failed` via Stripe CLI → customer + admin emails sent, status `past_due`, access still granted during grace

---

## 6. Phase 3 — Tour assignment + viewing (outline)

**Data model:** Custom post type `ml_tour`. Meta: `_ml_tour_user_id`, `_ml_tour_provider`, `_ml_tour_url`, `_ml_tour_embed_code`, `_ml_tour_status` ∈ {active, archived, pending_archive}, `_ml_tour_address`, `_ml_tour_assigned_at`.

**Admin:** meta box (assign user, paste embed code, status toggle). List table shows status + assignee.

**Customer:** `/dashboard/tours` lists their tours. `/dashboard/tour/{slug}` renders iframe — only if access gate passes AND tour assigned to this user AND status=`active`.

**Embed sanitization:** allowlist for iframe `src` (`my.matterport.com` by default; admin can extend in Settings).

**Subscription-inactive behavior:** tour viewer page shows "Toegang verlopen — verleng abonnement" with renew CTA instead of iframe; tours flagged `pending_archive`; admin notified.

**Reactivation:** Phase 3 admin action triggers one-time Stripe Invoice using `ml_stripe_reactivation_price_id`; on payment success → flip tour status back to `active`.

Full spec for Phase 3 to be written before implementation starts.

---

## 7. Phase 4 — Booking system (outline)

**Tables:** `wp_ml_availability_slots`, `wp_ml_bookings`.

**Admin UI:** calendar grid (week/month) of slots, bulk slot generator (weekday + time range + duration + N weeks), bookings list with filter + row actions (confirm / reschedule / cancel / mark complete).

**Customer UI (`/dashboard/booking`):** open slots next 60 days; pick → confirm modal → row inserted as `requested`. Admin confirms (or auto-confirm via setting). Reschedule ≥48h before slot, cancel ≥24h before (configurable).

**Service types:** `initial_scan` (gated to one per customer); `reactivation` (only if customer has archived tour).

**Access gate:** all booking endpoints require `ml_user_has_access()`.

Full spec for Phase 4 to be written before implementation starts.

---

## 8. Phase 5 — Admin tools (outline)

Top-level WP admin menu **Memory Lane**:

| Sub-page | Functions |
|---|---|
| Overview | counters: active subs, past_due, cancelled this month, upcoming bookings, pending tour archives |
| Customers | list + filter; row actions: view profile, assign tour, activate/deactivate access manually, resend welcome email, open Stripe customer |
| Tours | (uses CPT admin) + bulk archive |
| Bookings | list table with actions |
| Subscriptions | mirror of `wp_ml_subscriptions`, force-sync from Stripe, manual override |
| Notifications log | last 200 emails + delivery status, retry button |
| Webhooks log | event_id, type, status, retry failed |
| Settings | Stripe keys, price IDs, grace days, booking rules, email sender, admin notification recipients |

Manual access toggles write to `wp_ml_admin_actions_log` (who/when/why/before/after).

Full spec for Phase 5 to be written before implementation starts.

---

## 9. Phase 6 — Cron + emails (outline)

### 9.1 Cron jobs

| Job | Frequency | Purpose |
|---|---|---|
| `ml_cron_check_expirations` | hourly | mark expired subscriptions, revoke access, flag tours, email customer + admin |
| `ml_cron_renewal_warnings` | daily | email customers 7 days before year-1 → monthly transition |
| `ml_cron_retry_webhooks` | every 15 min | replay failed webhook events (retry_count < 5) |
| `ml_cron_retry_emails` | every 15 min | replay failed emails (retry_count < 3) |
| `ml_cron_orphan_payment_check` | daily | Stripe checkout sessions last 24h with no matching WP user |
| `ml_cron_booking_reminders` | hourly | 24h-before booking reminder (one-shot per booking) |
| `ml_cron_overdue_tour_archive` | daily | tours `pending_archive > 7 days` → email admin |
| `ml_cron_finalize_schedules` | hourly | retry Subscription Schedule conversion for `schedule_pending` rows |

**Reliability:** spec requires `define('DISABLE_WP_CRON', true)` in `wp-config.php` + an OS-level cron every 5 minutes hitting `https://memorylane.example/wp-cron.php?doing_wp_cron`. Installation instructions included in the implementation plan deliverables.

### 9.2 Email templates

**Customer (NL + EN each):** welcome_set_password, purchase_confirmation, password_reset_request, password_changed, subscription_renewal_warning, monthly_payment_receipt, payment_failed, subscription_cancelled, access_expired, booking_confirmed, booking_reminder, booking_rescheduled, tour_assigned, reactivation_completed.

**Admin:** new_purchase, payment_failed_final, subscription_cancelled, tour_pending_archive, orphan_payments, booking_request.

All templates use shared header/footer partials, accent button, dual language via `ml_t()`. Admin recipients configurable list in Settings.

Full spec for Phase 6 to be written before implementation starts.

---

## 10. Cross-cutting

### 10.1 Security checklist

- WP nonces on every form
- Rate-limit (transient) on login + forgot-password + checkout init
- Generic errors (no user enumeration)
- Honeypot on register / welcome / forgot-password
- `auth_cookie` HTTPS-only, HttpOnly, SameSite=Lax
- All custom SQL via `$wpdb->prepare()`
- Output escaped (`esc_html` / `esc_attr` / `esc_url` / `wp_kses_post`)
- Tour embed sanitized via iframe allowlist
- REST endpoints: capability + nonce checks; public ones explicitly documented
- Stripe webhook signature verified; raw body captured before any middleware
- Stripe keys: `option_autoload = no`; never echoed; never logged
- PII: webhook payloads contain emails — restricted to admin access on Webhooks Log page
- `force_ssl_admin(true)` in production wp-config
- CSP header recommended in ops docs (self + matterport.com + js.stripe.com)

### 10.2 Observability

- `wp_ml_webhook_events` — Stripe state audit
- `wp_ml_email_log` — email audit
- `wp_ml_admin_actions_log` — manual override audit
- Unhandled exceptions → `error_log` + Webhooks Log entry
- Admin Overview shows 24h webhook + email failures, 7d orphan payments

### 10.3 Testing

- Manual test playbooks per phase (acceptance criteria above)
- Stripe CLI: `stripe listen --forward-to localhost/wp-json/memorylane/v1/stripe-webhook` + `stripe trigger checkout.session.completed`
- Optional PHPUnit smoke tests (stubbed in `tests/`, runnable when Composer added) for pure functions: `ml_user_has_access`, slot capacity logic, schedule date math
- Browser smoke checklist run before each phase ships

### 10.4 Performance

- Index custom tables on lookup keys
- Access gate cached per-request (60s TTL), invalidated on webhook write
- Dashboard pages load only `dashboard.css` bundle, not public-site CSS
- Vite manifest already handles cache-busting

### 10.5 i18n

- `ml_t($key, $default_nl)` everywhere
- Strings in `inc/i18n/strings/{nl,en}.php`
- Stripe Checkout `locale` matches
- `wp_date()` with locale

### 10.6 Reliability

- OS-level cron required (WP-cron unreliable)
- Stripe webhook retries up to 3 days; we additionally retry via own cron
- Idempotency keys on all outbound Stripe writes (prevent dupe on double-click)
- DB migrations versioned in `ml_db_version` option, `dbDelta` runs on activation + admin_init version check

---

## 11. Implementation order (one plan per phase)

This spec covers everything **architecturally**. Each phase below will get its **own implementation plan** (`docs/superpowers/plans/`) before any code is written for that phase. Phase 1+2 first plan is the natural starting point.

| # | Phase | Ships value when complete |
|---|---|---|
| 1 | Auth + Dashboard shell | Customer can log in, reset password, see empty dashboard |
| 2 | Stripe Checkout + Webhooks + Access gate | Customer can pay, get account, see subscription status |
| 3 | Tour assignment + viewing | Customer can view their Matterport tour, access gated |
| 4 | Booking system | Customer can schedule scan appointment |
| 5 | Admin tools | Operations team can manage everything via WP admin |
| 6 | Cron + emails (full set) | All automated lifecycle communications working |

**Note:** Some Phase 6 plumbing (`wp_ml_email_log` + minimal `mailer.php`) is built early in Phase 1 because emails are needed for password reset. Likewise `wp_ml_webhook_events` is built in Phase 2. Phase 6's job is to bring all the *additional* automation (cron jobs, full template set, retry plumbing) under one consistent system.

---

## 12. Open items to confirm before Phase 1 plan

These do not block the spec, but should be settled before the **implementation plan** for Phase 1+2 is written:

1. Final accent color for dashboard (currently `#2563EB` placeholder) — does the brand have a preferred SaaS-tone accent?
2. Admin notification recipients (single email or list) for Phase 5 settings
3. Past-due grace period default (proposed 7 days)
4. Reschedule / cancel lead-time defaults for bookings (proposed 48h / 24h)
5. Brand voice for NL emails (formal "u" vs. informal "je" — public site uses "je", we'll match unless told otherwise)
6. Stripe account access (test mode keys) — needed to actually wire up Phase 2
7. Memberport / Matterport admin access for the team (operational, not code)

---

**End of spec.**
