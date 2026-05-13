<?php defined( 'ABSPATH' ) || exit;
$subject = 'Boekingsverzoek ontvangen — Memory Lane';
$when = isset( $slot->slot_start_datetime ) ? wp_date( 'j F Y H:i', strtotime( $slot->slot_start_datetime . ' UTC' ) ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Boeking aangevraagd</h2>
<p>We hebben je verzoek ontvangen voor een opname op <strong><?php echo esc_html( $when ); ?></strong>. Je krijgt een bevestiging zodra ons team de afspraak goedkeurt.</p>
