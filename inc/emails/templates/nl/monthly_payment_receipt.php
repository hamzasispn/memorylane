<?php defined( 'ABSPATH' ) || exit;
$subject = 'Betaling ontvangen — Memory Lane';
$amount = isset( $amount_paid ) ? number_format_i18n( $amount_paid / 100, 2 ) : '';
$cur = isset( $currency ) ? strtoupper( $currency ) : 'EUR';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Betaling ontvangen</h2>
<p>We hebben je maandelijkse betaling van <strong><?php echo esc_html( $cur . ' ' . $amount ); ?></strong> ontvangen. Bedankt!</p>
<?php if ( ! empty( $invoice_url ) ) : ?>
<p style="margin:24px 0;"><a href="<?php echo esc_url( $invoice_url ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Factuur bekijken</a></p>
<?php endif; ?>
