<?php defined( 'ABSPATH' ) || exit;
$subject  = 'Thanks for your purchase — Memory Lane';
$amount   = isset( $amount_total ) ? number_format_i18n( $amount_total / 100, 2 ) : '';
$currency = isset( $currency ) ? strtoupper( $currency ) : 'EUR';
$sla      = isset( $sla_hours ) ? (int) $sla_hours : 8;
?>
<h2 style="margin:0 0 16px;font-size:20px;">Thanks for your purchase</h2>
<p>We received your payment<?php if ( $amount ) echo ' (' . esc_html( $currency . ' ' . $amount ) . ')'; ?>.</p>
<p><strong>What happens next?</strong> Our team will process your request within <strong><?php echo (int) $sla; ?> hours</strong>: we schedule your 3D scan, create your virtual tour and activate your customer area. You will receive a separate email as soon as your tour is ready.</p>
<p>Meanwhile you can already log in, complete your profile and book a scan appointment.</p>
<p>Your first year of Memory Lane is included. After 12 months a monthly subscription starts automatically (cancel anytime).</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Go to my customer area</a></p>
