<?php defined( 'ABSPATH' ) || exit;
$subject  = 'Reactivation refunded — Memory Lane';
$is_admin = ! empty( $admin );
$display  = $user ? ( $user->display_name ?: $user->user_email ) : '—';
?>
<?php if ( $is_admin ) : ?>
<h2 style="margin:0 0 16px;font-size:20px;">Reactivation refunded</h2>
<p>The reactivation for <?php echo esc_html( $display ); ?> has been refunded. The subscription was cancelled and the tours are archived again.</p>
<?php else : ?>
<h2 style="margin:0 0 16px;font-size:20px;">Your reactivation was refunded</h2>
<p>Hi <?php echo esc_html( $display ); ?>, we refunded your reactivation fee. Your tour has been archived again.</p>
<p>Any questions? Just reply to this email.</p>
<?php endif; ?>
