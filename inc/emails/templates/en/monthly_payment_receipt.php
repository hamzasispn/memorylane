<?php defined( 'ABSPATH' ) || exit;
$subject = 'Payment received — Memory Lane';
$amount = isset( $amount_paid ) ? number_format_i18n( $amount_paid / 100, 2 ) : '';
$cur = isset( $currency ) ? strtoupper( $currency ) : 'EUR';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Payment received</h2>
<p>We received your monthly payment of <strong><?php echo esc_html( $cur . ' ' . $amount ); ?></strong>. Thank you!</p>
<?php if ( ! empty( $invoice_url ) ) : ?>
<p style="margin:24px 0;"><a href="<?php echo esc_url( $invoice_url ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">View invoice</a></p>
<?php endif; ?>
