<?php defined( 'ABSPATH' ) || exit;
$subject = 'Afspraak bevestigd — Memory Lane';
$when = isset( $booking->scheduled_for ) ? wp_date( 'j F Y H:i', strtotime( $booking->scheduled_for . ' UTC' ) ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Je afspraak is bevestigd</h2>
<p>We zien je op <strong><?php echo esc_html( $when ); ?></strong>. Ons team zal aanwezig zijn voor de 3D-scan.</p>
