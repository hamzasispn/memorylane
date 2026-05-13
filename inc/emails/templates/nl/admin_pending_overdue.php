<?php defined( 'ABSPATH' ) || exit;
$subject = '[Memory Lane] Goedkeuring(en) lopen achter op SLA';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Goedkeuringen openstaand &gt; SLA</h2>
<p>De volgende klanten hebben betaald maar wachten al langer dan de SLA op goedkeuring (toegangsactivering). Werk hen af in WP Admin → Memory Lane → Customers.</p>
<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;font-size:13px;">
<tr><th>Klant</th><th>Betaald op</th><th>Uren wachten</th><th>Bedrag</th></tr>
<?php foreach ( (array) ( $pending ?? array() ) as $p ) : ?>
<tr>
    <td><?php echo esc_html( $p['email'] ); ?></td>
    <td><?php echo esc_html( $p['paid_at'] ); ?></td>
    <td><?php echo esc_html( $p['hours'] ); ?>h</td>
    <td><?php echo esc_html( $p['amount'] ); ?></td>
</tr>
<?php endforeach; ?>
</table>
