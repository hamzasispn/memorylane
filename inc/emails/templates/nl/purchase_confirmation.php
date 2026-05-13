<?php defined( 'ABSPATH' ) || exit;
$subject  = 'Bedankt voor je aankoop — Memory Lane';
$amount   = isset( $amount_total ) ? number_format_i18n( $amount_total / 100, 2 ) : '';
$currency = isset( $currency ) ? strtoupper( $currency ) : 'EUR';
$sla      = isset( $sla_hours ) ? (int) $sla_hours : 8;
?>
<h2 style="margin:0 0 16px;font-size:20px;">Bedankt voor je aankoop</h2>
<p>We hebben je betaling ontvangen<?php if ( $amount ) echo ' (' . esc_html( $currency . ' ' . $amount ) . ')'; ?>. Dit bedrag dekt zowel de opname &amp; je eerste jaar als de Matterport-activatiekost.</p>
<p><strong>Wat gebeurt er nu?</strong> Ons team verwerkt je aanvraag binnen <strong><?php echo (int) $sla; ?> uur</strong>: we plannen je 3D-opname, maken je virtuele tour en activeren je klantenzone. Je krijgt een aparte e-mail zodra je tour beschikbaar is.</p>
<p>In de tussentijd kan je alvast inloggen, je profiel aanvullen en een opname-afspraak inplannen.</p>
<p>Je eerste jaar Memory Lane is inbegrepen. Na 12 maanden start automatisch een maandelijks abonnement (op elk moment opzegbaar).</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Naar mijn klantenzone</a></p>
