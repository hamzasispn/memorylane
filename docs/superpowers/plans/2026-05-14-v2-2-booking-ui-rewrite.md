# V2-2 Booking UI rewrite — date grid + dimmed times

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the flat slot picker on `/boek` and `/dashboard/booking` with a date-grid + dimmed-time-slots layout. Move the data layer from "admin pre-creates each slot" to "rules-based generation, lazy materialisation on booking commit."

**Architecture:**
- New "working hours" config layer with hardcoded defaults (Mon–Fri 09:00–17:00, 60-min slots, capacity 1, 60-day window, no blocked dates). The admin UI to edit these comes in V2-3.
- New backend module `inc/booking/working-hours.php` (getters) and `inc/booking/availability.php` (compute available times for a date).
- New REST endpoint `GET /wp-json/memorylane/v1/booking/slots?date=YYYY-MM-DD` returns `{ date, times: [ { time, available } ] }`.
- The `availability_slots` DB row is only inserted when a customer commits a booking (find-or-create from date + time).
- `/boek` and `/dashboard/booking` POST handlers swap `slot_id` for `date` + `time`.
- Shared template partial `template-parts/booking/picker.php` used by both pages.
- The existing admin bulk-create form stays functional for V2-2 (removed in V2-3) so we don't break the admin during transition.

**Tech Stack:** PHP / WordPress, vanilla JS, Tailwind classes (already in theme), Stripe.

**Spec reference:** [2026-05-14-memory-lane-v2-design.md §3–§4](../specs/2026-05-14-memory-lane-v2-design.md)

---

## File map

| File | Action | Purpose |
|---|---|---|
| `inc/booking/working-hours.php` | **Create** | Getters for `ml_booking_working_hours()`, `_slot_length_minutes()`, `_capacity_per_slot()`, `_window_days()`, `_blocked_dates()`. Hardcoded defaults until V2-3. |
| `inc/booking/availability.php` | **Create** | `ml_booking_compute_times_for_date()`, `ml_booking_is_date_available()`, `ml_booking_get_times_with_availability()`, `ml_booking_get_available_dates()`. |
| `inc/booking/slots.php` | Modify | Add `ml_booking_find_or_create_slot( $date, $time )`. |
| `inc/booking/booking-rest.php` | **Create** | REST endpoint `GET /booking/slots?date=…`. Registers under `memorylane/v1`. |
| `inc/booking/boek-checkout.php` | Modify | Accept `date` + `time` instead of `slot_id`. Call find-or-create. Keep rate limiting + slot soft-hold unchanged. |
| `inc/booking/bookings.php` | Modify | `ml_booking_request` POST handler — accept `date` + `time`, call find-or-create. |
| `inc/bootstrap.php` (or wherever subsystems load) | Modify | `require_once` the two new files. |
| `template-parts/booking/picker.php` | **Create** | Shared date-grid + times-column partial + tiny inline JS. |
| `template-parts/public/boek.php` | Modify | Use the picker partial. Replace flat-slot block. |
| `template-parts/dashboard/booking.php` | Modify | Use the picker partial. Replace flat-slot block. |
| `assets/css/booking-picker.css` (or extend existing) | Create or extend | Date card styles, dimmed-times state, taken-time state. |

No DB migration. No test files (theme has no PHPUnit harness; verification is manual).

---

### Task 1: Working-hours config getters

**Files:**
- Create: `inc/booking/working-hours.php`

- [ ] **Step 1: Write the file**

```php
<?php
/**
 * Memory Lane — Booking working-hours config getters.
 * Reads from wp_options with hardcoded fallbacks until V2-3 ships the admin form.
 */
defined( 'ABSPATH' ) || exit;

const ML_BOOKING_OPT_WORKING_HOURS  = 'ml_booking_working_hours_json';
const ML_BOOKING_OPT_SLOT_LENGTH    = 'ml_booking_slot_length_minutes';
const ML_BOOKING_OPT_CAPACITY       = 'ml_booking_capacity_per_slot';
const ML_BOOKING_OPT_WINDOW_DAYS    = 'ml_booking_window_days';
const ML_BOOKING_OPT_BLOCKED_DATES  = 'ml_booking_blocked_dates_json';

function ml_booking_working_hours() {
    $json = (string) get_option( ML_BOOKING_OPT_WORKING_HOURS, '' );
    if ( $json ) {
        $h = json_decode( $json, true );
        if ( is_array( $h ) ) return $h;
    }
    return array(
        'mon' => array( 'enabled' => true,  'start' => '09:00', 'end' => '17:00' ),
        'tue' => array( 'enabled' => true,  'start' => '09:00', 'end' => '17:00' ),
        'wed' => array( 'enabled' => true,  'start' => '09:00', 'end' => '17:00' ),
        'thu' => array( 'enabled' => true,  'start' => '09:00', 'end' => '17:00' ),
        'fri' => array( 'enabled' => true,  'start' => '09:00', 'end' => '17:00' ),
        'sat' => array( 'enabled' => false, 'start' => '09:00', 'end' => '17:00' ),
        'sun' => array( 'enabled' => false, 'start' => '09:00', 'end' => '17:00' ),
    );
}

function ml_booking_slot_length_minutes() {
    return max( 15, (int) get_option( ML_BOOKING_OPT_SLOT_LENGTH, 60 ) );
}

function ml_booking_capacity_per_slot() {
    return max( 1, (int) get_option( ML_BOOKING_OPT_CAPACITY, 1 ) );
}

function ml_booking_window_days() {
    return max( 1, (int) get_option( ML_BOOKING_OPT_WINDOW_DAYS, 60 ) );
}

function ml_booking_blocked_dates() {
    $json = (string) get_option( ML_BOOKING_OPT_BLOCKED_DATES, '' );
    if ( ! $json ) return array();
    $list = json_decode( $json, true );
    return is_array( $list ) ? array_values( array_filter( $list, 'is_string' ) ) : array();
}

/**
 * Map a YYYY-MM-DD date (in site timezone) to a weekday key (mon/tue/...).
 */
function ml_booking_weekday_key_for_date( $date ) {
    $ts = strtotime( $date . ' 12:00:00' ); // noon avoids DST edge cases
    return strtolower( substr( wp_date( 'D', $ts ), 0, 3 ) );
}
```

