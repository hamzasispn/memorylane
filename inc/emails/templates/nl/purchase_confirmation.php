<?php defined( 'ABSPATH' ) || exit;
$subject = 'Bedankt voor je aankoop — Memory Lane';
$amount  = isset( $amount_total ) ? number_format_i18n( $amount_total / 100, 2 ) : '';
$currency = isset( $currency ) ? strtoupper( $currency ) : 'EUR';
$year_end = isset( $year_one_end_date ) ? wp_date( 'j F Y', (int) $year_one_end_date ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Bedankt voor je aankoop</h2>
<p>We hebben je betaling ontvangen<?php if ( $amount ) echo ' (' . esc_html( $currency . ' ' . $amount ) . ')'; ?>.</p>
<p>Je toegang tot Memory Lane is actief tot <strong><?php echo esc_html( $year_end ); ?></strong>. Daarna gaat je abonnement automatisch over naar maandelijkse facturatie. Je kan op elk moment opzeggen.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Naar mijn klantenzone</a></p>
