<?php defined( 'ABSPATH' ) || exit;
$subject = '[Memory Lane] Betaling definitief mislukt';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Betaling definitief mislukt</h2>
<p>Klant <strong><?php echo esc_html( $user->user_email ?? '—' ); ?></strong> heeft een mislukte betaling die niet meer opnieuw geprobeerd wordt. Manueel opvolgen vereist.</p>
