<?php
defined( 'ABSPATH' ) || exit;
$user       = wp_get_current_user();
$row        = ml_get_subscription_row( $user->ID );
$is_pending     = ml_user_is_pending_approval( $user->ID );
$pending_react  = ml_reactivation_open_for_user( $user->ID );
$is_cancelled   = $row && in_array( $row->status, array( 'cancelled', 'canceled', 'incomplete_expired', 'unpaid' ), true );
$is_react_state = $row && $row->status === ML_SUB_STATUS_PENDING_REACTIVATION;
$paid_at    = get_user_meta( $user->ID, ML_META_SETUP_PAID_AT, true );
$paid_amt   = (int) get_user_meta( $user->ID, ML_META_SETUP_AMOUNT, true );
$paid_cur   = strtoupper( (string) get_user_meta( $user->ID, ML_META_SETUP_CURRENCY, true ) );

// Invoices + billing details (V2-4).
$invoice_page = max( 1, (int) ( $_GET['inv_page'] ?? 1 ) );
$inv_data     = ml_billing_list_invoices( $user->ID, 12, $invoice_page );
$billing      = ml_billing_get_details( $user->ID );
?>
<div>
    <h1 class="ml-h1"><?php ml_e( 'sub.title' ); ?></h1>
    <p class="ml-sub"></p>

    <?php if ( $is_pending ) : ?>
        <div class="ml-card ml-card--lg" style="background:#FEF3C7;border-color:#FCD34D;">
            <div class="ml-row-between">
                <div>
                    <p class="ml-card__title" style="color:#92400E;"><?php echo esc_html( ml_t( 'sub.pending.title', 'In afwachting van goedkeuring' ) ); ?></p>
                    <p class="ml-card__value" style="color:#78350F;"><span class="ml-pill ml-pill--warning"><?php echo esc_html( ml_t( 'overview.pending.pill', 'In afwachting' ) ); ?></span></p>
                </div>
                <?php if ( $paid_amt ) : ?>
                    <div style="text-align:right;">
                        <p class="ml-text-sm ml-text-muted" style="color:#78350F;"><?php echo esc_html( ml_t( 'sub.pending.paid', 'Betaling ontvangen' ) ); ?></p>
                        <p class="ml-h3" style="color:#78350F;"><?php echo esc_html( $paid_cur . ' ' . number_format( $paid_amt / 100, 2 ) ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <hr class="ml-divider">
            <p style="color:#78350F;margin:0;"><?php echo esc_html( sprintf( ml_t( 'sub.pending.body', 'Je toegang wordt geactiveerd binnen %d uur. Het maandabonnement start automatisch na 12 maanden vanaf de activering en is dan op elk moment opzegbaar.' ), ML_APPROVAL_SLA_HOURS ) ); ?></p>
        </div>
    <?php elseif ( $pending_react || $is_react_state ) : ?>
        <div class="ml-card ml-card--lg" style="background:#FEF3C7;border-color:#FCD34D;">
            <p class="ml-card__title" style="color:#92400E;"><?php echo esc_html( ml_t( 'reactivate.processing.title', 'Reactivatie wordt verwerkt' ) ); ?></p>
            <p style="color:#78350F;margin-top:8px;"><?php echo esc_html( sprintf( ml_t( 'reactivate.processing.body', 'We hebben je betaling ontvangen. Je tour is binnen %d uur opnieuw beschikbaar.' ), ML_REACTIVATION_SLA_HOURS ) ); ?></p>
        </div>
    <?php elseif ( $is_cancelled ) : ?>
        <div class="ml-card ml-card--lg" style="background:#FEE2E2;border-color:#FCA5A5;">
            <p class="ml-card__title" style="color:#B91C1C;"><?php echo esc_html( ml_t( 'sub.archived.title', 'Tour gearchiveerd' ) ); ?></p>
            <p style="color:#7F1D1D;margin-top:8px;"><?php echo esc_html( ml_t( 'sub.archived.body', 'Je abonnement is beëindigd en je tour is gearchiveerd. Je kan op elk moment opnieuw activeren tegen de activatiekost.' ) ); ?></p>
            <hr class="ml-divider">
            <a class="ml-btn ml-btn--primary" href="<?php echo esc_url( home_url( '/dashboard/reactivate' ) ); ?>"><?php echo esc_html( ml_t( 'sub.archived.cta', 'Heractiveer mijn tour' ) ); ?></a>
        </div>
    <?php elseif ( ! $row ) : ?>
        <div class="ml-empty">
            <div class="ml-empty__title"><?php echo esc_html__( 'No subscription yet', 'memorylane' ); ?></div>
            <p class="ml-text-sm"><?php echo esc_html__( 'You do not have an active subscription on this account.', 'memorylane' ); ?></p>
        </div>
    <?php else :
        $phase = ml_subscription_phase( $row );
        $label = ml_subscription_status_label( $row );
        $pill  = ml_subscription_status_pill_class( $row );
    ?>
        <div class="ml-card ml-card--lg">
            <div class="ml-row-between">
                <div>
                    <p class="ml-card__title"><?php ml_e( $phase === 'year_one' ? 'sub.phase.year_one' : 'sub.phase.monthly' ); ?></p>
                    <p class="ml-card__value"><span class="ml-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( $label ); ?></span></p>
                </div>
                <div style="text-align: right;">
                    <p class="ml-text-sm ml-text-muted"><?php ml_e( 'sub.next_billing' ); ?></p>
                    <p class="ml-h3"><?php echo esc_html( ml_format_date( $row->current_period_end ) ); ?></p>
                </div>
            </div>

            <?php if ( $row->cancel_at_period_end ) : ?>
                <div class="ml-alert ml-alert--warning ml-mt-2">
                    <?php echo esc_html( str_replace( '{date}', ml_format_date( $row->current_period_end ), ml_t( 'sub.cancelled_msg' ) ) ); ?>
                </div>
            <?php endif; ?>

            <hr class="ml-divider">

            <div class="ml-flex ml-gap-2" style="flex-wrap: wrap;">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
                    <?php wp_nonce_field( 'ml_portal' ); ?>
                    <input type="hidden" name="action" value="ml_portal">
                    <button type="submit" class="ml-btn ml-btn--secondary"><?php ml_e( 'sub.manage_in_stripe' ); ?></button>
                </form>

                <?php if ( ! $row->cancel_at_period_end && in_array( $row->status, array( 'active', 'trialing', 'past_due' ), true ) ) : ?>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;"
                          onsubmit="return confirm('<?php echo esc_js( ml_t( 'sub.cancel_confirm' ) ); ?>');">
                        <?php wp_nonce_field( 'ml_sub_cancel' ); ?>
                        <input type="hidden" name="action" value="ml_sub_cancel">
                        <button type="submit" class="ml-btn ml-btn--danger"><?php ml_e( 'sub.cancel' ); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Invoices (V2-4) -->
    <h2 class="ml-h2 ml-mt-4"><?php echo esc_html( ml_t( 'billing.invoices.title', 'Facturen' ) ); ?></h2>
    <?php if ( empty( $inv_data['invoices'] ) ) : ?>
        <div class="ml-empty">
            <div class="ml-empty__title"><?php echo esc_html( ml_t( 'billing.invoices.empty', 'Nog geen facturen.' ) ); ?></div>
        </div>
    <?php else : ?>
        <div class="ml-card">
            <div class="ml-table-wrap">
                <table class="ml-table">
                    <thead><tr>
                        <th><?php echo esc_html( ml_t( 'billing.invoices.col.date', 'Datum' ) ); ?></th>
                        <th><?php echo esc_html( ml_t( 'billing.invoices.col.number', 'Nummer' ) ); ?></th>
                        <th><?php echo esc_html( ml_t( 'billing.invoices.col.amount', 'Bedrag' ) ); ?></th>
                        <th><?php echo esc_html( ml_t( 'billing.invoices.col.status', 'Status' ) ); ?></th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ( $inv_data['invoices'] as $inv ) :
                        $pill = array(
                            'paid'          => 'ml-pill--success',
                            'open'          => 'ml-pill--warning',
                            'uncollectible' => 'ml-pill--danger',
                            'void'          => 'ml-pill--neutral',
                            'draft'         => 'ml-pill--neutral',
                        )[ $inv['status'] ] ?? 'ml-pill--neutral';
                        $amount = $inv['amount_paid'] > 0 ? $inv['amount_paid'] : $inv['amount_due'];
                    ?>
                        <tr>
                            <td><?php echo esc_html( ml_format_date( gmdate( 'Y-m-d H:i:s', $inv['created'] ) ) ); ?></td>
                            <td><?php echo esc_html( $inv['number'] ?: $inv['id'] ); ?></td>
                            <td><?php echo esc_html( $inv['currency'] . ' ' . number_format( $amount / 100, 2 ) ); ?></td>
                            <td><span class="ml-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( ml_t( 'billing.invoices.status.' . $inv['status'], $inv['status'] ) ); ?></span></td>
                            <td style="text-align:right;">
                                <?php if ( $inv['invoice_pdf'] ) : ?>
                                    <a class="ml-btn ml-btn--ghost ml-text-sm" href="<?php echo esc_url( $inv['invoice_pdf'] ); ?>" target="_blank" rel="noopener">PDF</a>
                                <?php endif; ?>
                                <?php if ( $inv['hosted_invoice_url'] ) : ?>
                                    <a class="ml-btn ml-btn--ghost ml-text-sm" href="<?php echo esc_url( $inv['hosted_invoice_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( ml_t( 'billing.invoices.view', 'Bekijken' ) ); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $total_pages = max( 1, (int) ceil( $inv_data['total_estimated'] / max( 1, $inv_data['limit'] ) ) );
            if ( $total_pages > 1 ) :
                $base = remove_query_arg( array( 'inv_page' ), home_url( $_SERVER['REQUEST_URI'] ?? '/dashboard/subscription' ) );
            ?>
                <div class="ml-flex ml-gap-2 ml-mt-2" style="justify-content:flex-end;align-items:center;">
                    <?php if ( $invoice_page > 1 ) : ?>
                        <a class="ml-btn ml-btn--ghost ml-text-sm" href="<?php echo esc_url( add_query_arg( 'inv_page', $invoice_page - 1, $base ) ); ?>">← <?php echo esc_html( ml_t( 'common.prev', 'Vorige' ) ); ?></a>
                    <?php endif; ?>
                    <span class="ml-text-sm ml-text-muted"><?php echo esc_html( sprintf( ml_t( 'common.page_x_of_y', 'Pagina %1$d van %2$d' ), $invoice_page, $total_pages ) ); ?></span>
                    <?php if ( $inv_data['has_more'] ) : ?>
                        <a class="ml-btn ml-btn--ghost ml-text-sm" href="<?php echo esc_url( add_query_arg( 'inv_page', $invoice_page + 1, $base ) ); ?>"><?php echo esc_html( ml_t( 'common.next', 'Volgende' ) ); ?> →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Billing details (V2-4) -->
    <h2 class="ml-h2 ml-mt-4"><?php echo esc_html( ml_t( 'billing.details.title', 'Facturatiegegevens' ) ); ?></h2>
    <div class="ml-card">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'ml_billing_save' ); ?>
            <input type="hidden" name="action" value="ml_billing_save">

            <p class="ml-text-sm ml-text-muted ml-mb-2"><?php echo esc_html( ml_t( 'billing.details.help', 'Optioneel. Vul in voor bedrijfsfacturen of als je een BTW-nummer wil vermelden.' ) ); ?></p>

            <div class="ml-grid ml-grid--2" style="gap:16px;">
                <div>
                    <label class="ml-label"><?php echo esc_html( ml_t( 'billing.field.company', 'Bedrijfsnaam' ) ); ?></label>
                    <input type="text" name="company" class="ml-input" value="<?php echo esc_attr( $billing['company'] ); ?>">
                </div>
                <div>
                    <label class="ml-label"><?php echo esc_html( ml_t( 'billing.field.vat', 'BTW-nummer' ) ); ?></label>
                    <input type="text" name="vat" class="ml-input" value="<?php echo esc_attr( $billing['vat'] ); ?>" placeholder="BE0123.456.789">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="ml-label"><?php echo esc_html( ml_t( 'billing.field.line1', 'Adres' ) ); ?></label>
                    <input type="text" name="line1" class="ml-input" value="<?php echo esc_attr( $billing['line1'] ); ?>">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="ml-label"><?php echo esc_html( ml_t( 'billing.field.line2', 'Adres (2e regel, optioneel)' ) ); ?></label>
                    <input type="text" name="line2" class="ml-input" value="<?php echo esc_attr( $billing['line2'] ); ?>">
                </div>
                <div>
                    <label class="ml-label"><?php echo esc_html( ml_t( 'billing.field.postal', 'Postcode' ) ); ?></label>
                    <input type="text" name="postal" class="ml-input" value="<?php echo esc_attr( $billing['postal'] ); ?>">
                </div>
                <div>
                    <label class="ml-label"><?php echo esc_html( ml_t( 'billing.field.city', 'Stad' ) ); ?></label>
                    <input type="text" name="city" class="ml-input" value="<?php echo esc_attr( $billing['city'] ); ?>">
                </div>
                <div>
                    <label class="ml-label"><?php echo esc_html( ml_t( 'billing.field.country', 'Land (2 letters)' ) ); ?></label>
                    <input type="text" name="country" class="ml-input" value="<?php echo esc_attr( $billing['country'] ); ?>" maxlength="2" placeholder="BE">
                </div>
            </div>

            <div class="ml-mt-3">
                <button type="submit" class="ml-btn ml-btn--primary"><?php echo esc_html( ml_t( 'common.save', 'Opslaan' ) ); ?></button>
                <span class="ml-text-sm ml-text-muted" style="margin-left:12px;"><?php echo esc_html( ml_t( 'billing.card_via_portal', 'Wijzig je kaartgegevens via "Beheren in Stripe" hierboven.' ) ); ?></span>
            </div>
        </form>
    </div>
</div>
