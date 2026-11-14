<?php
/**
 * Melinda's Kitchen theme bootstrap.
 *
 * @package MK
 */

defined( 'ABSPATH' ) || exit;

define( 'MK_VERSION', '1.0.0' );
define( 'MK_DIR', get_template_directory() );
define( 'MK_URI', get_template_directory_uri() );

require_once MK_DIR . '/inc/setup.php';
require_once MK_DIR . '/inc/assets.php';
require_once MK_DIR . '/inc/template-tags.php';
require_once MK_DIR . '/inc/customizer.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once MK_DIR . '/inc/woocommerce.php';
}
