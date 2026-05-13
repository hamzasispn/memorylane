<?php defined( 'ABSPATH' ) || exit;
$subject = '[Memory Lane] Tours in afwachting van archivering';
?>
<h2 style="margin:0 0 16px;font-size:20px;">Tours moeten geprivatiseerd worden</h2>
<p>De volgende tours zijn niet langer toegankelijk in de klantenzone wegens beëindigd abonnement. Zet de bijhorende spaces op privé bij Matterport.</p>
<?php if ( ! empty( $tours ) && is_array( $tours ) ) : ?>
<ul>
<?php foreach ( $tours as $t ) : ?>
    <li><?php echo esc_html( $t['address'] ?? '' ); ?> — <a href="<?php echo esc_url( $t['url'] ?? '' ); ?>"><?php echo esc_html( $t['url'] ?? '' ); ?></a></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
