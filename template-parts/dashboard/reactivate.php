<?php
/**
 * /dashboard/reactivate — plan picker for customer-driven reactivation.
 */
defined( 'ABSPATH' ) || exit;

$user    = wp_get_current_user();
$plan    = ml_plan_get();
$has_ann = (bool) ml_stripe_annual_price_id() && $plan['annual_amount'] > 0;
$cur     = strtoupper( $plan['currency'] );
$monthly = ml_from_minor_units( (int) $plan['monthly_amount'] );
$annual  = ml_from_minor_units( (int) $plan['annual_amount'] );
$fee     = ml_from_minor_units( (int) $plan['reactivation_amount'] );

$eligibility = ml_reactivation_check_eligibility( $user->ID );
$open        = ml_reactivation_open_for_user( $user->ID );

// Compute annual saving % vs 12 × monthly.
$savings_pct = 0;
if ( $has_ann && $plan['monthly_amount'] > 0 ) {
    $monthly_year_total = $plan['monthly_amount'] * 12;
    if ( $plan['annual_amount'] < $monthly_year_total ) {
        $savings_pct = (int) round( ( 1 - ( $plan['annual_amount'] / $monthly_year_total ) ) * 100 );
    }
}
?>
<div>
    <a href="<?php echo esc_url( home_url( '/dashboard/subscription' ) ); ?>" class="ml-text-sm">← <?php ml_e( 'common.back' ); ?></a>
    <h1 class="ml-h1 ml-mt-1"><?php echo esc_html( ml_t( 'reactivate.title', 'Heractiveer mijn tour' ) ); ?></h1>
    <p class="ml-sub"><?php echo esc_html( ml_t( 'reactivate.subtitle', 'Kies een formule om je tour opnieuw online te brengen. Activatie duurt tot 8 uur.' ) ); ?></p>

    <?php if ( $open ) : ?>
        <div class="ml-alert ml-alert--warning ml-mt-2">
            <strong><?php echo esc_html( ml_t( 'reactivate.already_pending.title', 'Reactivatie loopt' ) ); ?>:</strong>
            <?php echo esc_html( ml_t( 'reactivate.already_pending.body', 'Er staat al een reactivatie open. Ons team activeert je tour binnen 8 uur.' ) ); ?>
        </div>
        <p><a class="ml-btn ml-btn--secondary" href="<?php echo esc_url( home_url( '/dashboard/subscription' ) ); ?>"><?php ml_e( 'common.back' ); ?></a></p>
        <?php return; ?>
    <?php endif; ?>

    <?php if ( ! $eligibility['ok'] ) : ?>
        <div class="ml-alert ml-alert--danger ml-mt-2">
            <?php
            $err_msgs = array(
                'no_stripe_customer'         => ml_t( 'reactivate.err.no_customer',  'Je account is niet verbonden met onze betaalprovider. Neem contact op.' ),
                'no_prior_subscription'      => ml_t( 'reactivate.err.no_prior',     'Geen eerder abonnement gevonden om te heractiveren.' ),
                'subscription_still_active'  => ml_t( 'reactivate.err.still_active', 'Je abonnement is nog actief, je hoeft niet te heractiveren.' ),
                'stripe_not_configured'      => ml_t( 'reactivate.err.config',       'Betalingen zijn nog niet geconfigureerd. Neem contact op.' ),
                'reactivation_fee_not_priced'=> ml_t( 'reactivate.err.no_fee',       'Reactivatieprijs is nog niet ingesteld.' ),
            );
            $msg = $err_msgs[ $eligibility['error'] ] ?? ml_t( 'common.error_generic' );
            echo esc_html( $msg );
            ?>
        </div>
    <?php else : ?>

        <div class="ml-grid ml-grid--2 ml-mt-3" style="gap:16px;">

            <!-- Monthly card -->
            <div class="ml-card ml-card--lg">
                <p class="ml-card__title"><?php echo esc_html( ml_t( 'reactivate.plan.monthly', 'Maandelijks' ) ); ?></p>
                <p class="ml-h2 ml-mt-1"><?php echo esc_html( $cur . ' ' . $monthly ); ?><span class="ml-text-muted" style="font-size:14px;font-weight:400;"> / <?php echo esc_html( ml_t( 'reactivate.unit.month', 'maand' ) ); ?></span></p>
                <p class="ml-text-sm ml-text-muted"><?php echo esc_html( ml_t( 'reactivate.cancel_anytime', 'Op elk moment opzegbaar.' ) ); ?></p>
                <hr class="ml-divider">
                <p class="ml-text-sm">
                    <?php echo esc_html( sprintf( ml_t( 'reactivate.first_charge', 'Eerste afschrijving: %1$s %2$s reactivatiekost + %1$s %3$s eerste maand.' ), $cur, $fee, $monthly ) ); ?>
                </p>
                <form method="post" action="" data-ml-reactivate>
                    <input type="hidden" name="plan" value="monthly">
                    <button class="ml-btn ml-btn--primary ml-btn--block ml-mt-2" type="submit">
                        <?php echo esc_html( ml_t( 'reactivate.cta.monthly', 'Heractiveer maandelijks' ) ); ?>
                    </button>
                </form>
            </div>

            <!-- Annual card -->
            <?php if ( $has_ann ) : ?>
            <div class="ml-card ml-card--lg" style="border-color:#10B981;">
                <p class="ml-card__title">
                    <?php echo esc_html( ml_t( 'reactivate.plan.annual', 'Jaarlijks vooruitbetaald' ) ); ?>
                    <?php if ( $savings_pct > 0 ) : ?>
                        <span class="ml-pill ml-pill--success" style="margin-left:8px;font-size:11px;">−<?php echo (int) $savings_pct; ?>%</span>
                    <?php endif; ?>
                </p>
                <p class="ml-h2 ml-mt-1"><?php echo esc_html( $cur . ' ' . $annual ); ?><span class="ml-text-muted" style="font-size:14px;font-weight:400;"> / <?php echo esc_html( ml_t( 'reactivate.unit.year', 'jaar' ) ); ?></span></p>
                <p class="ml-text-sm ml-text-muted">
                    <?php echo esc_html( ml_t( 'reactivate.annual.note', 'Eén jaar online, daarna automatisch verlengen.' ) ); ?>
                </p>
                <hr class="ml-divider">
                <p class="ml-text-sm">
                    <?php echo esc_html( sprintf( ml_t( 'reactivate.first_charge_annual', 'Eerste afschrijving: %1$s %2$s reactivatiekost + %1$s %3$s jaarabonnement.' ), $cur, $fee, $annual ) ); ?>
                </p>
                <form method="post" action="" data-ml-reactivate>
                    <input type="hidden" name="plan" value="annual">
                    <button class="ml-btn ml-btn--primary ml-btn--block ml-mt-2" type="submit">
                        <?php echo esc_html( ml_t( 'reactivate.cta.annual', 'Heractiveer jaarlijks' ) ); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

        </div>

        <p class="ml-text-sm ml-text-muted ml-mt-3">
            <?php echo esc_html( ml_t( 'reactivate.sla_note', 'Heractivatie gebeurt manueel door ons team. Je tour is binnen 8 uur opnieuw beschikbaar.' ) ); ?>
        </p>

        <script>
            (function () {
                var forms = document.querySelectorAll('[data-ml-reactivate]');
                forms.forEach(function (form) {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        var btn = form.querySelector('button[type=submit]');
                        var plan = form.querySelector('[name=plan]').value;
                        if (btn) { btn.disabled = true; btn.textContent = <?php echo wp_json_encode( ml_t( 'common.loading', 'Laden...' ) ); ?>; }
                        fetch('<?php echo esc_url_raw( rest_url( 'memorylane/v1/reactivate' ) ); ?>', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
                            },
                            body: JSON.stringify({ plan: plan })
                        }).then(function (r) { return r.json(); }).then(function (data) {
                            if (data && data.ok && data.url) {
                                window.location = data.url;
                            } else {
                                if (btn) { btn.disabled = false; btn.textContent = form.dataset.originalLabel || 'Heractiveer'; }
                                alert((data && data.error) || 'Something went wrong.');
                            }
                        }).catch(function () {
                            if (btn) { btn.disabled = false; }
                            alert('Network error. Please try again.');
                        });
                    });
                });
            })();
        </script>
    <?php endif; ?>
</div>
