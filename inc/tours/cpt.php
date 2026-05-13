<?php
/**
 * Memory Lane — virtual tour custom post type.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
    register_post_type( ML_CPT_TOUR, array(
        'label'        => __( 'Tours', 'memorylane' ),
        'labels'       => array(
            'name'          => __( 'Tours', 'memorylane' ),
            'singular_name' => __( 'Tour', 'memorylane' ),
            'add_new_item'  => __( 'Add new tour', 'memorylane' ),
            'edit_item'     => __( 'Edit tour', 'memorylane' ),
        ),
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => 'memorylane',
        'capability_type' => 'post',
        'map_meta_cap'  => true,
        'supports'      => array( 'title', 'author' ),
        'has_archive'   => false,
        'rewrite'       => false,
        'menu_icon'     => 'dashicons-format-video',
    ) );
} );

/**
 * Get all tours assigned to a user.
 */
function ml_get_user_tours( $user_id ) {
    return get_posts( array(
        'post_type'   => ML_CPT_TOUR,
        'numberposts' => -1,
        'post_status' => 'publish',
        'meta_query'  => array( array(
            'key'   => ML_META_TOUR_USER,
            'value' => (int) $user_id,
        ) ),
        'orderby' => 'date',
        'order'   => 'DESC',
    ) );
}

function ml_count_user_tours( $user_id ) {
    return count( ml_get_user_tours( $user_id ) );
}

/**
 * Flag all of a user's tours pending_archive (called on subscription end).
 */
function ml_flag_user_tours_pending_archive( $user_id ) {
    $tours = ml_get_user_tours( $user_id );
    $flagged = array();
    foreach ( $tours as $t ) {
        if ( get_post_meta( $t->ID, ML_META_TOUR_STATUS, true ) === ML_TOUR_STATUS_ACTIVE ) {
            update_post_meta( $t->ID, ML_META_TOUR_STATUS, ML_TOUR_STATUS_PENDING_ARCHIVE );
            $flagged[] = $t->ID;
        }
    }
    return $flagged;
}
