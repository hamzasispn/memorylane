<?php defined( 'ABSPATH' ) || exit;
// V2-3: working hours admin form.
$hours        = ml_booking_working_hours();
$slot_length  = ml_booking_slot_length_minutes();
$capacity     = ml_booking_capacity_per_slot();
$window_days  = ml_booking_window_days();
$blocked      = ml_booking_blocked_dates();

$days = array(
    'mon' => 'Monday',    'tue' => 'Tuesday', 'wed' => 'Wednesday',
    'thu' => 'Thursday',  'fri' => 'Friday',  'sat' => 'Saturday', 'sun' => 'Sunday',
);
?>

<form class="mla-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <?php wp_nonce_field( 'ml_ap_slots_save' ); ?>
    <input type="hidden" name="action" value="ml_ap_slots_save">

    <h2>Working hours</h2>
    <p class="mla-muted" style="margin:0 0 12px;">When customers can pick a scan appointment. Uncheck a day to mark it closed.</p>

    <div class="mla-week" style="margin-bottom:16px;">
        <div></div><div></div><div></div><div></div><div></div>
        <?php foreach ( $days as $key => $label ) :
            $h = $hours[ $key ] ?? array( 'enabled' => false, 'start' => '09:00', 'end' => '17:00' );
        ?>
            <div class="day-label"><?php echo esc_html( $label ); ?></div>
            <label><input type="checkbox" name="hours[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $h['enabled'] ); ?>> Open</label>
            <input type="time" name="hours[<?php echo esc_attr( $key ); ?>][start]" value="<?php echo esc_attr( $h['start'] ); ?>">
            <span class="mla-muted">to</span>
            <input type="time" name="hours[<?php echo esc_attr( $key ); ?>][end]"   value="<?php echo esc_attr( $h['end'] ); ?>">
        <?php endforeach; ?>
    </div>

    <h2>Slot defaults</h2>
    <div class="mla-form-row">
        <label for="sl-length">Slot length (minutes)</label>
        <div><input id="sl-length" type="number" name="slot_length" value="<?php echo (int) $slot_length; ?>" min="15" step="15"></div>
    </div>
    <div class="mla-form-row">
        <label for="sl-cap">Capacity per slot</label>
        <div><input id="sl-cap" type="number" name="capacity" value="<?php echo (int) $capacity; ?>" min="1"></div>
    </div>
    <div class="mla-form-row">
        <label for="sl-win">Booking window (days)</label>
        <div><input id="sl-win" type="number" name="window_days" value="<?php echo (int) $window_days; ?>" min="1" max="365">
            <div class="help">How far ahead customers can book.</div>
        </div>
    </div>
    <div class="mla-form-row">
        <label for="sl-blocked">Blocked dates</label>
        <div><textarea id="sl-blocked" name="blocked_dates" rows="6" placeholder="2026-12-25&#10;2027-01-01"><?php echo esc_textarea( implode( "\n", $blocked ) ); ?></textarea>
            <div class="help">One YYYY-MM-DD per line. Customer cannot pick these dates.</div>
        </div>
    </div>

    <div style="margin-top:16px;">
        <button class="mla-btn mla-btn--primary" type="submit">Save</button>
    </div>
</form>
