<?php
/**
 * Restores WooCommerce Product Add-Ons configuration onto the imported
 * products, matched by product title.
 *
 * Usage, from the WordPress root as the site user:
 *   wp eval-file ~/melinda-ops/scripts/import-addons.php          # report only
 *   wp eval-file ~/melinda-ops/scripts/import-addons.php apply    # write
 *   wp eval-file ~/melinda-ops/scripts/import-addons.php apply force
 *       also overwrite products that already have a different config
 *
 * Requires WooCommerce Product Add-Ons installed and active.
 *
 * Safe to re-run. It only writes _product_addons and
 * _product_addons_exclude_global on matched products. A product that already
 * has a different add-on configuration (for example one Melinda edited in
 * admin) is skipped unless "force" is given; with "force" the old value is
 * copied to _mk_product_addons_backup first.
 *
 * Titles are compared after normalising entities, curly quotes and spacing:
 * WordPress may store "Mother's Day Cake & Flowers" as "Mother’s Day Cake
 * &amp; Flowers", which an exact match would silently miss.
 *
 * @package MK_Core
 */

defined( 'ABSPATH' ) || die( 'Run via wp eval-file.' );

$apply = in_array( 'apply', (array) ( $args ?? array() ), true );
$force = in_array( 'force', (array) ( $args ?? array() ), true );

if ( ! function_exists( 'wc_get_products' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}
if ( ! class_exists( 'WC_Product_Addons' ) && ! defined( 'WC_PRODUCT_ADDONS_VERSION' ) ) {
	WP_CLI::error( 'WooCommerce Product Add-Ons is not active. Install and activate it first: the data is useless without it.' );
}

$json = null;
foreach ( array( __DIR__ . '/product-addons.json', dirname( __DIR__ ) . '/data/product-addons.json' ) as $candidate ) {
	if ( is_readable( $candidate ) ) {
		$json = $candidate;
		break;
	}
}
if ( ! $json ) {
	WP_CLI::error( 'product-addons.json not found next to this script or in ../data/.' );
}

$data = json_decode( (string) file_get_contents( $json ), true );
if ( ! is_array( $data ) ) {
	WP_CLI::error( 'Could not parse ' . $json . ': ' . json_last_error_msg() );
}

function mk_ai_norm( $title ) {
	$title = html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$title = str_replace( array( '’', '‘', '`' ), "'", $title );
	$title = str_replace( array( '“', '”' ), '"', $title );
	return mb_strtolower( trim( preg_replace( '/\s+/u', ' ', $title ) ) );
}

/**
 * The plugin expects PHP-style 0..n integer keys on lists.
 */
function mk_ai_relist( $value ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}
	$out = array();
	foreach ( $value as $k => $v ) {
		$out[ is_numeric( $k ) ? (int) $k : $k ] = mk_ai_relist( $v );
	}
	return $out;
}

/**
 * Returns a list of problems with one add-on group, empty if it is fine.
 */
function mk_ai_validate( array $group ) {
	$errors = array();
	foreach ( array( 'name', 'type' ) as $required ) {
		if ( empty( $group[ $required ] ) ) {
			$errors[] = "missing '$required'";
		}
	}
	$known_types = array( 'multiple_choice', 'checkbox', 'custom_text', 'custom_textarea', 'file_upload', 'custom_price', 'input_multiplier', 'heading', 'datepicker' );
	if ( ! empty( $group['type'] ) && ! in_array( $group['type'], $known_types, true ) ) {
		$errors[] = "unknown type '{$group['type']}'";
	}
	foreach ( (array) ( $group['options'] ?? array() ) as $i => $opt ) {
		if ( '' !== (string) ( $opt['price'] ?? '' ) && ! is_numeric( $opt['price'] ) ) {
			$errors[] = "option $i has non-numeric price '{$opt['price']}'";
		}
		if ( ! in_array( $opt['price_type'] ?? 'flat_fee', array( 'flat_fee', 'percentage_based', 'quantity_based' ), true ) ) {
			$errors[] = "option $i has unknown price_type";
		}
	}
	return $errors;
}

