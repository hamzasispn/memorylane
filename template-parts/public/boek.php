<?php
/**
 * /boek — public booking + setup-fee payment in one flow.
 * Visitor picks date + time, fills name/email/phone/address, gets redirected
 * to Stripe. On payment success, webhook creates user + booking row.
 */
defined( 'ABSPATH' ) || exit;

$page_title = ml_t( 'boek.title', 'Boek jouw opname' );
include ML_PATH . 'template-parts/auth/_layout-head.php';

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
?>
<main class="ml-main" style="max-width:1100px;margin:48px auto;padding:0 24px;">

    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ml-text-sm">← <?php ml_e( 'common.back' ); ?></a>
    <h1 class="ml-h1 ml-mt-1"><?php echo esc_html( ml_t( 'boek.title', 'Boek jouw opname' ) ); ?></h1>
    <p class="ml-sub"><?php echo esc_html( $ml_subtitle ); ?></p>

    <form id="ml-boek-form" class="ml-mt-3">
        <input type="hidden" name="date" value="">
        <input type="hidden" name="time" value="">

        <h2 class="ml-h2 ml-mt-3"><?php echo esc_html( ml_t( 'boek.pick_slot', '1. Kies je opname-moment' ) ); ?></h2>
        <div class="ml-mt-2">
            <?php
                $picker_id = 'ml-boek-picker';
                include ML_PATH . 'template-parts/booking/picker.php';
            ?>
        </div>

        <h2 class="ml-h2 ml-mt-3"><?php echo esc_html( ml_t( 'boek.your_info', '2. Jouw gegevens' ) ); ?></h2>
        <div class="ml-grid ml-grid--2" style="gap:16px;">
            <div>
                <label class="ml-label"><?php echo esc_html( ml_t( 'boek.name', 'Naam' ) ); ?> *</label>
                <input type="text" name="name" class="ml-input" required>
            </div>
            <div>
                <label class="ml-label"><?php echo esc_html( ml_t( 'boek.email', 'E-mailadres' ) ); ?> *</label>
                <input type="email" name="email" class="ml-input" required>
            </div>
            <div>
                <label class="ml-label"><?php echo esc_html( ml_t( 'boek.phone', 'Telefoon' ) ); ?> *</label>
                <input type="tel" name="phone" class="ml-input" required>
            </div>
            <div>
                <label class="ml-label"><?php echo esc_html( ml_t( 'boek.address', 'Adres van de woning' ) ); ?> *</label>
                <input type="text" name="address" class="ml-input" required placeholder="Straat 12, 9000 Gent">
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

        <button type="submit" class="ml-btn ml-btn--primary ml-btn--block ml-mt-3" id="ml-boek-submit" disabled>
            <?php echo esc_html( $ml_cta ); ?>
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
                errBox.textContent = (out && out.error) || '<?php echo esc_js( ml_t( 'common.error_generic', 'Er ging iets mis.' ) ); ?>';
                errBox.style.display = 'block';
                submit.disabled = false;
                submit.textContent = '<?php echo esc_js( $ml_cta ); ?>';
            }).catch(function () {
                errBox.textContent = 'Network error.';
                errBox.style.display = 'block';
                submit.disabled = false;
                submit.textContent = '<?php echo esc_js( $ml_cta ); ?>';
            });
        });
    })();
    </script>

</main>
<?php wp_footer(); ?>
</body>
</html>
