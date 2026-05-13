<?php defined( 'ABSPATH' ) || exit;
$subject = '[Memory Lane] Wees-betalingen gedetecteerd';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Wees-betalingen</h2>
<p>De volgende Stripe checkout sessies hebben geen gekoppelde WP-gebruiker. Handmatige opvolging vereist.</p>
<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;font-size:13px;">
<tr><th>Session</th><th>Customer</th><th>Email</th><th>Amount</th></tr>
<?php foreach ( (array) $orphans as $o ) : ?>
<tr>
    <td><code><?php echo esc_html( $o['session'] ); ?></code></td>
    <td><code><?php echo esc_html( $o['customer'] ); ?></code></td>
    <td><?php echo esc_html( $o['email'] ); ?></td>
    <td><?php echo esc_html( number_format( ( $o['amount'] ?? 0 ) / 100, 2 ) ); ?></td>
</tr>
<?php endforeach; ?>
</table>
