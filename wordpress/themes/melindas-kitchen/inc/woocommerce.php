<?php
/**
 * WooCommerce presentation changes.
 *
 * Presentation only. Business rules belong in the mk-core plugin so they
 * survive a future theme change.
 *
 * @package MK
 */

defined( 'ABSPATH' ) || exit;

/**
 * Strip Woo's default wrappers. Our own templates supply the markup.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

/**
 * Archive cards show image and title only.
 *
 * This reproduces the current site, where price, rating and add-to-cart are
 * all hidden on archives so products read as a lookbook. It is a brand
 * decision, not an oversight: made-to-order cakes are enquiries, not
 * impulse buys.
 */
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

/**
 * Four across on desktop, two on mobile. Handled in CSS grid, so tell Woo
 * not to inject its own column classes.
 */
add_filter( 'loop_shop_columns', 'mk_loop_columns' );
function mk_loop_columns() {
	return 4;
}

add_filter( 'loop_shop_per_page', 'mk_products_per_page' );
function mk_products_per_page() {
	return 12;
}

/**
 * Use our portrait crop everywhere Woo asks for a thumbnail.
 */
add_filter( 'woocommerce_gallery_thumbnail_size', 'mk_gallery_thumb_size' );
function mk_gallery_thumb_size() {
	return 'mk-card';
}

/**
 * Product tabs were hidden site-wide on the old build with display:none,
 * which kept the markup and the accessibility tree. Remove them properly.
 * Delete this block if Melinda wants descriptions shown.
 */
add_filter( 'woocommerce_product_tabs', 'mk_remove_product_tabs', 98 );
function mk_remove_product_tabs( $tabs ) {
	unset( $tabs['description'], $tabs['additional_information'], $tabs['reviews'] );
	return $tabs;
}

/**
 * Cart fragment for the header count, so Alpine can update it without a
 * page reload.
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'mk_cart_count_fragment' );
function mk_cart_count_fragment( $fragments ) {
	ob_start();
	mk_cart_count_markup();
	$fragments['.js-cart-count'] = ob_get_clean();
	return $fragments;
}

function mk_cart_count_markup() {
	$count = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	printf(
		'<span class="js-cart-count cart-count%s">%s</span>',
		$count ? '' : ' is-empty',
		esc_html( (string) $count )
	);
}

/**
 * Add Total's row classes to the product list so the grid classes on each
 * item resolve, and drop Woo's own columns-N class which fights them.
 */
add_filter( 'woocommerce_product_loop_start', 'mk_product_loop_start' );
function mk_product_loop_start( $html ) {
	return '<ul class="products wpex-row wpex-clr">';
}

/**
 * Drop Woo's float-based grid stylesheet. The theme lays the grid out with
 * CSS grid, and the two fight each other.
 */
add_filter( 'woocommerce_enqueue_styles', 'mk_trim_woo_styles' );
function mk_trim_woo_styles( $styles ) {
	unset( $styles['woocommerce-layout'], $styles['woocommerce-smallscreen'] );
	return $styles;
}

/**
 * Serve a large enough file for the card. Woo's thumbnail is 300px, which
 * is soft on a 2x display in a four-across grid.
 */
add_filter( 'woocommerce_get_image_size_thumbnail', 'mk_card_image_size' );
function mk_card_image_size( $size ) {
	$size['width']  = 600;
	$size['height'] = 600;
	$size['crop']   = 1;
	return $size;
}
