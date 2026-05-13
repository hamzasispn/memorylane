<?php defined( 'ABSPATH' ) || exit;
$subject = 'Payment failed — Memory Lane';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Payment failed</h2>
<p>We could not complete your most recent payment. We will retry automatically soon.</p>
<?php if ( ! empty( $invoice_url ) ) : ?>
<p style="margin:24px 0;"><a href="<?php echo esc_url( $invoice_url ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">View invoice</a></p>
<?php endif; ?>
<p style="color:#71717A;font-size:13px;">Tip: go to "Manage in Stripe" from your customer area to update your payment method.</p>
