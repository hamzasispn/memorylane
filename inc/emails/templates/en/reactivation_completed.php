<?php defined( 'ABSPATH' ) || exit;
$subject = 'Your tour is live again — Memory Lane';
$display = $user->display_name ?: $user->user_email;
?>
<h2 style="margin:0 0 16px;font-size:20px;">Your tour is back online</h2>
<p>Hi <?php echo esc_html( $display ); ?>, your virtual tour is active again and visible from your customer area.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard/tours' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">View my tour</a></p>
