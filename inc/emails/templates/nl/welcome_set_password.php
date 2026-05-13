<?php defined( 'ABSPATH' ) || exit;
$subject = 'Welkom bij Memory Lane — stel je wachtwoord in';
$display = $user->display_name ?: $user->user_email;
?>
<h2 style="margin:0 0 16px;font-size:20px;">Welkom bij Memory Lane, <?php echo esc_html( $display ); ?></h2>
<p>Je betaling is gelukt. Klik op de knop hieronder om je wachtwoord in te stellen en toegang te krijgen tot je persoonlijke klantenzone.</p>
<p style="margin:24px 0;"><a href="<?php echo esc_url( $url ); ?>" style="background:#2563EB;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Wachtwoord instellen</a></p>
<p style="color:#71717A;font-size:13px;">Of kopieer deze link in je browser: <br><span style="color:#3F3F46;word-break:break-all;"><?php echo esc_html( $url ); ?></span></p>
<p style="color:#71717A;font-size:13px;">Deze link is 24 uur geldig.</p>
