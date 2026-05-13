<?php defined( 'ABSPATH' ) || exit;
$subject = '[Memory Lane] Nieuwe aankoop';
$amount = isset( $amount ) ? number_format_i18n( $amount / 100, 2 ) : '';
$cur = isset( $currency ) ? strtoupper( $currency ) : 'EUR';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Nieuwe aankoop</h2>
<p><strong><?php echo esc_html( $user->display_name ?: $user->user_email ); ?></strong> (<?php echo esc_html( $user->user_email ); ?>) heeft Memory Lane gekocht.</p>
<p>Bedrag: <strong><?php echo esc_html( $cur . ' ' . $amount ); ?></strong></p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=memorylane-customers' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Klant bekijken</a></p>
