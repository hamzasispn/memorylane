<?php defined( 'ABSPATH' ) || exit;
$subject = 'Your first year is ending soon — Memory Lane';
$end = isset( $period_end ) ? wp_date( 'F j, Y', (int) $period_end ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Your first year is ending soon</h2>
<p>On <strong><?php echo esc_html( $end ); ?></strong>, your first year of Memory Lane ends. A monthly subscription will start automatically so your property stays online.</p>
<p>You can cancel anytime from your customer area.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard/subscription' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Manage subscription</a></p>
