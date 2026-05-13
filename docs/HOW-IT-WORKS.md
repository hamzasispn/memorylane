# How Memory Lane Works

A plain-language walkthrough of the system. Written for the business owner — no code, just the flow.

---

## 1. The big picture

Memory Lane has three sides:

| Side | Who | What they do |
|---|---|---|
| **Public website** | Anyone | Browse `/`, `/waarom`, `/hoe-werkt-het`, `/tarieven`. Click "Start je tour" to buy. |
| **Customer portal** | People who paid | Log in at `/login`. See `/dashboard` with their tour, booking, subscription, settings. |
| **Admin** | You (the business) | Log into WordPress admin → "Memory Lane" menu. Manage everything from there. |

Payments live in **Stripe**. WordPress and Stripe stay in sync automatically (no double-entry).

---

## 2. The customer journey — step by step

### Step 1 — Visitor lands on `/tarieven`
They see your three pricing cards. The first card ("Jouw woning op Memory Lane") has a **"Start je tour"** button. The other two cards explain what comes after.

### Step 2 — They click "Start je tour"
WordPress redirects them straight to **Stripe Checkout** (Stripe-hosted page). They never enter card details on your site — Stripe handles that, fully PCI-compliant.

On the Stripe page they:
- Enter email + name
- Enter billing address + phone (required)
- Enter card details
- Pay the **Setup + Year 1** amount

### Step 3 — Payment succeeds
Stripe redirects them to `/checkout/success` (a "thanks, check your email" page).

In the background, Stripe sends a **webhook** to your site. The webhook does:
1. **Creates the customer's WordPress account** automatically (with their email).
2. **Sends a welcome email** with a link to set their password.
3. **Records the subscription** in your database.
4. **Converts the subscription into a 2-phase schedule** in Stripe (see Section 4 below).
5. **Sends you an admin email** ("New purchase: …").

### Step 4 — Customer clicks the email link
The link goes to `/welcome/<token>`. They pick their password. Now they can log in.

### Step 5 — They land in the dashboard `/dashboard`
They see an overview card with:
- **Subscription status** (Active / Past due / Cancelled / Ending soon)
- **Tours** count (zero until you assign one)
- **Next appointment** (empty until they book)

### Step 6 — They book the scan appointment
They go to `/dashboard/booking`, see your available time slots for the next 60 days, pick one, optionally write a note, and submit.

A booking is created with status **"requested"**. They get a confirmation-pending email. You get an admin email ("New booking: …").

### Step 7 — You confirm the appointment in WP admin
WP Admin → Memory Lane → Bookings → click **Confirm**. The customer gets a "your appointment is confirmed" email. You get a 24-hour-before reminder email sent automatically to them.

### Step 8 — Your scan team visits and scans the house
After the scan, you upload it to Matterport (your existing workflow — outside this system).

### Step 9 — You assign the tour to the customer in WP
WP Admin → Memory Lane (sidebar) → Tours → **Add new tour**:
- Title (e.g. address)
- Pick the customer from the dropdown
- Paste the **Matterport iframe embed code**
- Set status to **Active**
- Save

The customer instantly sees the tour at `/dashboard/tours` and can view it at `/dashboard/tour/<slug>` (embedded as iframe).

The customer also gets a "Your virtual tour is ready" email.

### Step 10 — 12 months pass
A week before the 12-month mark, the customer automatically gets a "Your first year is ending soon" email explaining that monthly billing is about to start.

### Step 11 — Stripe automatically switches to monthly
At the exact moment Year 1 ends, Stripe automatically charges the customer the **monthly amount** and continues to do so every month. **No cron job, no manual action — Stripe handles it.** This is the "subscription schedule" we set up at purchase.

The customer keeps seeing the tour. They get a "Payment received" email each month.

### Step 12 — Customer wants to cancel
They go to `/dashboard/subscription` → click **"Abonnement opzeggen"**.

WP tells Stripe to cancel at period end. Stripe sends back a webhook to confirm. The customer's dashboard pill changes to **"Loopt af op …"**. They keep access until the end of the current paid period. After that, access is revoked and you get an admin email reminding you to set the tour to private in Matterport.

### Step 13 — Later, customer wants to reactivate
Manual flow (Phase 3 spec future): you trigger a one-time **reactivation invoice** in Stripe → on payment, you flip the tour status back to Active and re-enable it in Matterport.

