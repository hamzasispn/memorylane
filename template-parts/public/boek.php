<?php
/**
 * /boek — public booking, wrapped in the marketing site theme.
 * Visitor picks date + time, fills name/email/phone + structured address.
 * When payment is required → Stripe Checkout; otherwise the booking is created
 * directly (see inc/booking/boek-checkout.php).
 */
defined( 'ABSPATH' ) || exit;

$plan = ml_plan_get();
$fee  = $plan['year_one_amount'] ? ml_from_minor_units( (int) $plan['year_one_amount'] ) : '';
$cur  = strtoupper( $plan['currency'] );

$ml_pay      = ml_booking_payment_required();
$ml_cta      = $ml_pay
    ? ml_t( 'boek.cta', 'Bevestig en betaal' )
    : ml_t( 'boek.cta_no_pay', 'Bevestig je boeking' );
$ml_subtitle = $ml_pay
    ? ml_t( 'boek.subtitle', 'Kies een datum, vul je gegevens in en betaal. Je klantenzone wordt automatisch aangemaakt.' )
    : ml_t( 'boek.subtitle_no_pay', 'Kies een datum en vul je gegevens in. We nemen contact op om je opname te bevestigen.' );
$ml_countries = ml_iso_countries();

get_header();
?>
<link rel="stylesheet" href="<?php echo esc_url( ML_URI . 'assets/src/css/dashboard.css' ); ?>?v=<?php echo esc_attr( ML_VERSION ); ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">

