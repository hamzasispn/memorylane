<?php defined( 'ABSPATH' ) || exit;
$subject = 'Reactivation received — Memory Lane';
$display = $user->display_name ?: $user->user_email;
$sla     = isset( $sla_hours ) ? (int) $sla_hours : 8;
$plan_lbl= ( $plan ?? 'monthly' ) === 'annual' ? 'annual' : 'monthly';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Thanks for reactivating</h2>
<p>Hi <?php echo esc_html( $display ); ?>, we received your payment for the <?php echo esc_html( $plan_lbl ); ?> plan.</p>
<p>Our team will bring your tour back online within <strong><?php echo (int) $sla; ?> hours</strong>. You will get another email as soon as your tour is live again.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Go to my customer area</a></p>
