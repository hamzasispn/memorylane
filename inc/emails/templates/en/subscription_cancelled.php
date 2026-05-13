<?php defined( 'ABSPATH' ) || exit;
$subject = 'Subscription cancelled — Memory Lane';
$end = isset( $end_date ) ? wp_date( 'F j, Y', (int) $end_date ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Your subscription has been cancelled</h2>
<p>You keep access to Memory Lane until <strong><?php echo esc_html( $end ); ?></strong>. After that, your virtual tour will be archived.</p>
<p>You can later reactivate your property via a one-time reactivation fee.</p>
