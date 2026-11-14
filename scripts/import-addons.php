<?php
/**
 * Restores WooCommerce Product Add-Ons configuration onto the imported
 * products, matched by product title.
 *
 * Usage, from the WordPress root as the site user:
 *   wp eval-file import-addons.php
 *
 * Safe to re-run: it overwrites _product_addons for matched products only
 * and never touches anything else.
 */

defined( 'ABSPATH' ) || die( 'Run via wp eval-file.' );

$json = __DIR__ . '/product-addons.json';
if ( ! file_exists( $json ) ) {
	WP_CLI::error( 'product-addons.json not found next to this script.' );
}

$data = json_decode( (string) file_get_contents( $json ), true );
if ( ! is_array( $data ) ) {
	WP_CLI::error( 'Could not parse product-addons.json.' );
}

/**
 * php-style numeric arrays are what the plugin expects, so re-key any
 * JSON lists back to 0..n integer keys.
 */
function mk_relist( $value ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}
	$out = array();
	foreach ( $value as $k => $v ) {
		$out[ is_numeric( $k ) ? (int) $k : $k ] = mk_relist( $v );
	}
	return $out;
}

$matched = 0;
$missing = array();
$groups  = 0;

foreach ( $data as $title => $addons ) {
	$posts = get_posts( array(
		'post_type'        => 'product',
		'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
		'title'            => $title,
		'posts_per_page'   => 1,
		'fields'           => 'ids',
		'suppress_filters' => false,
	) );

	if ( ! $posts ) {
		$missing[] = $title;
		continue;
	}

	$clean = mk_relist( $addons );
	update_post_meta( $posts[0], '_product_addons', $clean );
	update_post_meta( $posts[0], '_product_addons_exclude_global', 0 );

	$matched++;
	$groups += count( $clean );
	WP_CLI::log( sprintf( '  %-46s %2d groups', mb_substr( $title, 0, 46 ), count( $clean ) ) );
}

WP_CLI::log( '' );
WP_CLI::success( sprintf( '%d products updated, %d add-on groups restored.', $matched, $groups ) );

if ( $missing ) {
	WP_CLI::warning( 'No product matched these titles:' );
	foreach ( $missing as $m ) {
		WP_CLI::log( '  - ' . $m );
	}
}
