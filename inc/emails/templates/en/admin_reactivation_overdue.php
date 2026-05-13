<?php defined( 'ABSPATH' ) || exit;
$count   = is_array( $overdue ) ? count( $overdue ) : 0;
$has_24h = false;
if ( is_array( $overdue ) ) { foreach ( $overdue as $o ) { if ( $o['hours'] >= 24 ) { $has_24h = true; break; } } }
$subject = ( $has_24h ? 'URGENT — ' : '' ) . sprintf( '%d reactivation(s) awaiting Matterport', $count );
?>
<h2 style="margin:0 0 16px;font-size:20px;"><?php echo $has_24h ? '⚠️ ' : ''; ?>Reactivations awaiting manual activation</h2>
<p>The following have been pending for more than <?php echo (int) ML_REACTIVATION_SLA_HOURS; ?> hours:</p>
<table cellpadding="6" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:13px;">
    <thead><tr style="background:#F4F4F5;"><th align="left">Customer</th><th align="left">Cycle</th><th align="left">Plan</th><th align="left">Tours</th><th align="left">Open for</th></tr></thead>
    <tbody>
    <?php foreach ( (array) $overdue as $o ) : ?>
        <tr style="border-top:1px solid #E4E4E7;">
            <td><?php echo esc_html( $o['email'] ); ?></td>
            <td>#<?php echo (int) $o['cycle']; ?></td>
            <td><?php echo esc_html( ucfirst( $o['plan'] ) ); ?></td>
            <td><?php echo (int) $o['tour_n']; ?></td>
            <td><?php echo esc_html( $o['hours'] ); ?>h</td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p style="margin:24px 0;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=memorylane-reactivations' ) ); ?>" style="background:#F97316;color:#fff;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;font-weight:500;">Open the queue</a></p>
