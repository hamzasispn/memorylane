<?php defined( 'ABSPATH' ) || exit;
$subject = 'Thanks for your purchase — Memory Lane';
$amount  = isset( $amount_total ) ? number_format_i18n( $amount_total / 100, 2 ) : '';
$currency = isset( $currency ) ? strtoupper( $currency ) : 'EUR';
$year_end = isset( $year_one_end_date ) ? wp_date( 'F j, Y', (int) $year_one_end_date ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Thanks for your purchase</h2>
<p>We received your payment<?php if ( $amount ) echo ' (' . esc_html( $currency . ' ' . $amount ) . ')'; ?>.</p>
<p>Your Memory Lane access is active until <strong><?php echo esc_html( $year_end ); ?></strong>. After that, your subscription automatically switches to monthly billing. You can cancel anytime.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Go to my customer area</a></p>
