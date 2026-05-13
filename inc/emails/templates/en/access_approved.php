<?php defined( 'ABSPATH' ) || exit;
$subject = 'Your access is now active — Memory Lane';
$display = $user->display_name ?: $user->user_email;
?>
<h2 style="margin:0 0 16px;font-size:20px;">Your access is now active</h2>
<p>Hi <?php echo esc_html( $display ); ?>, good news — your virtual tour is ready and your customer area is fully active.</p>
<p>Your first year of Memory Lane is included. After 12 months a monthly subscription starts automatically so your property stays online. You can cancel anytime from your customer area.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Go to my customer area</a></p>
