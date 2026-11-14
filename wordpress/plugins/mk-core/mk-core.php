<?php
/**
 * Plugin Name: Melinda's Kitchen Core
 * Description: Business logic for Melinda's Kitchen: occasion taxonomy, checkout localisation, order-flow rules. Lives outside the theme so a future redesign does not take the functionality with it.
 * Version:     1.0.0
 * Author:      Operandi
 * Requires PHP: 8.2
 * Text Domain: mk-core
 *
 * @package MK_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'MK_CORE_VERSION', '1.0.0' );
define( 'MK_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once MK_CORE_DIR . 'includes/class-taxonomies.php';
require_once MK_CORE_DIR . 'includes/class-checkout.php';

add_action( 'plugins_loaded', 'mk_core_init' );
function mk_core_init() {
	MK_Core\Taxonomies::init();

	if ( class_exists( 'WooCommerce' ) ) {
		MK_Core\Checkout::init();
	}
}

/**
 * Flush rewrites once on activation so the occasion archives resolve.
 */
register_activation_hook( __FILE__, 'mk_core_activate' );
function mk_core_activate() {
	require_once MK_CORE_DIR . 'includes/class-taxonomies.php';
	MK_Core\Taxonomies::register();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
