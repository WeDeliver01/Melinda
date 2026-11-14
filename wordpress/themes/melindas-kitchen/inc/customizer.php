<?php
/**
 * Customizer: only the handful of settings Melinda should be able to change
 * herself. Everything else is fixed in the theme on purpose.
 *
 * @package MK
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', 'mk_customize_register' );
function mk_customize_register( $wp_customize ) {

	$wp_customize->add_section( 'mk_home', array(
		'title'    => __( 'Homepage', 'mk' ),
		'priority' => 25,
	) );

	$wp_customize->add_setting( 'mk_hero_image', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	) );

	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'mk_hero_image', array(
		'label'       => __( 'Hero image', 'mk' ),
		'description' => __( 'Landscape, at least 1800px wide. The title sits over the lower left, so leave that area uncluttered.', 'mk' ),
		'section'     => 'mk_home',
		'mime_type'   => 'image',
	) ) );
}
