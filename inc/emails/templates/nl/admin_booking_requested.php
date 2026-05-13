<?php defined( 'ABSPATH' ) || exit;
$subject = '[Memory Lane] Nieuwe boekingsaanvraag';
$when = isset( $slot->slot_start_datetime ) ? wp_date( 'j F Y H:i', strtotime( $slot->slot_start_datetime . ' UTC' ) ) : '';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Nieuwe boekingsaanvraag</h2>
<p>Klant: <strong><?php echo esc_html( $user->user_email ); ?></strong></p>
<p>Wanneer: <strong><?php echo esc_html( $when ); ?></strong></p>
<?php if ( ! empty( $notes ) ) : ?><p>Notities: <em><?php echo esc_html( $notes ); ?></em></p><?php endif; ?>
<p style="margin:24px 0;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=memorylane-bookings' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Boekingen openen</a></p>
