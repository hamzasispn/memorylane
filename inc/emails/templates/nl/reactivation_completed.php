<?php defined( 'ABSPATH' ) || exit;
$subject = 'Je tour is opnieuw actief — Memory Lane';
$display = $user->display_name ?: $user->user_email;
?>
<h2 style="margin:0 0 16px;font-size:20px;">Je tour is opnieuw online</h2>
<p>Hallo <?php echo esc_html( $display ); ?>, je virtuele tour is opnieuw actief en zichtbaar in je klantenzone.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard/tours' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Bekijk mijn tour</a></p>
