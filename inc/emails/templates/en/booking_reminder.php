<?php defined( 'ABSPATH' ) || exit;
$subject = 'Reminder: appointment tomorrow — Memory Lane';
$when = isset( $booking->scheduled_for ) ? wp_date( 'F j, Y H:i', strtotime( $booking->scheduled_for . ' UTC' ) ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Reminder: appointment tomorrow</h2>
<p>See you on <strong><?php echo esc_html( $when ); ?></strong> for the 3D scan. Until then!</p>
