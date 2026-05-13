<?php defined( 'ABSPATH' ) || exit;
$subject = 'Reactivatie aanvraag — actie vereist';
$email   = $user ? $user->user_email : '—';
$plan_lbl= ( $plan ?? 'monthly' ) === 'annual' ? 'Jaarlijks' : 'Maandelijks';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Nieuwe reactivatie aanvraag</h2>
<p><strong><?php echo esc_html( $email ); ?></strong> heeft betaald om hun tour te heractiveren.</p>
<ul>
    <li>Cyclus: #<?php echo (int) ( $cycle ?? 0 ); ?></li>
    <li>Plan: <?php echo esc_html( $plan_lbl ); ?></li>
    <li>Tours te activeren: <?php echo is_array( $tours ) ? count( $tours ) : 0; ?></li>
</ul>
<?php if ( ! empty( $tours ) ) : ?>
<p><strong>Tour ID's + Matterport links:</strong></p>
<ul>
<?php foreach ( $tours as $t ) :
    $url = get_post_meta( $t->ID, ML_META_TOUR_URL, true ); ?>
    <li>#<?php echo (int) $t->ID; ?> — <?php echo esc_html( $t->post_title ); ?><?php if ( $url ) : ?> — <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $url ); ?></a><?php endif; ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<p style="margin:24px 0;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=memorylane-reactivations' ) ); ?>" style="background:#F97316;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Open de reactivatiequeue</a></p>
<p style="color:#71717A;font-size:12px;">SLA: <?php echo (int) ML_REACTIVATION_SLA_HOURS; ?> uur. Zet eerst de Matterport space op publiek, klik dan "Reactivation done".</p>
