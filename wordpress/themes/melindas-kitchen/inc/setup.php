<?php
/**
 * Theme supports, menus, image sizes.
 *
 * @package MK
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'mk_setup' );
function mk_setup() {
	load_theme_textdomain( 'mk', MK_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array(
		'height'      => 200,
		'width'       => 600,
		'flex-width'  => true,
		'flex-height' => true,
	) );
	add_theme_support( 'html5', array(
		'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets',
	) );
	add_theme_support( 'responsive-embeds' );

	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus( array(
		'primary' => __( 'Primary menu', 'mk' ),
		'footer'  => __( 'Footer menu', 'mk' ),
	) );

	// Product cards are portrait. Set once here, never in the Customizer.
	add_image_size( 'mk-card', 600, 750, true );
	add_image_size( 'mk-hero', 1800, 1000, true );
}

add_action( 'widgets_init', 'mk_widgets_init' );
function mk_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Footer', 'mk' ),
		'id'            => 'footer',
		'before_widget' => '<div class="footer__widget">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="mk-h3">',
		'after_title'   => '</h3>',
	) );
}

/**
 * Trim the default WordPress head. None of this is used.
 */
add_action( 'init', 'mk_clean_head' );
function mk_clean_head() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
