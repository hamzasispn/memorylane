<?php defined( 'ABSPATH' ) || exit;
$subject = 'Abonnement geannuleerd — Memory Lane';
$end = isset( $end_date ) ? wp_date( 'j F Y', (int) $end_date ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Je abonnement is geannuleerd</h2>
<p>Je behoudt toegang tot Memory Lane tot <strong><?php echo esc_html( $end ); ?></strong>. Daarna wordt je virtuele tour gearchiveerd.</p>
<p>Je kan jouw woning later opnieuw activeren via een eenmalige reactivatie.</p>
