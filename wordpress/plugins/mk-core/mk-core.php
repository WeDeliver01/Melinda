<?php
/**
 * Plugin Name: Melinda's Kitchen Core
 * Description: Business logic for Melinda's Kitchen: occasion taxonomy, checkout localisation, order-flow rules. Lives outside the theme so a future redesign does not take the functionality with it.
 * Version:     1.1.0
 * Author:      Operandi
 * Requires PHP: 8.2
 * Text Domain: mk-core
 * Requires Plugins: woocommerce
 * WC requires at least: 9.4
 *
 * @package MK_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'MK_CORE_VERSION', '1.1.0' );
define( 'MK_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once MK_CORE_DIR . 'includes/class-taxonomies.php';
require_once MK_CORE_DIR . 'includes/class-checkout.php';
require_once MK_CORE_DIR . 'includes/class-fulfilment-rules.php';
require_once MK_CORE_DIR . 'includes/class-fulfilment.php';

/**
 * mk-core only touches orders through WooCommerce CRUD, and its checkout
 * field uses the Additional Checkout Fields API, so it is compatible with
 * both HPOS and the Cart/Checkout blocks.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action( 'plugins_loaded', 'mk_core_init' );
function mk_core_init() {
	MK_Core\Taxonomies::init();

	if ( class_exists( 'WooCommerce' ) ) {
		MK_Core\Checkout::init();
		MK_Core\Fulfilment::init();
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