- [ ] **Step 2: Register the file in bootstrap**

Open `inc/bootstrap.php`. Find the booking-related requires (search "booking/"). Add a line right after the existing booking requires:

```php
require_once ML_PATH . 'inc/booking/working-hours.php';
```

- [ ] **Step 3: Commit**

```bash
git add inc/booking/working-hours.php inc/bootstrap.php
git commit -m "feat(booking): working-hours config getters with hardcoded defaults"
```

---

### Task 2: Availability compute functions

**Files:**
- Create: `inc/booking/availability.php`

- [ ] **Step 1: Write the file**

```php
<?php
/**
 * Memory Lane — Booking availability computations.
 * Generates virtual time slots from working hours rules, overlays existing
 * booked slot rows, returns availability per time.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Compute the list of HH:MM start times for the given YYYY-MM-DD date,
 * purely from the working-hours rules (no booking overlay).
 */
function ml_booking_compute_times_for_date( $date ) {
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) return array();

    $weekday = ml_booking_weekday_key_for_date( $date );
    $hours   = ml_booking_working_hours();
    if ( empty( $hours[ $weekday ] ) || empty( $hours[ $weekday ]['enabled'] ) ) {
        return array();
    }

    $start_str = $hours[ $weekday ]['start'];
    $end_str   = $hours[ $weekday ]['end'];
    $start_ts  = strtotime( "{$date} {$start_str}:00" );
    $end_ts    = strtotime( "{$date} {$end_str}:00" );
    if ( ! $start_ts || ! $end_ts || $end_ts <= $start_ts ) return array();

    $len_sec = ml_booking_slot_length_minutes() * 60;
    $times   = array();
    for ( $t = $start_ts; $t + $len_sec <= $end_ts + 1; $t += $len_sec ) {
        $times[] = wp_date( 'H:i', $t );
    }
    return $times;
}

/**
 * Is this date in scope (within window, not blocked, working day)?
 */
function ml_booking_is_date_available( $date ) {
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) return false;
    if ( in_array( $date, ml_booking_blocked_dates(), true ) ) return false;

    $today    = wp_date( 'Y-m-d' );
    $max_date = wp_date( 'Y-m-d', strtotime( $today . ' +' . ml_booking_window_days() . ' days' ) );
    if ( $date < $today || $date > $max_date ) return false;

    $weekday = ml_booking_weekday_key_for_date( $date );
    $hours   = ml_booking_working_hours();
    return ! empty( $hours[ $weekday ] ) && ! empty( $hours[ $weekday ]['enabled'] );
}

/**
 * Convert a YYYY-MM-DD + HH:MM (site timezone) → UTC datetime string used by the DB.
 */
function ml_booking_local_to_utc( $date, $time ) {
    $dt = new DateTime( "{$date} {$time}:00", wp_timezone() );
    $dt->setTimezone( new DateTimeZone( 'UTC' ) );
    return $dt->format( 'Y-m-d H:i:s' );
}

/**
 * Get availability for one date: [{ time: 'HH:MM', available: bool }, ...]
 * Times in the past for today are marked unavailable. Times with a booked slot
 * row at capacity are marked unavailable.
 */
function ml_booking_get_times_with_availability( $date ) {
    if ( ! ml_booking_is_date_available( $date ) ) return array();

    $times = ml_booking_compute_times_for_date( $date );
    if ( empty( $times ) ) return array();

    $cap = ml_booking_capacity_per_slot();
    $today_local = wp_date( 'Y-m-d' );

    global $wpdb;
    $tbl = ml_table( 'availability_slots' );

    // Build a set of booked datetimes (UTC) we care about.
    $utc_to_time = array();
    foreach ( $times as $time ) {
        $utc_to_time[ ml_booking_local_to_utc( $date, $time ) ] = $time;
    }
    $utc_list = array_keys( $utc_to_time );
    $placeholders = implode( ',', array_fill( 0, count( $utc_list ), '%s' ) );
    $existing = $wpdb->get_results( $wpdb->prepare(
        "SELECT slot_start_datetime, booked_count, capacity, status
           FROM {$tbl}
          WHERE slot_start_datetime IN ($placeholders)",
        ...$utc_list
    ) );
    $booked_map = array();
    foreach ( $existing as $row ) {
        $booked_map[ $row->slot_start_datetime ] = array(
            'booked' => (int) $row->booked_count,
            'cap'    => (int) $row->capacity,
            'status' => $row->status,
        );
    }

    $now_ts = time();
    $result = array();
    foreach ( $times as $time ) {
        $utc = ml_booking_local_to_utc( $date, $time );
        $available = true;
        if ( isset( $booked_map[ $utc ] ) ) {
            $available = $booked_map[ $utc ]['status'] === 'open'
                       && $booked_map[ $utc ]['booked'] < $booked_map[ $utc ]['cap'];
        }
        if ( $date === $today_local && strtotime( "{$date} {$time}:00" ) <= $now_ts ) {
            $available = false;
        }
        $result[] = array( 'time' => $time, 'available' => $available );
    }
    return $result;
}

/**
 * Return list of YYYY-MM-DD dates within the booking window that have a
 * working day (regardless of per-time availability — fine-grained checks
 * happen when user clicks the date).
 */
function ml_booking_get_available_dates() {
    $today = wp_date( 'Y-m-d' );
    $days  = ml_booking_window_days();
    $out   = array();
    for ( $i = 0; $i <= $days; $i++ ) {
        $d = wp_date( 'Y-m-d', strtotime( $today . " +{$i} days" ) );
        $out[] = array(
            'date'      => $d,
            'available' => ml_booking_is_date_available( $d ),
            'is_today'  => $d === $today,
            'weekday'   => ml_booking_weekday_key_for_date( $d ),
        );
    }
    return $out;
}
```

