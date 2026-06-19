<?php
/**
 * /boek — public booking, wrapped in the marketing site theme.
 * Visitor picks date + time, fills name/email/phone + structured address.
 * When payment is required → Stripe Checkout; otherwise the booking is created
 * directly (see inc/booking/boek-checkout.php).
 *
 * Styles + behaviour live in the Vite-built `boek` bundle (assets/src/scss/boek.scss
 * and assets/src/js/boek.js), enqueued for this route only by ml_vite_enqueue()
 * in functions.php. Server values reach the script via window.mlBoek
 * (wp_localize_script). No inline <style>/<script>/<link> here.
 */
defined( 'ABSPATH' ) || exit;

$ml_cta      = ml_t( 'boek.cta_no_pay', 'Bevestig je boeking' );
$ml_subtitle = ml_t( 'boek.subtitle_no_pay', 'Kies een datum en vul je gegevens in. We nemen contact op om je opname te bevestigen.' );
$ml_countries = ml_iso_countries();

get_header();
?>

<main class="boek-page">
    <div class="boek-wrap">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="boek-back">← <?php ml_e( 'common.back' ); ?></a>
        <h1 class="boek-title"><?php echo esc_html( ml_t( 'boek.title', 'Boek jouw opname' ) ); ?></h1>
        <p class="boek-sub"><?php echo esc_html( $ml_subtitle ); ?></p>

        <form id="ml-boek-form" class="boek-card">
            <input type="hidden" name="date" value="">
            <input type="hidden" name="time" value="">

            <h2 class="boek-h2"><?php echo esc_html( ml_t( 'boek.pick_slot', '1. Kies je opname-moment' ) ); ?></h2>
            <div class="mt-4">
                <?php
                    $picker_id = 'ml-boek-picker';
                    include ML_PATH . 'template-parts/booking/picker.php';
                ?>
            </div>

            <h2 class="boek-h2"><?php echo esc_html( ml_t( 'boek.your_info', '2. Jouw gegevens' ) ); ?></h2>
            <div class="boek-grid-2">
                <div>
                    <label class="boek-label"><?php echo esc_html( ml_t( 'boek.name', 'Naam' ) ); ?> *</label>
                    <input type="text" name="name" class="boek-input" autocomplete="name" required>
                </div>
                <div>
                    <label class="boek-label"><?php echo esc_html( ml_t( 'boek.email', 'E-mailadres' ) ); ?> *</label>
                    <input type="email" name="email" class="boek-input" autocomplete="email" required>
                </div>
            </div>
            <div class="mt-4">
                <label class="boek-label"><?php echo esc_html( ml_t( 'boek.phone', 'Telefoon' ) ); ?> *</label>
                <input type="tel" id="ml-boek-phone" name="phone" class="boek-input" autocomplete="tel" required>
            </div>

            <h2 class="boek-h2"><?php echo esc_html( ml_t( 'boek.address_heading', '3. Adres van de woning' ) ); ?></h2>
            <div class="boek-ac">
                <label class="boek-label"><?php echo esc_html( ml_t( 'boek.street', 'Straat en huisnummer' ) ); ?> *</label>
                <input type="text" id="ml-boek-street" name="street" class="boek-input" autocomplete="off"
                       placeholder="<?php echo esc_attr( ml_t( 'boek.street_ph', 'Begin te typen voor suggesties…' ) ); ?>" required>
                <div class="boek-ac__list" id="ml-boek-ac"></div>
            </div>
            <div class="boek-grid-2">
                <div>
                    <label class="boek-label"><?php echo esc_html( ml_t( 'boek.postcode', 'Postcode' ) ); ?> *</label>
                    <input type="text" id="ml-boek-postcode" name="postcode" class="boek-input" autocomplete="postal-code" required>
                </div>
                <div>
                    <label class="boek-label"><?php echo esc_html( ml_t( 'boek.city', 'Plaats' ) ); ?> *</label>
                    <input type="text" id="ml-boek-city" name="city" class="boek-input" autocomplete="address-level2" required>
                </div>
            </div>
            <div class="boek-grid-2">
                <div>
                    <label class="boek-label"><?php echo esc_html( ml_t( 'boek.state', 'Provincie / regio' ) ); ?></label>
                    <input type="text" id="ml-boek-state" name="state" class="boek-input" autocomplete="address-level1">
                </div>
                <div>
                    <label class="boek-label"><?php echo esc_html( ml_t( 'boek.country', 'Land' ) ); ?> *</label>
                    <select id="ml-boek-country" name="country" class="boek-input" required>
                        <?php foreach ( $ml_countries as $code => $cname ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, 'BE' ); ?>><?php echo esc_html( $cname ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <label class="boek-label"><?php echo esc_html( ml_t( 'boek.notes', 'Opmerkingen (optioneel)' ) ); ?></label>
                <textarea name="notes" rows="3" class="boek-input"></textarea>
            </div>

            <button type="submit" class="boek-submit" id="ml-boek-submit" disabled>
                <?php echo esc_html( $ml_cta ); ?>
            </button>
            <div id="ml-boek-error" class="boek-error" hidden></div>
        </form>
    </div>
</main>

<?php get_footer(); ?>