<style>
    /* Keep the fixed marketing header readable over the light booking page. */
    .site-header { background: rgba(21, 39, 81, .93); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); }
    .ml-boek-page { background: linear-gradient(to bottom, #cabee2, #cad7dd); min-height: 100vh; padding: 150px 20px 90px; }
    .ml-boek-wrap { max-width: 880px; margin: 0 auto; }
    .ml-boek-back { color: #152751; font-weight: 600; text-decoration: none; opacity: .9; }
    .ml-boek-back:hover { opacity: 1; text-decoration: underline; }
    .ml-boek-title { font-family: 'AdleryPro', serif; color: #152751; font-size: clamp(2.2rem, 6vw, 3.4rem); line-height: 1.05; margin: 14px 0 6px; font-weight: 400; }
    .ml-boek-sub { color: #1b2c50; opacity: .85; font-size: 1.05rem; max-width: 60ch; margin: 0 0 8px; }
    .ml-boek-card { background: #fff; border-radius: 22px; padding: clamp(22px, 4vw, 42px); box-shadow: 0 24px 60px rgba(21, 39, 81, .16); margin-top: 26px; }
    .ml-boek-card .ml-h2 { color: #152751; margin-top: 8px; }
    .ml-boek-card .ml-h2:not(:first-child) { margin-top: 28px; }
    .ml-boek-submit { background: #152751; color: #fff; border: 0; border-radius: 999px; padding: 15px 22px; width: 100%; font-size: 1.05rem; font-weight: 600; cursor: pointer; margin-top: 26px; transition: background .15s, transform .15s, opacity .15s; }
    .ml-boek-submit:hover:not(:disabled) { background: #0e1a38; transform: translateY(-1px); }
    .ml-boek-submit:disabled { opacity: .45; cursor: not-allowed; }
    /* intl-tel-input takes full width */
    .iti { width: 100%; }
    /* address autocomplete dropdown */
    .ml-ac { position: relative; }
    .ml-ac__list { position: absolute; z-index: 30; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid #E4E4E7; border-radius: 10px; box-shadow: 0 12px 28px rgba(21,39,81,.16); max-height: 260px; overflow-y: auto; display: none; }
    .ml-ac__list.is-open { display: block; }
    .ml-ac__item { padding: 10px 14px; font-size: 14px; cursor: pointer; color: #27272A; border-bottom: 1px solid #F4F4F5; }
    .ml-ac__item:last-child { border-bottom: 0; }
    .ml-ac__item:hover, .ml-ac__item.is-active { background: #E7E2F2; color: #152751; }
</style>

<main class="ml-boek-page">
    <div class="ml-boek-wrap">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ml-boek-back">← <?php ml_e( 'common.back' ); ?></a>
        <h1 class="ml-boek-title"><?php echo esc_html( ml_t( 'boek.title', 'Boek jouw opname' ) ); ?></h1>
        <p class="ml-boek-sub"><?php echo esc_html( $ml_subtitle ); ?></p>

        <form id="ml-boek-form" class="ml-boek-card">
            <input type="hidden" name="date" value="">
            <input type="hidden" name="time" value="">

            <h2 class="ml-h2"><?php echo esc_html( ml_t( 'boek.pick_slot', '1. Kies je opname-moment' ) ); ?></h2>
            <div class="ml-mt-2">
                <?php
                    $picker_id = 'ml-boek-picker';
                    include ML_PATH . 'template-parts/booking/picker.php';
                ?>
            </div>

            <h2 class="ml-h2"><?php echo esc_html( ml_t( 'boek.your_info', '2. Jouw gegevens' ) ); ?></h2>
            <div class="ml-grid ml-grid--2" style="gap:16px;">
                <div>
                    <label class="ml-label"><?php echo esc_html( ml_t( 'boek.name', 'Naam' ) ); ?> *</label>
                    <input type="text" name="name" class="ml-input" autocomplete="name" required>
                </div>
                <div>
                    <label class="ml-label"><?php echo esc_html( ml_t( 'boek.email', 'E-mailadres' ) ); ?> *</label>
                    <input type="email" name="email" class="ml-input" autocomplete="email" required>
                </div>
            </div>
            <div class="ml-mt-2">
                <label class="ml-label"><?php echo esc_html( ml_t( 'boek.phone', 'Telefoon' ) ); ?> *</label>
                <input type="tel" id="ml-boek-phone" name="phone" class="ml-input" autocomplete="tel" required>
            </div>

            <h2 class="ml-h2"><?php echo esc_html( ml_t( 'boek.address_heading', '3. Adres van de woning' ) ); ?></h2>
            <div class="ml-ac">
                <label class="ml-label"><?php echo esc_html( ml_t( 'boek.street', 'Straat en huisnummer' ) ); ?> *</label>
                <input type="text" id="ml-boek-street" name="street" class="ml-input" autocomplete="off"
                       placeholder="<?php echo esc_attr( ml_t( 'boek.street_ph', 'Begin te typen voor suggesties…' ) ); ?>" required>
                <div class="ml-ac__list" id="ml-boek-ac"></div>
            </div>
            <div class="ml-grid ml-grid--2 ml-mt-2" style="gap:16px;">
                <div>
                    <label class="ml-label"><?php echo esc_html( ml_t( 'boek.postcode', 'Postcode' ) ); ?> *</label>
                    <input type="text" id="ml-boek-postcode" name="postcode" class="ml-input" autocomplete="postal-code" required>
                </div>
                <div>
                    <label class="ml-label"><?php echo esc_html( ml_t( 'boek.city', 'Plaats' ) ); ?> *</label>
                    <input type="text" id="ml-boek-city" name="city" class="ml-input" autocomplete="address-level2" required>
                </div>
            </div>
            <div class="ml-grid ml-grid--2 ml-mt-2" style="gap:16px;">
                <div>
                    <label class="ml-label"><?php echo esc_html( ml_t( 'boek.state', 'Provincie / regio' ) ); ?></label>
                    <input type="text" id="ml-boek-state" name="state" class="ml-input" autocomplete="address-level1">
                </div>
                <div>
                    <label class="ml-label"><?php echo esc_html( ml_t( 'boek.country', 'Land' ) ); ?> *</label>
                    <select id="ml-boek-country" name="country" class="ml-input" required>
                        <?php foreach ( $ml_countries as $code => $cname ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, 'BE' ); ?>><?php echo esc_html( $cname ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="ml-mt-2">
                <label class="ml-label"><?php echo esc_html( ml_t( 'boek.notes', 'Opmerkingen (optioneel)' ) ); ?></label>
                <textarea name="notes" rows="3" class="ml-input"></textarea>
            </div>

<?php if ( $ml_pay ) : ?>
            <div class="ml-card ml-card--lg ml-mt-3" style="background:#F4F4F5;">
                <div class="ml-row-between">
                    <div>
                        <p class="ml-card__title"><?php echo esc_html( ml_t( 'boek.total', 'Te betalen nu' ) ); ?></p>
                        <p class="ml-text-sm ml-text-muted"><?php echo esc_html( ml_t( 'boek.includes', 'Opname + virtuele tour + 1 jaar online beschikbaarheid + Matterport-activatiekost.' ) ); ?></p>
                    </div>
                    <p class="ml-h2"><?php echo esc_html( $fee ? $cur . ' ' . $fee : '€ —' ); ?></p>
                </div>
            </div>
<?php endif; ?>

            <button type="submit" class="ml-boek-submit" id="ml-boek-submit" disabled>
                <?php echo esc_html( $ml_cta ); ?>
            </button>
            <div id="ml-boek-error" class="ml-alert ml-alert--danger ml-mt-2" style="display:none;"></div>
        </form>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
<script>
(function () {
    var form   = document.getElementById('ml-boek-form');
    var submit = document.getElementById('ml-boek-submit');
    var errBox = document.getElementById('ml-boek-error');
    var ctaTxt = '<?php echo esc_js( $ml_cta ); ?>';

    /* ── International phone input (flags + dial codes) ── */
    var phoneEl = document.getElementById('ml-boek-phone');
    var iti = window.intlTelInput(phoneEl, {
        initialCountry: 'be',
        preferredCountries: ['be', 'nl', 'de', 'fr', 'lu', 'gb'],
        separateDialCode: true,
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js'
    });

    /* ── Address autocomplete (OpenStreetMap / Nominatim) ── */
    var streetEl = document.getElementById('ml-boek-street');
    var acBox    = document.getElementById('ml-boek-ac');
    var postEl   = document.getElementById('ml-boek-postcode');
    var cityEl   = document.getElementById('ml-boek-city');
    var stateEl  = document.getElementById('ml-boek-state');
    var countryEl= document.getElementById('ml-boek-country');
    var acTimer  = null, acCtrl = null;
    var lang     = '<?php echo esc_js( ml_current_lang() ); ?>';

    function closeAc() { acBox.classList.remove('is-open'); acBox.innerHTML = ''; }

    function pick(item) {
        var a = item.address || {};
        var road = a.road || a.pedestrian || a.cycleway || a.footway || a.path || '';
        var num  = a.house_number ? (' ' + a.house_number) : '';
        if (road) streetEl.value = road + num;
        if (a.postcode) postEl.value = a.postcode;
        var city = a.city || a.town || a.village || a.municipality || a.hamlet || a.county || '';
        if (city) cityEl.value = city;
        if (a.state || a.region || a.province) stateEl.value = a.state || a.region || a.province;
        if (a.country_code) {
            var cc = a.country_code.toUpperCase();
            if (countryEl.querySelector('option[value="' + cc + '"]')) countryEl.value = cc;
        }
        closeAc();
    }

    function runSearch(q) {
        if (acCtrl) acCtrl.abort();
        acCtrl = new AbortController();
        var url = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=6'
                + '&accept-language=' + encodeURIComponent(lang)
                + '&q=' + encodeURIComponent(q);
        fetch(url, { signal: acCtrl.signal, headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                acBox.innerHTML = '';
                if (!rows || !rows.length) { closeAc(); return; }
                rows.forEach(function (row) {
                    var el = document.createElement('div');
                    el.className = 'ml-ac__item';
                    el.textContent = row.display_name;
                    el.addEventListener('mousedown', function (e) { e.preventDefault(); pick(row); });
                    acBox.appendChild(el);
                });
                acBox.classList.add('is-open');
            })
            .catch(function () { /* aborted or network — ignore */ });
    }

    streetEl.addEventListener('input', function () {
        var q = streetEl.value.trim();
        if (acTimer) clearTimeout(acTimer);
        if (q.length < 3) { closeAc(); return; }
        acTimer = setTimeout(function () { runSearch(q); }, 350);
    });
    streetEl.addEventListener('blur', function () { setTimeout(closeAc, 150); });

    /* ── Submit ── */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        errBox.style.display = 'none';

        var data = new FormData(form);
        var payload = {};
        data.forEach(function (v, k) { payload[k] = v; });
        payload.phone = iti.getNumber() || payload.phone; // E.164 if valid

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
            errBox.textContent = (out && out.error) || '<?php echo esc_js( ml_t( 'common.error_generic', 'Er ging iets mis.' ) ); ?>';
            errBox.style.display = 'block';
            submit.disabled = false;
            submit.textContent = ctaTxt;
        }).catch(function () {
            errBox.textContent = 'Network error.';
            errBox.style.display = 'block';
            submit.disabled = false;
            submit.textContent = ctaTxt;
        });
    });
})();
</script>

<?php get_footer(); ?>