- [ ] **Step 2: Register the file in bootstrap**

In `inc/bootstrap.php`, right after the line that requires `working-hours.php`:

```php
require_once ML_PATH . 'inc/booking/availability.php';
```

- [ ] **Step 3: Commit**

```bash
git add inc/booking/availability.php inc/bootstrap.php
git commit -m "feat(booking): availability computation from working-hours rules"
```

---

### Task 3: `ml_booking_find_or_create_slot` helper

**Files:**
- Modify: `inc/booking/slots.php` (add to end of file)

- [ ] **Step 1: Append the function to `inc/booking/slots.php`**

```php
/**
 * Find an existing availability_slots row matching the given local date+time,
 * or create one using the working-hours rules. Returns the row object, or
 * null if the date+time is not valid per the rules.
 */
function ml_booking_find_or_create_slot( $date, $time ) {
    if ( ! ml_booking_is_date_available( $date ) ) return null;

    $valid_times = ml_booking_compute_times_for_date( $date );
    if ( ! in_array( $time, $valid_times, true ) ) return null;

    $utc_start = ml_booking_local_to_utc( $date, $time );

    global $wpdb;
    $tbl = ml_table( 'availability_slots' );

    $existing = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$tbl} WHERE slot_start_datetime = %s LIMIT 1",
        $utc_start
    ) );
    if ( $existing ) return $existing;

    $len_min   = ml_booking_slot_length_minutes();
    $utc_end   = ( new DateTime( $utc_start, new DateTimeZone( 'UTC' ) ) )
                  ->modify( "+{$len_min} minutes" )->format( 'Y-m-d H:i:s' );

    $wpdb->insert( $tbl, array(
        'slot_start_datetime' => $utc_start,
        'slot_end_datetime'   => $utc_end,
        'capacity'            => ml_booking_capacity_per_slot(),
        'booked_count'        => 0,
        'status'              => 'open',
        'created_at'          => current_time( 'mysql', true ),
    ) );
    if ( ! $wpdb->insert_id ) return null;

    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$tbl} WHERE id = %d",
        $wpdb->insert_id
    ) );
}
```

- [ ] **Step 2: Commit**

```bash
git add inc/booking/slots.php
git commit -m "feat(booking): find-or-create slot row from date+time"
```

---

### Task 4: REST endpoint `GET /booking/slots`

**Files:**
- Create: `inc/booking/booking-rest.php`

- [ ] **Step 1: Write the file**

