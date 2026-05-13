<?php
/**
 * Memory Lane — admin Bookings page (list slots + bookings, create slots, confirm/cancel/complete).
 */
defined( 'ABSPATH' ) || exit;

function ml_admin_render_bookings() {
    if ( ! current_user_can( ML_CAP_MANAGE ) ) wp_die();
    global $wpdb;
    $slots_tbl = ml_table( 'availability_slots' );
    $book_tbl  = ml_table( 'bookings' );

    // Handle actions.
    if ( isset( $_POST['ml_create_slots'] ) && check_admin_referer( 'ml_admin_slots' ) ) {
        $date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
        $date_to   = sanitize_text_field( $_POST['date_to']   ?? '' );
        $times_raw = (array) ( $_POST['times']  ?? array( '09:00' ) );
        $times     = array_values( array_filter( array_map( 'sanitize_text_field', $times_raw ) ) );
        if ( empty( $times ) ) $times = array( '09:00' );
        $duration  = max( 30, (int) ( $_POST['duration']      ?? 60 ) );
        $weekdays  = array_map( 'intval', (array) ( $_POST['weekdays'] ?? array() ) );
        $count = ml_admin_create_slots_bulk( $date_from, $date_to, $times, $duration, $weekdays );
        echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Created %d slots.', 'memorylane' ), $count ) ) . '</p></div>';
    }
    if ( isset( $_GET['ml_action'], $_GET['id'] ) && check_admin_referer( 'ml_booking_action' ) ) {
        $id  = (int) $_GET['id'];
        $msg = '';
        switch ( $_GET['ml_action'] ) {
            case 'confirm':
                $wpdb->update( $book_tbl, array( 'status' => 'confirmed', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $id ) );
                $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$book_tbl} WHERE id=%d", $id ) );
                if ( $row ) {
                    $user = get_user_by( 'id', $row->user_id );
                    if ( $user ) ml_mail_send( $user->user_email, 'booking_confirmed', array( 'user' => $user, 'booking' => $row ), $user->ID );
                }
                $msg = 'confirmed';
                break;
            case 'complete':
                $wpdb->update( $book_tbl, array( 'status' => 'completed', 'completed_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $id ) );
                $msg = 'completed';
                break;
            case 'cancel':
                $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$book_tbl} WHERE id=%d", $id ) );
                $wpdb->update( $book_tbl, array( 'status' => 'cancelled', 'cancelled_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $id ) );
                if ( $row && $row->slot_id ) ml_decrement_slot_booked( $row->slot_id );
                $msg = 'cancelled';
                break;
        }
        // PRG: redirect to clean URL so refresh doesn't replay the action and cache sees a fresh request.
        wp_safe_redirect( add_query_arg( 'ml_msg', rawurlencode( $msg ), admin_url( 'admin.php?page=memorylane-bookings' ) ) );
        exit;
    }
    $action_msg = isset( $_GET['ml_msg'] ) ? sanitize_key( wp_unslash( $_GET['ml_msg'] ) ) : '';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Bookings', 'memorylane' ); ?></h1>

        <h2 style="margin-top:24px;"><?php esc_html_e( 'Create availability slots (bulk)', 'memorylane' ); ?></h2>
        <form method="post" style="background:#fff;border:1px solid #ddd;padding:16px;border-radius:8px;max-width:760px;">
            <?php wp_nonce_field( 'ml_admin_slots' ); ?>
            <table class="form-table">
                <tr><th><?php esc_html_e( 'Date from', 'memorylane' ); ?></th><td><input type="date" name="date_from" required></td></tr>
                <tr><th><?php esc_html_e( 'Date to', 'memorylane' ); ?></th><td><input type="date" name="date_to" required></td></tr>
                <tr><th><?php esc_html_e( 'Time slots (per day)', 'memorylane' ); ?></th><td>
                    <div id="ml-times-wrap" style="display:flex;gap:6px;flex-wrap:wrap;">
                        <input type="time" name="times[]" value="09:00" required>
                        <input type="time" name="times[]" value="11:00">
                        <input type="time" name="times[]" value="14:00">
                        <input type="time" name="times[]" value="16:00">
                    </div>
                    <button type="button" class="button button-small" style="margin-top:6px;" onclick="var w=document.getElementById('ml-times-wrap');var i=document.createElement('input');i.type='time';i.name='times[]';w.appendChild(i);">+ <?php esc_html_e( 'Add slot', 'memorylane' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Add as many start times as you like — one slot per time per matching weekday.', 'memorylane' ); ?></p>
                </td></tr>
                <tr><th><?php esc_html_e( 'Duration (minutes)', 'memorylane' ); ?></th><td><input type="number" name="duration" value="60" min="30" max="480"></td></tr>
                <tr><th><?php esc_html_e( 'Weekdays', 'memorylane' ); ?></th><td>
                    <?php $labels = array( __( 'Mon', 'memorylane' ), __( 'Tue', 'memorylane' ), __( 'Wed', 'memorylane' ), __( 'Thu', 'memorylane' ), __( 'Fri', 'memorylane' ), __( 'Sat', 'memorylane' ), __( 'Sun', 'memorylane' ) );
                    foreach ( $labels as $i => $l ) : ?>
                        <label style="margin-right:12px;"><input type="checkbox" name="weekdays[]" value="<?php echo $i + 1; ?>" <?php echo $i < 5 ? 'checked' : ''; ?>> <?php echo esc_html( $l ); ?></label>
                    <?php endforeach; ?>
                </td></tr>
            </table>
            <button type="submit" name="ml_create_slots" value="1" class="button button-primary"><?php esc_html_e( 'Create slots', 'memorylane' ); ?></button>
        </form>

        <h2 style="margin-top:32px;"><?php esc_html_e( 'Upcoming slots', 'memorylane' ); ?></h2>
        <?php
        $slots = $wpdb->get_results( "SELECT * FROM {$slots_tbl} WHERE slot_start_datetime > UTC_TIMESTAMP() ORDER BY slot_start_datetime ASC LIMIT 100" );
        if ( empty( $slots ) ) :
            echo '<p>' . esc_html__( 'No slots yet.', 'memorylane' ) . '</p>';
        else :
            echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Start (UTC)', 'memorylane' ) . '</th><th>' . esc_html__( 'Capacity', 'memorylane' ) . '</th><th>' . esc_html__( 'Status', 'memorylane' ) . '</th></tr></thead><tbody>';
            foreach ( $slots as $s ) :
                echo '<tr><td>' . esc_html( $s->slot_start_datetime ) . '</td><td>' . (int) $s->booked_count . ' / ' . (int) $s->capacity . '</td><td>' . esc_html( $s->status ) . '</td></tr>';
            endforeach;
            echo '</tbody></table>';
        endif;
        ?>

        <h2 style="margin-top:32px;"><?php esc_html_e( 'Bookings', 'memorylane' ); ?></h2>
        <?php
        $rows = $wpdb->get_results( "SELECT b.*, u.user_email FROM {$book_tbl} b LEFT JOIN {$wpdb->users} u ON u.ID=b.user_id ORDER BY b.id DESC LIMIT 200" );
        echo '<table class="widefat striped"><thead><tr><th>#</th><th>' . esc_html__( 'Customer', 'memorylane' ) . '</th><th>' . esc_html__( 'When', 'memorylane' ) . '</th><th>' . esc_html__( 'Status', 'memorylane' ) . '</th><th></th></tr></thead><tbody>';
        if ( empty( $rows ) ) {
            echo '<tr><td colspan="5">' . esc_html__( 'No bookings.', 'memorylane' ) . '</td></tr>';
        } else {
            foreach ( $rows as $b ) :
                $nonce = wp_create_nonce( 'ml_booking_action' );
                echo '<tr>';
                echo '<td>' . (int) $b->id . '</td>';
                echo '<td>' . esc_html( $b->user_email ?: '—' ) . '</td>';
                echo '<td>' . esc_html( $b->scheduled_for ) . '</td>';
                echo '<td>' . esc_html( $b->status ) . '</td>';
                echo '<td>';
                if ( $b->status === 'requested' ) {
                    echo '<a class="button button-small button-primary" href="' . esc_url( admin_url( 'admin.php?page=memorylane-bookings&ml_action=confirm&id=' . $b->id . '&_wpnonce=' . $nonce ) ) . '">' . esc_html__( 'Confirm', 'memorylane' ) . '</a> ';
                }
                if ( in_array( $b->status, array( 'requested', 'confirmed' ), true ) ) {
                    echo '<a class="button button-small" href="' . esc_url( admin_url( 'admin.php?page=memorylane-bookings&ml_action=cancel&id=' . $b->id . '&_wpnonce=' . $nonce ) ) . '">' . esc_html__( 'Cancel', 'memorylane' ) . '</a> ';
                }
                if ( $b->status === 'confirmed' ) {
                    echo '<a class="button button-small" href="' . esc_url( admin_url( 'admin.php?page=memorylane-bookings&ml_action=complete&id=' . $b->id . '&_wpnonce=' . $nonce ) ) . '">' . esc_html__( 'Mark complete', 'memorylane' ) . '</a>';
                }
                echo '</td></tr>';
            endforeach;
        }
        echo '</tbody></table>';
        ?>
    </div>
    <?php
}

function ml_admin_create_slots_bulk( $date_from, $date_to, $times, $duration_min, $weekdays ) {
    global $wpdb;
    $tbl = ml_table( 'availability_slots' );
    $count = 0;
    if ( ! $date_from || ! $date_to ) return 0;
    if ( ! is_array( $times ) ) $times = array( $times );

    $start = strtotime( $date_from );
    $end   = strtotime( $date_to );
    if ( $end < $start ) return 0;

    for ( $t = $start; $t <= $end; $t += DAY_IN_SECONDS ) {
        $iso_dow = (int) wp_date( 'N', $t );
        if ( ! empty( $weekdays ) && ! in_array( $iso_dow, $weekdays, true ) ) continue;

        foreach ( $times as $time ) {
            $time = preg_match( '/^\d{2}:\d{2}$/', $time ) ? $time : '09:00';
            $slot_start = gmdate( 'Y-m-d', $t ) . ' ' . $time . ':00';
            $slot_end   = gmdate( 'Y-m-d H:i:s', strtotime( $slot_start ) + $duration_min * 60 );

            $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tbl} WHERE slot_start_datetime=%s", $slot_start ) );
            if ( $existing ) continue;

            $wpdb->insert( $tbl, array(
                'slot_start_datetime' => $slot_start,
                'slot_end_datetime'   => $slot_end,
                'capacity'            => 1,
                'booked_count'        => 0,
                'status'              => 'open',
                'created_by'          => get_current_user_id(),
                'created_at'          => current_time( 'mysql', true ),
            ) );
            $count++;
        }
    }
    return $count;
}
