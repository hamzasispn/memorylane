<?php
/**
 * Memory Lane — tour admin meta box (assign user, paste embed, status).
 */
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'ml_tour_meta',
        __( 'Tour details', 'memorylane' ),
        'ml_render_tour_metabox',
        ML_CPT_TOUR,
        'normal',
        'high'
    );
} );

function ml_render_tour_metabox( $post ) {
    wp_nonce_field( 'ml_tour_meta', 'ml_tour_meta_nonce' );

    $user_id  = (int) get_post_meta( $post->ID, ML_META_TOUR_USER, true );
    $provider = get_post_meta( $post->ID, ML_META_TOUR_PROVIDER, true ) ?: 'matterport';
    $url      = get_post_meta( $post->ID, ML_META_TOUR_URL, true );
    $embed    = get_post_meta( $post->ID, ML_META_TOUR_EMBED, true );
    $status   = get_post_meta( $post->ID, ML_META_TOUR_STATUS, true ) ?: ML_TOUR_STATUS_ACTIVE;
    $address  = get_post_meta( $post->ID, ML_META_TOUR_ADDRESS, true );

    $users = get_users( array( 'role__in' => array( ML_ROLE_CUSTOMER, 'administrator' ), 'fields' => array( 'ID', 'user_email', 'display_name' ), 'number' => 500, 'orderby' => 'display_name', 'order' => 'ASC' ) );
    ?>
    <table class="form-table">
        <tr><th><label for="ml_tour_user"><?php esc_html_e( 'Assigned customer', 'memorylane' ); ?></label></th>
            <td><select id="ml_tour_user" name="ml_tour_user">
                <option value="0">— <?php esc_html_e( 'Unassigned', 'memorylane' ); ?> —</option>
                <?php foreach ( $users as $u ) : ?>
                    <option value="<?php echo (int) $u->ID; ?>" <?php selected( $user_id, $u->ID ); ?>>
                        <?php echo esc_html( $u->display_name . ' (' . $u->user_email . ')' ); ?>
                    </option>
                <?php endforeach; ?>
            </select></td>
        </tr>
        <tr><th><label for="ml_tour_address"><?php esc_html_e( 'Property address', 'memorylane' ); ?></label></th>
            <td><input type="text" id="ml_tour_address" name="ml_tour_address" value="<?php echo esc_attr( $address ); ?>" class="regular-text"></td></tr>
        <tr><th><label for="ml_tour_provider"><?php esc_html_e( 'Provider', 'memorylane' ); ?></label></th>
            <td><input type="text" id="ml_tour_provider" name="ml_tour_provider" value="<?php echo esc_attr( $provider ); ?>" class="regular-text"></td></tr>
        <tr><th><label for="ml_tour_url"><?php esc_html_e( 'Public URL (optional)', 'memorylane' ); ?></label></th>
            <td><input type="url" id="ml_tour_url" name="ml_tour_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text"></td></tr>
        <tr><th><label for="ml_tour_embed"><?php esc_html_e( 'Embed code (iframe)', 'memorylane' ); ?></label></th>
            <td>
                <textarea id="ml_tour_embed" name="ml_tour_embed" rows="6" class="large-text code"><?php echo esc_textarea( $embed ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Paste the iframe code from Matterport. Allowed domains:', 'memorylane' ); ?> <code><?php echo esc_html( implode( ', ', ml_embed_domain_allowlist() ) ); ?></code></p>
            </td></tr>
        <tr><th><label for="ml_tour_status"><?php esc_html_e( 'Status', 'memorylane' ); ?></label></th>
            <td><select id="ml_tour_status" name="ml_tour_status">
                <option value="active"          <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'memorylane' ); ?></option>
                <option value="archived"        <?php selected( $status, 'archived' ); ?>><?php esc_html_e( 'Archived', 'memorylane' ); ?></option>
                <option value="pending_archive" <?php selected( $status, 'pending_archive' ); ?>><?php esc_html_e( 'Pending archive', 'memorylane' ); ?></option>
            </select></td>
        </tr>
    </table>
    <?php
}

add_action( 'save_post_' . ML_CPT_TOUR, function ( $post_id ) {
    if ( ! isset( $_POST['ml_tour_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ml_tour_meta_nonce'], 'ml_tour_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $user_id = (int) ( $_POST['ml_tour_user'] ?? 0 );
    update_post_meta( $post_id, ML_META_TOUR_USER, $user_id );

    update_post_meta( $post_id, ML_META_TOUR_ADDRESS,  sanitize_text_field( wp_unslash( $_POST['ml_tour_address']  ?? '' ) ) );
    update_post_meta( $post_id, ML_META_TOUR_PROVIDER, sanitize_text_field( wp_unslash( $_POST['ml_tour_provider'] ?? 'matterport' ) ) );
    update_post_meta( $post_id, ML_META_TOUR_URL,      esc_url_raw( wp_unslash( $_POST['ml_tour_url'] ?? '' ) ) );

    $embed_raw  = wp_unslash( $_POST['ml_tour_embed'] ?? '' );
    $embed_safe = ml_sanitize_tour_embed( $embed_raw );
    update_post_meta( $post_id, ML_META_TOUR_EMBED, $embed_safe );

    $status = sanitize_key( wp_unslash( $_POST['ml_tour_status'] ?? 'active' ) );
    if ( ! in_array( $status, array( 'active', 'archived', 'pending_archive' ), true ) ) $status = 'active';
    update_post_meta( $post_id, ML_META_TOUR_STATUS, $status );

    // If a customer just got assigned + tour is active, notify them.
    $old_user = (int) get_post_meta( $post_id, '_ml_tour_assigned_at_user', true );
    if ( $user_id && $user_id !== $old_user ) {
        $user = get_user_by( 'id', $user_id );
        if ( $user ) {
            ml_mail_send( $user->user_email, 'tour_assigned', array(
                'user' => $user,
                'tour_url' => get_edit_post_link( $post_id ),
                'tour'     => get_post( $post_id ),
            ), $user_id );
        }
        update_post_meta( $post_id, '_ml_tour_assigned_at_user', $user_id );
    }
}, 10, 1 );