```php
<?php
/**
 * Memory Lane — Booking REST endpoints (date picker support).
 */
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
    register_rest_route( 'memorylane/v1', '/booking/slots', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'ml_rest_booking_slots',
        'args'                => array(
            'date' => array(
                'required' => true,
                'type'     => 'string',
            ),
        ),
    ) );
} );

function ml_rest_booking_slots( WP_REST_Request $req ) {
    $date = (string) $req->get_param( 'date' );
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'bad_date' ), 400 );
    }
    if ( ! ml_booking_is_date_available( $date ) ) {
        return new WP_REST_Response( array( 'ok' => true, 'date' => $date, 'times' => array(), 'closed' => true ), 200 );
    }
    $times = ml_booking_get_times_with_availability( $date );
    return new WP_REST_Response( array( 'ok' => true, 'date' => $date, 'times' => $times, 'closed' => false ), 200 );
}
```

- [ ] **Step 2: Register in bootstrap**

In `inc/bootstrap.php`, after the availability require:

```php
require_once ML_PATH . 'inc/booking/booking-rest.php';
```

- [ ] **Step 3: Manual verify**

Open in browser:

```
http://localhost/memory-lane/wp-json/memorylane/v1/booking/slots?date=2026-05-15
```

(replace with a Friday within 60 days from today)

Expected JSON:

```json
{ "ok": true, "date": "2026-05-15", "times": [
  { "time": "09:00", "available": true },
  { "time": "10:00", "available": true },
  ...
], "closed": false }
```

For a Saturday: `"times": [], "closed": true`.

- [ ] **Step 4: Commit**

```bash
git add inc/booking/booking-rest.php inc/bootstrap.php
git commit -m "feat(booking): GET /booking/slots REST endpoint"
```

---

### Task 5: Shared booking-picker partial

**Files:**
- Create: `template-parts/booking/picker.php`

The partial takes one variable: `$picker_id` (string, used to namespace DOM IDs in case two pickers are on one page — defaults to `ml-picker`). It also expects the consumer to render its own hidden inputs `name="date"` and `name="time"` inside a form.

- [ ] **Step 1: Write the partial**

```php
<?php
/**
 * Booking date+time picker partial.
 * Renders an 8-col date grid + 4-col times column.
 * Times start dimmed until a date is picked.
 *
 * @param string $picker_id  unique ID prefix for this picker instance (default: 'ml-picker')
 */
defined( 'ABSPATH' ) || exit;
$picker_id = $picker_id ?? 'ml-picker';
$dates     = ml_booking_get_available_dates();
$rest_url  = rest_url( 'memorylane/v1/booking/slots' );
?>
<div class="ml-picker" id="<?php echo esc_attr( $picker_id ); ?>" data-rest="<?php echo esc_url( $rest_url ); ?>">
    <div class="ml-picker__grid">
        <div class="ml-picker__dates">
            <div class="ml-picker__dates-grid">
                <?php foreach ( $dates as $d ) :
                    $ts        = strtotime( $d['date'] . ' 12:00:00' );
                    $is_avail  = (bool) $d['available'];
                    $is_today  = (bool) $d['is_today'];
                    $classes   = 'ml-picker__date';
                    if ( ! $is_avail ) $classes .= ' is-closed';
                    if ( $is_today  ) $classes .= ' is-today';
                ?>
                    <button type="button"
                            class="<?php echo esc_attr( $classes ); ?>"
                            data-date="<?php echo esc_attr( $d['date'] ); ?>"
                            <?php echo $is_avail ? '' : 'disabled aria-disabled="true"'; ?>>
                        <span class="ml-picker__date-wd"><?php echo esc_html( wp_date( 'D', $ts ) ); ?></span>
                        <span class="ml-picker__date-day"><?php echo esc_html( wp_date( 'j', $ts ) ); ?></span>
                        <span class="ml-picker__date-mo"><?php echo esc_html( wp_date( 'M', $ts ) ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ml-picker__times is-dim" data-state="dim">
            <div class="ml-picker__times-hint">
                <?php echo esc_html( ml_t( 'booking.pick_date_first', 'Pick a date first' ) ); ?>
            </div>
            <div class="ml-picker__times-list" hidden></div>
        </div>
    </div>
</div>

<script>
(function () {
    var root    = document.getElementById('<?php echo esc_js( $picker_id ); ?>');
    if (!root) return;
    var rest    = root.dataset.rest;
    var dates   = root.querySelectorAll('.ml-picker__date');
    var col     = root.querySelector('.ml-picker__times');
    var hint    = root.querySelector('.ml-picker__times-hint');
    var listEl  = root.querySelector('.ml-picker__times-list');
    var form    = root.closest('form');
    var dateIn  = form ? form.querySelector('input[name="date"]') : null;
    var timeIn  = form ? form.querySelector('input[name="time"]') : null;
    var submit  = form ? form.querySelector('[type="submit"]') : null;

    function setSubmitEnabled() {
        if (!submit) return;
        submit.disabled = !(dateIn && dateIn.value && timeIn && timeIn.value);
    }
    setSubmitEnabled();

    function renderTimes(payload) {
        listEl.innerHTML = '';
        if (!payload || !payload.times || !payload.times.length) {
            hint.textContent = '<?php echo esc_js( ml_t( 'booking.no_times', 'No times available on this day.' ) ); ?>';
            hint.hidden = false;
            listEl.hidden = true;
            col.classList.add('is-dim');
            return;
        }
        hint.hidden = true;
        listEl.hidden = false;
        col.classList.remove('is-dim');
        payload.times.forEach(function (t) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ml-picker__time' + (t.available ? '' : ' is-taken');
            btn.dataset.time = t.time;
            btn.textContent = t.time;
            if (!t.available) { btn.disabled = true; btn.setAttribute('aria-disabled', 'true'); }
            btn.addEventListener('click', function () {
                listEl.querySelectorAll('.ml-picker__time').forEach(function (b) { b.classList.remove('is-selected'); });
                btn.classList.add('is-selected');
                if (timeIn) timeIn.value = t.time;
                setSubmitEnabled();
            });
            listEl.appendChild(btn);
        });
    }

    dates.forEach(function (d) {
        if (d.disabled) return;
        d.addEventListener('click', function () {
            dates.forEach(function (x) { x.classList.remove('is-selected'); });
            d.classList.add('is-selected');
            if (dateIn) dateIn.value = d.dataset.date;
            if (timeIn) timeIn.value = '';
            setSubmitEnabled();
            hint.textContent = '<?php echo esc_js( ml_t( 'common.loading', 'Loading…' ) ); ?>';
            hint.hidden = false;
            listEl.hidden = true;
            col.classList.add('is-dim');
            fetch(rest + '?date=' + encodeURIComponent(d.dataset.date), { credentials: 'same-origin' })
              .then(function (r) { return r.json(); })
              .then(renderTimes)
              .catch(function () {
                  hint.textContent = '<?php echo esc_js( ml_t( 'common.error_generic', 'Something went wrong.' ) ); ?>';
              });
        });
    });
})();
</script>
```

