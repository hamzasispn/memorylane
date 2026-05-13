<?php defined( 'ABSPATH' ) || exit;
$subject = 'Reactivatie ontvangen — Memory Lane';
$display = $user->display_name ?: $user->user_email;
$sla     = isset( $sla_hours ) ? (int) $sla_hours : 8;
$plan_lbl= ( $plan ?? 'monthly' ) === 'annual' ? 'jaarlijks' : 'maandelijks';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Bedankt voor je reactivatie</h2>
<p>Hallo <?php echo esc_html( $display ); ?>, we hebben je betaling ontvangen voor het <?php echo esc_html( $plan_lbl ); ?> abonnement.</p>
<p>Ons team zet je tour binnen <strong><?php echo (int) $sla; ?> uur</strong> opnieuw online. Je krijgt een aparte e-mail zodra je tour weer beschikbaar is.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Naar mijn klantenzone</a></p>
