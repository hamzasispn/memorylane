<?php defined( 'ABSPATH' ) || exit;
$subject = 'Welcome to Memory Lane — set your password';
$display = $user->display_name ?: $user->user_email;
?>
<h2 style="margin:0 0 16px;font-size:20px;">Welcome to Memory Lane, <?php echo esc_html( $display ); ?></h2>
<p>Your payment went through. Click the button below to set your password and access your customer area.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( $url ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Set password</a></p>
<p style="color:#71717A;font-size:13px;">Or paste this link in your browser:<br><span style="color:#3F3F46;word-break:break-all;"><?php echo esc_html( $url ); ?></span></p>
<p style="color:#71717A;font-size:13px;">This link is valid for 24 hours.</p>