- [ ] **Step 2: Commit**

```bash
git add template-parts/booking/picker.php
git commit -m "feat(booking): shared date+time picker partial"
```

---

### Task 6: Booking picker CSS

**Files:**
- Create: `assets/css/booking-picker.css`
- Modify: theme `functions.php` (or wherever public assets are enqueued) — enqueue the new stylesheet

- [ ] **Step 1: Write the CSS**

```css
/* Booking date + time picker */
.ml-picker { width: 100%; }
.ml-picker__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}
@media (min-width: 768px) {
    .ml-picker__grid {
        grid-template-columns: repeat(12, minmax(0, 1fr));
    }
    .ml-picker__dates { grid-column: span 8 / span 8; }
    .ml-picker__times { grid-column: span 4 / span 4; }
}

.ml-picker__dates-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}
@media (min-width: 480px) { .ml-picker__dates-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (min-width: 768px) { .ml-picker__dates-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }

.ml-picker__date {
    appearance: none;
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 10px 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    cursor: pointer;
    transition: border-color .15s, background .15s, transform .1s;
    font: inherit;
    color: inherit;
}
.ml-picker__date:hover:not(:disabled) { border-color: #2563EB; transform: translateY(-1px); }
.ml-picker__date.is-selected { border-color: #2563EB; background: #EFF6FF; box-shadow: 0 0 0 2px rgba(37, 99, 235, .15); }
.ml-picker__date.is-today { outline: 1px dashed rgba(37, 99, 235, .35); outline-offset: 2px; }
.ml-picker__date.is-closed,
.ml-picker__date:disabled { opacity: .35; cursor: not-allowed; }
.ml-picker__date-wd { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #6B7280; }
.ml-picker__date-day { font-size: 22px; font-weight: 600; line-height: 1; }
.ml-picker__date-mo { font-size: 11px; color: #6B7280; }

.ml-picker__times {
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 14px;
    min-height: 220px;
    transition: opacity .2s;
}
.ml-picker__times.is-dim { opacity: .4; }
.ml-picker__times-hint { text-align: center; color: #6B7280; padding: 24px 0; }
.ml-picker__times-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}
.ml-picker__time {
    appearance: none;
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    padding: 10px 8px;
    text-align: center;
    cursor: pointer;
    font: inherit;
    color: inherit;
    transition: border-color .15s, background .15s;
}
.ml-picker__time:hover:not(:disabled) { border-color: #2563EB; }
.ml-picker__time.is-selected { border-color: #2563EB; background: #EFF6FF; }
.ml-picker__time.is-taken,
.ml-picker__time:disabled { opacity: .4; text-decoration: line-through; cursor: not-allowed; }
```

- [ ] **Step 2: Enqueue the stylesheet**

Find the theme's main `wp_enqueue_style` call (likely in `functions.php` or `inc/enqueue.php`). Add:

```php
wp_enqueue_style( 'ml-booking-picker', get_template_directory_uri() . '/assets/css/booking-picker.css', array(), filemtime( get_template_directory() . '/assets/css/booking-picker.css' ) );
```

