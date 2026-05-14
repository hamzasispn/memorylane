<?php
/**
 * Booking date+time picker partial.
 *
 * Renders an 8-col date grid + 4-col times column. Times start dimmed
 * until a date is picked. Expects the consuming <form> to declare two
 * hidden inputs: name="date" and name="time".
 *
 * @var string $picker_id  unique ID prefix (default: 'ml-picker')
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
                    $ts       = strtotime( $d['date'] . ' 12:00:00' );
                    $is_avail = (bool) $d['available'];
                    $is_today = (bool) $d['is_today'];
                    $classes  = 'ml-picker__date';
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
                <?php echo esc_html( ml_t( 'booking.pick_date_first', 'Kies eerst een datum' ) ); ?>
            </div>
            <div class="ml-picker__times-list" hidden></div>
        </div>
    </div>
</div>

<script>
(function () {
    var root   = document.getElementById('<?php echo esc_js( $picker_id ); ?>');
    if (!root) return;
    var rest   = root.dataset.rest;
    var dates  = root.querySelectorAll('.ml-picker__date');
    var col    = root.querySelector('.ml-picker__times');
    var hint   = root.querySelector('.ml-picker__times-hint');
    var listEl = root.querySelector('.ml-picker__times-list');
    var form   = root.closest('form');

    // Lazy lookups — submit button may not exist yet at script-execute time
    // because the picker partial is rendered above its containing form's
    // submit button. Re-query on each call so the toggle works once parsed.
    function getDateIn() { return form ? form.querySelector('input[name="date"]') : null; }
    function getTimeIn() { return form ? form.querySelector('input[name="time"]') : null; }
    function getSubmit() { return form ? form.querySelector('[type="submit"]') : null; }

    function setSubmitEnabled() {
        var dateIn = getDateIn();
        var timeIn = getTimeIn();
        var submit = getSubmit();
        if (!submit) return;
        submit.disabled = !(dateIn && dateIn.value && timeIn && timeIn.value);
    }
    // First call: after the rest of the form has been parsed. If DOM is
    // already past 'loading', call immediately.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setSubmitEnabled);
    } else {
        setSubmitEnabled();
    }

    function showHint(text) {
        hint.textContent = text;
        hint.hidden = false;
        listEl.hidden = true;
        col.classList.add('is-dim');
    }

    function renderTimes(payload) {
        listEl.innerHTML = '';
        if (!payload || !payload.times || !payload.times.length) {
            showHint('<?php echo esc_js( ml_t( 'booking.no_times', 'Geen vrije uren op deze dag.' ) ); ?>');
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
                var timeIn = getTimeIn();
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
            var dateIn = getDateIn();
            var timeIn = getTimeIn();
            if (dateIn) dateIn.value = d.dataset.date;
            if (timeIn) timeIn.value = '';
            setSubmitEnabled();
            showHint('<?php echo esc_js( ml_t( 'common.loading', 'Laden...' ) ); ?>');
            fetch(rest + '?date=' + encodeURIComponent(d.dataset.date), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(renderTimes)
                .catch(function () {
                    showHint('<?php echo esc_js( ml_t( 'common.error_generic', 'Er ging iets mis.' ) ); ?>');
                });
        });
    });
})();
</script>