---

## 3. What you do as the admin

All from **WP Admin → Memory Lane** menu (left sidebar):

| Sub-page | What it's for |
|---|---|
| **Overview** | Quick KPI cards: active subscriptions, pending cancellations, past-due, webhook failures (24h). Plus a yellow banner if Stripe isn't connected yet. |
| **Customers** | List of all paying customers. Search by name/email. Each row shows subscription status + tour count. Actions: edit profile, open the customer in Stripe Dashboard, resend the welcome email. |
| **Subscriptions** | All subscriptions in one place. Filter by status. Direct link into Stripe Dashboard for each one. |
| **Tours** | Add/edit virtual tours (Matterport embed). Assign to a customer. Set Active / Archived / Pending archive. |
| **Bookings** | Create availability slots in bulk (pick weekdays + time + date range). See all booking requests. Confirm / cancel / mark complete with one click. |
| **Webhooks log** | Audit trail of every event Stripe has sent us. Anything failed? One-click retry. |
| **Notifications log** | Audit trail of every email we sent (customer + admin). Failed? One-click retry. |
| **Settings** | Tabbed: Stripe / Matterport / Access / Booking / Emails / General. |

---

## 4. The Stripe relationship — one-time setup

You only do this once. From WP Admin → Memory Lane → Settings → **Stripe** tab.

### Step A — Paste your Stripe API keys
- Get them from your Stripe Dashboard → Developers → API keys
- Paste **Publishable key**, **Secret key**, and **Webhook signing secret** into the WP form
- Click **"Connect with Stripe"**
- The status card flips green if the keys verify

### Step B — Define your subscription plan
Right below the connection section, you fill in:
- Product name (default: "Memory Lane")
- Description (shown on Stripe receipts)
- Currency
- **Setup + Year 1 amount** (e.g. 299.00)
- **Monthly hosting amount** (e.g. 9.00)
- **Reactivation fee** (e.g. 49.00)

Click **"⇅ Sync with Stripe"**. This:
- Creates a Stripe Product (if not already)
- Creates 3 Stripe Prices: yearly setup, monthly recurring, one-time reactivation
- Saves all the Stripe IDs back into WordPress

A "Synced status" panel appears showing exactly which Stripe Product and Price IDs are in use.

**Want to change a price later?** Edit the amount field, click Sync again. WordPress creates a NEW Stripe Price and archives the old one. **Existing customer subscriptions keep paying their original price.** Only NEW checkouts use the new price. This is how Stripe works — prices are immutable so we never accidentally change what existing customers pay.

### Step C — Configure the webhook endpoint in Stripe
In Stripe Dashboard → Developers → Webhooks → Add endpoint:
- URL: shown in your WP Settings page (`…/wp-json/memorylane/v1/stripe-webhook`)
- Listen for: `checkout.session.completed`, `customer.subscription.created/updated/deleted`, `invoice.payment_succeeded/failed/upcoming`
- Copy the "Signing secret" → paste back into WP Settings → save

That's the whole setup. Maybe 10 minutes total.

---

## 5. The Stripe subscription model (the smart part)

When a customer pays Year 1, here's what happens behind the scenes:

```
Day 0          Day 365         Day 395         Day 425         …
│              │               │               │
│  €299        │  €9           €9              €9              €9
│ ─paid───┐    │ ──auto────────────────────────────────────────────►
│         │    │
│ Phase A │    │ Phase B (monthly forever, until customer cancels)
└─Year 1──┘    └─Monthly hosting──────────────────────────────────►
```

In Stripe terms this is a **Subscription Schedule** with two phases:
- **Phase A:** one yearly invoice for the setup amount, runs for 1 cycle (12 months)
- **Phase B:** monthly recurring at the hosting amount, runs forever (until cancelled)

The transition between Phase A and Phase B happens automatically at the Stripe side at the exact 12-month mark. We don't need a cron job watching the clock — Stripe does that.

---

## 6. What runs automatically (cron jobs)

