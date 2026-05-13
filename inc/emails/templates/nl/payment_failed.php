<?php defined( 'ABSPATH' ) || exit;
$subject = 'Betaling mislukt — Memory Lane';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Betaling mislukt</h2>
<p>We konden je laatste betaling niet voltooien. We proberen het binnenkort automatisch opnieuw.</p>
<?php if ( ! empty( $invoice_url ) ) : ?>
<p style="margin:24px 0;"><a href="<?php echo esc_url( $invoice_url ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Betaling bekijken</a></p>
<?php endif; ?>
<p style="color:#71717A;font-size:13px;">Tip: ga via je klantenzone naar "Beheren in Stripe" om je betaalmethode bij te werken.</p>
