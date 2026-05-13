<?php defined( 'ABSPATH' ) || exit;
$subject = 'Booking request received — Memory Lane';
$when = isset( $slot->slot_start_datetime ) ? wp_date( 'F j, Y H:i', strtotime( $slot->slot_start_datetime . ' UTC' ) ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Booking requested</h2>
<p>We received your request for a scan on <strong><?php echo esc_html( $when ); ?></strong>. You will get a confirmation as soon as our team approves the appointment.</p>