| When | What |
|---|---|
| **Every hour** | Check for subscriptions that should have ended now (cancel-at-period-end past due) → revoke access, flag the tours, email customer + admin |
| **Every hour** | Send 24-hour-before booking reminders |
| **Every hour** | Retry any Stripe schedule conversion that failed during checkout |
| **Every 15 minutes** | Retry failed webhook events (if our handler crashed for any reason) |
| **Every 15 minutes** | Retry failed email sends |
| **Every day** | Email customers 7 days before their Year 1 ends |
| **Every day** | Cross-check Stripe for "orphan" payments — paid customers without a WordPress account — and email you to investigate |
| **Every day** | Flag tours that have been in "pending archive" for >7 days so you remember to set them private at Matterport |

For these to fire on schedule, the hosting needs an OS-level cron hitting `wp-cron.php` every 5 minutes. **This must be set up on the server, not just in WordPress** (instructions are in the project docs).

---

## 7. The webhook log — your safety net

Every payment event Stripe sends us is logged in **WP Admin → Memory Lane → Webhooks log**:

- Event ID
- Type (e.g. `checkout.session.completed`)
- Status (processed / failed / duplicate)
- Retry count
- Error message (if failed)
- Received-at timestamp

**Idempotency:** if Stripe sends the same event twice (which they do for safety), we recognize it and don't double-process. So a customer never gets two welcome emails, never gets billed twice, never has two subscription rows created.

**Manual retry:** anything in `failed` status has a Retry button. Click → we replay the event through our handler.

**The notifications log** works the same way for emails. Failed email send? Click Retry.

---

## 8. Language

The customer can switch between **Dutch** (default) and **English** via the language toggle in the top-right of any auth/dashboard page. Their choice sticks in a cookie + their profile. All emails are sent in their chosen language.

You can extend the dictionary at any time (the strings are in `inc/i18n/strings/nl.php` and `en.php`).

---

## 9. Failure scenarios — what happens if…

| If… | What happens |
|---|---|
| Customer's monthly card payment fails | Stripe automatically retries (default: 4 attempts over ~3 weeks). Customer gets a "payment failed" email after each attempt. If all attempts fail, you get an admin email and the subscription becomes `past_due`. Customer keeps access during a configurable grace period (default 7 days), then access is revoked. |
| Customer pays but our webhook crashes | Stripe retries for up to 3 days. Plus our own cron retries every 15 minutes. The orphan-payment cron also flags this daily. The customer can't be "stuck paid but no account" for more than 24 hours. |
| Customer can't find the welcome email | Customers tab in admin → click "Resend welcome". A fresh password-set link is sent. |
| You need to give a refund | Refund in Stripe Dashboard → webhook fires → we sync the status. For full account closure, you can manually deactivate access from the customer profile. |
| You change a price | Edit + Sync — old customers unaffected, new customers pay the new price. |
| Customer cancels then changes their mind before period end | Stripe Customer Portal (linked from `/dashboard/subscription` → "Beheren in Stripe") lets them un-cancel. |

---

## 10. What you do daily / weekly / never

| Frequency | Task |
|---|---|
| **Daily** | Glance at the Overview KPIs in WP admin. Check if any webhooks failed or any past-due cases need attention. |
| **Weekly** | Confirm new booking requests. Assign tours after scan visits. Process any cancellations that need Matterport set-private. |
| **Monthly** | Spot-check the subscriptions list. Maybe download a report from Stripe Dashboard for accounting. |
| **Never** | Manually charge anyone. Manually update price IDs. Manually start subscriptions. Stripe + our system handle all the billing math. |

---

## 11. What this system is **not** doing (by design)

- **It does not store credit card numbers.** All card handling stays inside Stripe.
- **It does not auto-integrate with Matterport.** You still log in to Matterport to upload spaces and to toggle their privacy when a subscription ends. The system reminds you via email; it doesn't push to Matterport. (This is a future-feature option that needs a Matterport Business plan.)
- **It does not send marketing emails.** Only transactional ones (purchase, password reset, payment fail, cancel, booking, reminder).
- **It does not handle multiple tours per customer differently from one.** Each customer can have many tours assigned; they all appear in `/dashboard/tours`.

---

## 12. Summary in one sentence

**A customer pays once → WordPress automatically creates their account and gives them a private portal → Stripe automatically bills monthly after year one → you assign their tour in WP admin → automation handles renewals, reminders, payment retries, and access revocation; you only do business actions (confirm bookings, assign tours, manual support when emails ask you to).**
