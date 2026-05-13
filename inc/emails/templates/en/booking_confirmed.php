<?php defined( 'ABSPATH' ) || exit;
$subject = 'Appointment confirmed — Memory Lane';
$when = isset( $booking->scheduled_for ) ? wp_date( 'F j, Y H:i', strtotime( $booking->scheduled_for . ' UTC' ) ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Your appointment is confirmed</h2>
<p>See you on <strong><?php echo esc_html( $when ); ?></strong>. Our team will be there for the 3D scan.</p>
