<?php defined( 'ABSPATH' ) || exit;
$subject = 'Je eerste jaar loopt af — Memory Lane';
$end = isset( $period_end ) ? wp_date( 'j F Y', (int) $period_end ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Je eerste jaar loopt binnenkort af</h2>
<p>Op <strong><?php echo esc_html( $end ); ?></strong> eindigt je eerste jaar Memory Lane. Daarna start automatisch een maandelijks abonnement zodat je woning online beschikbaar blijft.</p>
<p>Je kan op elk moment opzeggen via je klantenzone.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard/subscription' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Abonnement beheren</a></p>