(Enqueue only on `/boek` and dashboard pages if you want to scope it. Otherwise keep global — the file is tiny.)

- [ ] **Step 3: Commit**

```bash
git add assets/css/booking-picker.css functions.php
git commit -m "feat(booking): picker stylesheet"
```

---

### Task 7: Update `/boek` POST handler — accept `date` + `time`

**Files:**
- Modify: `inc/booking/boek-checkout.php`

- [ ] **Step 1: Swap `slot_id` for `date` + `time`**

In `inc/booking/boek-checkout.php`, find this block:

```php
$slot_id = (int) $req->get_param( 'slot_id' );
$email   = sanitize_email( (string) $req->get_param( 'email' ) );
$name    = sanitize_text_field( (string) $req->get_param( 'name' ) );
$phone   = sanitize_text_field( (string) $req->get_param( 'phone' ) );
$address = sanitize_text_field( (string) $req->get_param( 'address' ) );
$notes   = sanitize_textarea_field( (string) $req->get_param( 'notes' ) );

if ( ! $slot_id || ! $email || ! $name || ! $phone || ! $address ) {
    return new WP_REST_Response( array( 'ok' => false, 'error' => 'missing_fields' ), 400 );
}
```

Replace with:

```php
$date    = sanitize_text_field( (string) $req->get_param( 'date' ) );
$time    = sanitize_text_field( (string) $req->get_param( 'time' ) );
$email   = sanitize_email( (string) $req->get_param( 'email' ) );
$name    = sanitize_text_field( (string) $req->get_param( 'name' ) );
$phone   = sanitize_text_field( (string) $req->get_param( 'phone' ) );
$address = sanitize_text_field( (string) $req->get_param( 'address' ) );
$notes   = sanitize_textarea_field( (string) $req->get_param( 'notes' ) );

if ( ! $date || ! $time || ! $email || ! $name || ! $phone || ! $address ) {
    return new WP_REST_Response( array( 'ok' => false, 'error' => 'missing_fields' ), 400 );
}
```

- [ ] **Step 2: Replace the slot lookup block**

Find this block:

```php
$slot = ml_get_slot( $slot_id );
if ( ! $slot || $slot->status !== 'open' || $slot->booked_count >= $slot->capacity ) {
    return new WP_REST_Response( array( 'ok' => false, 'error' => 'slot_unavailable' ), 409 );
}
if ( strtotime( $slot->slot_start_datetime . ' UTC' ) <= time() ) {
    return new WP_REST_Response( array( 'ok' => false, 'error' => 'slot_in_past' ), 409 );
}

// Soft-hold the slot so two visitors don't grab it.
ml_increment_slot_booked( $slot_id );
```

Replace with:

```php
$slot = ml_booking_find_or_create_slot( $date, $time );
if ( ! $slot ) {
    return new WP_REST_Response( array( 'ok' => false, 'error' => 'slot_invalid' ), 400 );
}
if ( $slot->status !== 'open' || $slot->booked_count >= $slot->capacity ) {
    return new WP_REST_Response( array( 'ok' => false, 'error' => 'slot_unavailable' ), 409 );
}
if ( strtotime( $slot->slot_start_datetime . ' UTC' ) <= time() ) {
    return new WP_REST_Response( array( 'ok' => false, 'error' => 'slot_in_past' ), 409 );
}

$slot_id = (int) $slot->id;
// Soft-hold the slot so two visitors don't grab it.
ml_increment_slot_booked( $slot_id );
```

- [ ] **Step 3: Commit**

```bash
git add inc/booking/boek-checkout.php
git commit -m "feat(boek): accept date+time on REST POST, find-or-create slot"
```

---

### Task 8: Rebuild `/boek` template

**Files:**
- Modify: `template-parts/public/boek.php` — replace the flat-slot block with the picker partial

- [ ] **Step 1: Replace the slot picker + form glue**

Open `template-parts/public/boek.php`. Replace **from** the `<?php if ( empty( $slots ) ) : ?>` block down to the closing `</script>` tag (the entire current booking UI) with:

