<?php

// Resgiter Cutom Menus 
function register_my_menus() {
    register_nav_menus(
        array(
            'primary' => __( 'Primary Menu' ),
            'footer' => __( 'Footer Menu'),
        )
    );
}
add_action( 'init', 'register_my_menus' );