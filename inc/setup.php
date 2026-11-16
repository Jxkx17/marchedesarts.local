<?php
/**
 * Configuration du thème : supports, menus, styles et polices.
 *
 * @package MarcheDesArts
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

function mda_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    register_nav_menus( array(
        'primary' => 'Menu principal',
    ) );
}
add_action( 'after_setup_theme', 'mda_setup' );

function mda_enqueue_assets() {
    wp_enqueue_style( 'mda-style', get_stylesheet_uri(), array(), filemtime( get_stylesheet_directory() . '/style.css' ) );
    wp_enqueue_style( 'mda-google-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Poppins:wght@400;600&display=swap', array(), null );
}
add_action( 'wp_enqueue_scripts', 'mda_enqueue_assets' );