// Index every product by normalised title. A title can match more than one
// product (e.g. "Pearl Symphony" exists as published and private): all get it.
$index = array();
foreach ( wc_get_products( array( 'status' => array( 'publish', 'draft', 'pending', 'private' ), 'limit' => -1 ) ) as $p ) {
	$index[ mk_ai_norm( $p->get_name() ) ][] = $p;
}

WP_CLI::log( $apply ? 'WRITING ADD-ONS' : 'REPORT ONLY (add "apply" to write)' );
WP_CLI::log( 'Source: ' . $json );
WP_CLI::log( '' );

$stats   = array( 'products' => 0, 'groups' => 0, 'options' => 0, 'unchanged' => 0, 'backed_up' => 0, 'skipped' => 0 );
$missing = array();
$invalid = array();

foreach ( $data as $title => $addons ) {
	$clean = mk_ai_relist( $addons );

	foreach ( $clean as $gi => $group ) {
		foreach ( mk_ai_validate( (array) $group ) as $err ) {
			$invalid[] = sprintf( '%s / group %d (%s): %s', $title, $gi, $group['name'] ?? '?', $err );
		}
	}

	$matches = $index[ mk_ai_norm( $title ) ] ?? array();
	if ( ! $matches ) {
		$missing[] = $title;
		continue;
	}

	$options = array_sum( array_map( static fn( $g ) => count( (array) ( $g['options'] ?? array() ) ), $clean ) );

	foreach ( $matches as $product ) {
		$current = $product->get_meta( '_product_addons', true );

		if ( $current === $clean ) {
			$stats['unchanged']++;
			WP_CLI::log( sprintf( '  =  #%-5d %-46s already up to date', $product->get_id(), mb_substr( $title, 0, 46 ) ) );
			continue;
		}

		if ( $current && ! $force ) {
			$stats['skipped']++;
			WP_CLI::log( sprintf( '  !  #%-5d %-46s has a different config, skipped (use "force")', $product->get_id(), mb_substr( $title, 0, 46 ) ) );
			continue;
		}

		WP_CLI::log( sprintf( '  +  #%-5d %-46s %2d groups %3d options%s', $product->get_id(), mb_substr( $title, 0, 46 ), count( $clean ), $options, $current ? '  (replaces existing, backed up)' : '' ) );

		$stats['products']++;
		$stats['groups']  += count( $clean );
		$stats['options'] += $options;

		if ( $apply ) {
			if ( $current ) {
				$product->update_meta_data( '_mk_product_addons_backup', $current );
				$stats['backed_up']++;
			}
			$product->update_meta_data( '_product_addons', $clean );
			$product->update_meta_data( '_product_addons_exclude_global', 0 );
			$product->save();
		}
	}
}

WP_CLI::log( '' );

if ( $invalid ) {
	WP_CLI::warning( 'Validation problems in the source data (written as-is, check in admin):' );
	foreach ( $invalid as $i ) {
		WP_CLI::log( '  - ' . $i );
	}
}

if ( $missing ) {
	WP_CLI::warning( 'No product matched these titles:' );
	foreach ( $missing as $m ) {
		WP_CLI::log( '  - ' . $m );
	}
}

WP_CLI::log(
	sprintf(
		'%s %d products, %d groups, %d options. %d already up to date. %d skipped. %d existing configs backed up.',
		$apply ? 'Wrote' : 'Would write',
		$stats['products'],
		$stats['groups'],
		$stats['options'],
		$stats['unchanged'],
		$stats['skipped'],
		$stats['backed_up']
	)
);

if ( $apply ) {
	wc_delete_product_transients();
	wp_cache_flush();
	WP_CLI::success( 'Done. Spot-check a product page: Simplistic Wedding Cake should show a required Flavour dropdown.' );
}
