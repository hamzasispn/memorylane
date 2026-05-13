<?php defined( 'ABSPATH' ) || exit;
$subject = 'Reactivatie terugbetaald — Memory Lane';
$is_admin = ! empty( $admin );
$display  = $user ? ( $user->display_name ?: $user->user_email ) : '—';
?>
<?php if ( $is_admin ) : ?>
<h2 style="margin:0 0 16px;font-size:20px;">Reactivatie terugbetaald</h2>
<p>De reactivatie van <?php echo esc_html( $display ); ?> is terugbetaald. Het abonnement is geannuleerd en de tours zijn opnieuw gearchiveerd.</p>
<?php else : ?>
<h2 style="margin:0 0 16px;font-size:20px;">Je reactivatie is terugbetaald</h2>
<p>Hallo <?php echo esc_html( $display ); ?>, we hebben je reactivatiekost terugbetaald. Je tour is opnieuw gearchiveerd.</p>
<p>Heb je vragen? Antwoord op deze e-mail.</p>
<?php endif; ?>
