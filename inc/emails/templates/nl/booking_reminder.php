<?php defined( 'ABSPATH' ) || exit;
$subject = 'Herinnering: afspraak morgen — Memory Lane';
$when = isset( $booking->scheduled_for ) ? wp_date( 'j F Y H:i', strtotime( $booking->scheduled_for . ' UTC' ) ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Herinnering: afspraak morgen</h2>
<p>We zien je op <strong><?php echo esc_html( $when ); ?></strong> voor de 3D-scan. Tot dan!</p>
