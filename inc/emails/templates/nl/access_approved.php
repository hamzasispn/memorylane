<?php defined( 'ABSPATH' ) || exit;
$subject = 'Je toegang is geactiveerd — Memory Lane';
$display = $user->display_name ?: $user->user_email;
?>
<h2 style="margin:0 0 16px;font-size:20px;">Je toegang is geactiveerd</h2>
<p>Hallo <?php echo esc_html( $display ); ?>, goed nieuws — je virtuele tour is klaar en je klantenzone is volledig actief.</p>
<p>Je eerste jaar Memory Lane is inbegrepen. Na 12 maanden start automatisch een maandelijks abonnement zodat je woning online beschikbaar blijft. Je kan op elk moment opzeggen via je klantenzone.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Naar mijn klantenzone</a></p>