```php
<form id="ml-boek-form" class="ml-mt-3">
    <input type="hidden" name="date" value="">
    <input type="hidden" name="time" value="">

    <h2 class="ml-h2 ml-mt-3"><?php echo esc_html( ml_t( 'boek.pick_slot', '1. Kies je opname-moment' ) ); ?></h2>
    <?php
        $picker_id = 'ml-boek-picker';
        include ML_PATH . 'template-parts/booking/picker.php';
    ?>

    <h2 class="ml-h2 ml-mt-3"><?php echo esc_html( ml_t( 'boek.your_info', '2. Jouw gegevens' ) ); ?></h2>
    <div class="ml-grid ml-grid--2" style="gap:16px;">
        <div><label class="ml-label"><?php echo esc_html( ml_t( 'boek.name', 'Naam' ) ); ?> *</label><input type="text" name="name" class="ml-input" required></div>
        <div><label class="ml-label"><?php echo esc_html( ml_t( 'boek.email', 'E-mailadres' ) ); ?> *</label><input type="email" name="email" class="ml-input" required></div>
        <div><label class="ml-label"><?php echo esc_html( ml_t( 'boek.phone', 'Telefoon' ) ); ?> *</label><input type="tel" name="phone" class="ml-input" required></div>
        <div><label class="ml-label"><?php echo esc_html( ml_t( 'boek.address', 'Adres van de woning' ) ); ?> *</label><input type="text" name="address" class="ml-input" required placeholder="Straat 12, 9000 Gent"></div>
    </div>

    <div class="ml-mt-2">
        <label class="ml-label"><?php echo esc_html( ml_t( 'boek.notes', 'Opmerkingen (optioneel)' ) ); ?></label>
        <textarea name="notes" rows="3" class="ml-input"></textarea>
    </div>

    <div class="ml-card ml-card--lg ml-mt-3" style="background:#F4F4F5;">
        <div class="ml-row-between">
            <div>
                <p class="ml-card__title"><?php echo esc_html( ml_t( 'boek.total', 'Te betalen nu' ) ); ?></p>
                <p class="ml-text-sm ml-text-muted"><?php echo esc_html( ml_t( 'boek.includes', 'Opname + virtuele tour + 1 jaar online beschikbaarheid + Matterport-activatiekost.' ) ); ?></p>
            </div>
            <p class="ml-h2"><?php echo esc_html( $fee ? $cur . ' ' . $fee : '€ —' ); ?></p>
        </div>
    </div>

    <button type="submit" class="ml-btn ml-btn--primary ml-btn--block ml-mt-3" id="ml-boek-submit" disabled>
        <?php echo esc_html( ml_t( 'boek.cta', 'Bevestig en betaal' ) ); ?>
    </button>
    <div id="ml-boek-error" class="ml-alert ml-alert--danger ml-mt-2" style="display:none;"></div>
</form>

<script>
(function () {
    var form   = document.getElementById('ml-boek-form');
    var submit = document.getElementById('ml-boek-submit');
    var errBox = document.getElementById('ml-boek-error');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        errBox.style.display = 'none';
        var data = new FormData(form);
        var payload = {};
        data.forEach(function (v, k) { payload[k] = v; });
        if (!payload.date || !payload.time) {
            errBox.textContent = '<?php echo esc_js( ml_t( 'boek.err.pick_slot', 'Kies eerst een datum en uur.' ) ); ?>';
            errBox.style.display = 'block';
            return;
        }
        submit.disabled = true;
        submit.textContent = '<?php echo esc_js( ml_t( 'common.loading', 'Laden...' ) ); ?>';
        fetch('<?php echo esc_url_raw( rest_url( 'memorylane/v1/boek' ) ); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>' },
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json(); }).then(function (out) {
            if (out && out.ok && out.url) { window.location = out.url; return; }
            errBox.textContent = (out && out.error) || '<?php echo esc_js( ml_t( 'common.error_generic', 'Something went wrong.' ) ); ?>';
            errBox.style.display = 'block';
            submit.disabled = false;
            submit.textContent = '<?php echo esc_js( ml_t( 'boek.cta', 'Bevestig en betaal' ) ); ?>';
        }).catch(function () {
            errBox.textContent = 'Network error.';
            errBox.style.display = 'block';
            submit.disabled = false;
            submit.textContent = '<?php echo esc_js( ml_t( 'boek.cta', 'Bevestig en betaal' ) ); ?>';
        });
    });
})();
</script>
```

Also delete the old `$slots = ml_get_open_slots( 60 );` line near the top of the file (no longer needed).

- [ ] **Step 2: Manual verify**

Open `/boek` in browser. Confirm:
- Date grid renders (next ~60 days, weekends greyed out).
- Times column starts dimmed with "Pick a date first."
- Clicking a working date loads times via fetch, brightens the column.
- Clicking a time selects it, enables submit.
- Submitting redirects to Stripe Checkout with two line items.

- [ ] **Step 3: Commit**

```bash
git add template-parts/public/boek.php
git commit -m "feat(boek): new date-grid + dimmed-times picker on /boek"
```

---

### Task 9: Update `/dashboard/booking` template + POST handler

**Files:**
- Modify: `template-parts/dashboard/booking.php`
- Modify: `inc/booking/bookings.php` — `ml_booking_request` POST handler

- [ ] **Step 1: Replace the slot picker in `template-parts/dashboard/booking.php`**

Locate the block starting `<h2 class="ml-h2">Available slots</h2>` and ending with `</form>` and `</div>`. Replace with:

```php
<h2 class="ml-h2"><?php echo esc_html__( 'Pick your appointment', 'memorylane' ); ?></h2>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <?php wp_nonce_field( 'ml_booking_request' ); ?>
    <input type="hidden" name="action" value="ml_booking_request">
    <input type="hidden" name="date" value="">
    <input type="hidden" name="time" value="">

    <?php
        $picker_id = 'ml-dash-picker';
        include ML_PATH . 'template-parts/booking/picker.php';
    ?>

    <div class="ml-mt-3">
        <label class="ml-label"><?php echo esc_html__( 'Notes (optional)', 'memorylane' ); ?></label>
        <textarea name="notes" class="ml-input" rows="3"></textarea>
    </div>

    <button class="ml-btn ml-btn--primary ml-mt-2" type="submit" disabled><?php ml_e( 'common.confirm' ); ?></button>
</form>
```

Also delete the line `$slots = ml_get_open_slots( 60 );` near the top (no longer used). Keep `$user_bookings`.

- [ ] **Step 2: Update the POST handler in `inc/booking/bookings.php`**

In the `admin_post_ml_booking_request` callback, replace this block:

```php
$slot_id = (int) ( $_POST['slot_id'] ?? 0 );
$notes   = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

$slot = ml_get_slot( $slot_id );
if ( ! $slot || $slot->status !== 'open' || $slot->booked_count >= $slot->capacity ) {
    ml_flash_set( 'error', __( 'This slot is no longer available.', 'memorylane' ) );
    wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
}
if ( strtotime( $slot->slot_start_datetime . ' UTC' ) <= time() ) {
    ml_flash_set( 'error', __( 'Slot is in the past.', 'memorylane' ) );
    wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
}
```

with:

```php
$date  = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
$time  = sanitize_text_field( wp_unslash( $_POST['time'] ?? '' ) );
$notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

if ( ! $date || ! $time ) {
    ml_flash_set( 'error', __( 'Please pick a date and time.', 'memorylane' ) );
    wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
}

$slot = ml_booking_find_or_create_slot( $date, $time );
if ( ! $slot ) {
    ml_flash_set( 'error', __( 'This date and time is not available.', 'memorylane' ) );
    wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
}
if ( $slot->status !== 'open' || $slot->booked_count >= $slot->capacity ) {
    ml_flash_set( 'error', __( 'This slot is no longer available.', 'memorylane' ) );
    wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
}
if ( strtotime( $slot->slot_start_datetime . ' UTC' ) <= time() ) {
    ml_flash_set( 'error', __( 'Slot is in the past.', 'memorylane' ) );
    wp_safe_redirect( home_url( '/dashboard/booking' ) ); exit;
}
$slot_id = (int) $slot->id;
```

- [ ] **Step 3: Manual verify**

Log in as a customer, go to `/dashboard/booking`. Confirm:
- Date grid + dimmed times render.
- Picking date+time + submitting creates a booking (visible in the "My bookings" table).
- Admin email about the new booking arrives.

- [ ] **Step 4: Commit**

```bash
git add template-parts/dashboard/booking.php inc/booking/bookings.php
git commit -m "feat(dashboard): new date+time picker on /dashboard/booking"
```

---

### Task 10: Add translation strings

**Files:**
- Modify: `inc/i18n/strings/nl.php`
- Modify: `inc/i18n/strings/en.php`

The picker uses these keys: `booking.pick_date_first`, `booking.no_times`, `common.loading`, `common.error_generic`, `boek.err.pick_slot`, `boek.includes`. Most already exist. Add any missing.

- [ ] **Step 1: Add missing keys to `inc/i18n/strings/nl.php`**

```php
'booking.pick_date_first' => 'Kies eerst een datum',
'booking.no_times'        => 'Geen vrije uren op deze dag.',
```

- [ ] **Step 2: Add same keys to `inc/i18n/strings/en.php`**

```php
'booking.pick_date_first' => 'Pick a date first',
'booking.no_times'        => 'No times available on this day.',
```

- [ ] **Step 3: Commit**

```bash
git add inc/i18n/strings/nl.php inc/i18n/strings/en.php
git commit -m "i18n(booking): add picker strings"
```

---

## Verification checklist (run after all tasks)

- [ ] `/boek` renders date grid (8 cols) + times column (4 cols) on desktop.
- [ ] Times column starts dimmed with "Pick a date first."
- [ ] Clicking a weekday brightens the times column; weekends are disabled.
- [ ] Clicking a time enables the submit button.
- [ ] Submit creates a Stripe Checkout with two line items.
- [ ] `/dashboard/booking` shows the same picker, submitting books and emails admin.
- [ ] Existing admin bulk-create form still works (regression check — V2-3 will remove it).
- [ ] Mobile (< 768 px): dates stack above times. No horizontal scroll.
- [ ] REST endpoint `/wp-json/memorylane/v1/booking/slots?date=` returns expected JSON.

---

## Done definition

V2-2 is shipped when:

- Both booking surfaces use the date+time picker partial.
- The data model no longer needs admin-pre-created slot rows for `/boek` to function (a slot row is created lazily on commit).
- The REST endpoint returns the right times for any working date.
- The existing admin slot list still shows historical / pre-created slots (no schema change).
