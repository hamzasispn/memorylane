<?php defined( 'ABSPATH' ) || exit;

$hours       = ml_booking_working_hours();
$slot_len    = ml_booking_slot_length_minutes();
$window_days = ml_booking_window_days();
$blocked     = ml_booking_blocked_dates();

$day_labels = array(
    'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday',
    'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday',
);
?>

<form class="mla-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <?php wp_nonce_field( 'ml_ap_settings_save' ); ?>
    <input type="hidden" name="action" value="ml_ap_settings_save">

    <h2>Working hours</h2>
    <p class="help">Bookings are open on every working day within the booking window — there are no fixed slots or capacity. Times below define the bookable hours per day.</p>
    <table class="mla-table" style="max-width:640px;">
        <thead><tr><th>Day</th><th>Open</th><th>Start</th><th>End</th></tr></thead>
        <tbody>
        <?php foreach ( $day_labels as $key => $label ) :
            $row = $hours[ $key ] ?? array( 'enabled' => false, 'start' => '09:00', 'end' => '17:00' );
        ?>
            <tr>
                <td><?php echo esc_html( $label ); ?></td>
                <td><input type="checkbox" name="hours[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $row['enabled'] ) ); ?>></td>
                <td><input type="time" name="hours[<?php echo esc_attr( $key ); ?>][start]" value="<?php echo esc_attr( $row['start'] ?? '09:00' ); ?>"></td>
                <td><input type="time" name="hours[<?php echo esc_attr( $key ); ?>][end]" value="<?php echo esc_attr( $row['end'] ?? '17:00' ); ?>"></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mla-form-row" style="margin-top:16px;">
        <label>Appointment length (minutes)</label>
        <div><input type="number" name="slot_length" value="<?php echo (int) $slot_len; ?>" min="15" step="5" style="max-width:120px;"></div>
    </div>
    <div class="mla-form-row">
        <label>Booking window (days ahead)</label>
        <div><input type="number" name="window_days" value="<?php echo (int) $window_days; ?>" min="1" max="365" style="max-width:120px;">
            <div class="help">How far into the future visitors can book.</div>
        </div>
    </div>
    <div class="mla-form-row">
        <label>Blocked dates</label>
        <div><textarea name="blocked_dates" rows="3" placeholder="2026-12-25"><?php echo esc_textarea( implode( "\n", $blocked ) ); ?></textarea>
            <div class="help">One YYYY-MM-DD per line. These days are closed for booking.</div>
        </div>
    </div>

    <h2 style="margin-top:24px;">Booking rules</h2>
    <div class="mla-form-row">
        <label>Cancel notice (hours)</label>
        <div><input type="number" name="cancel_hours" value="<?php echo (int) get_option( ML_OPT_BOOKING_CANCEL_HOURS, 24 ); ?>" min="0" style="max-width:120px;"></div>
    </div>
    <div class="mla-form-row">
        <label>Reschedule notice (hours)</label>
        <div><input type="number" name="reschedule_hours" value="<?php echo (int) get_option( ML_OPT_BOOKING_RESCHED_HOURS, 24 ); ?>" min="0" style="max-width:120px;"></div>
    </div>

    <h2 style="margin-top:24px;">Notifications</h2>
    <div class="mla-form-row">
        <label>Admin recipients</label>
        <div><input type="text" name="admin_recipients" value="<?php echo esc_attr( (string) get_option( ML_OPT_ADMIN_RECIPIENTS, get_option( 'admin_email' ) ) ); ?>" placeholder="alice@…, bob@…">
            <div class="help">Comma-separated. Notified of new bookings.</div>
        </div>
    </div>
    <div class="mla-form-row">
        <label>From name</label>
        <div><input type="text" name="email_from_name" value="<?php echo esc_attr( (string) get_option( ML_OPT_EMAIL_FROM_NAME, 'Memory Lane' ) ); ?>"></div>
    </div>
    <div class="mla-form-row">
        <label>From email</label>
        <div><input type="email" name="email_from_address" value="<?php echo esc_attr( (string) get_option( ML_OPT_EMAIL_FROM_ADDRESS, '' ) ); ?>" placeholder="no-reply@yourdomain.com"></div>
    </div>

    <h2 style="margin-top:24px;">Embed allowlist</h2>
    <div class="mla-form-row">
        <label>Allowed iframe domains</label>
        <div><textarea name="embed_allowlist" rows="3"><?php echo esc_textarea( (string) get_option( ML_OPT_EMBED_DOMAIN_ALLOW, "my.matterport.com\nmatterport.com" ) ); ?></textarea>
            <div class="help">One domain per line. Only iframes from these domains render in tours.</div>
        </div>
    </div>

    <div style="margin-top:24px;">
        <button class="mla-btn mla-btn--primary" type="submit">Save settings</button>
    </div>
</form>

<?php ml_tl_render_settings(); ?>
