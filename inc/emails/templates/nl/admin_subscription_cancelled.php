<?php defined( 'ABSPATH' ) || exit;
$subject = '[Memory Lane] Abonnement geannuleerd';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Abonnement geannuleerd</h2>
<p>Klant <strong><?php echo esc_html( $user->user_email ?? '—' ); ?></strong> heeft zijn abonnement opgezegd. Bekijk de tours van deze klant en zet ze indien gewenst op privé bij Matterport.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=memorylane-customers' ) ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Klant openen</a></p>
