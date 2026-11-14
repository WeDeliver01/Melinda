<?php
/**
 * Reconciles the shop against data/melindas-products.csv.
 *
 * Diagnosis (14 Nov 2026): the shop shows 39 products against 29 published in
 * the source. The CSV holds 29 published, 10 draft and 1 private parent
 * products. 29 + 10 = 39, and every "(Copy)" title in the shop is one of the
 * 10 drafts. The likeliest cause is that the drafts were published on import,
 * not that the CSV was imported twice. This script handles both:
 *
 *   1. STATUS  Products whose status differs from the source are set back to
 *              the source status (publish / draft / private).
 *   2. REPEATS Where a title+type exists more times than the CSV has it, the
 *              best-populated copy is kept and the rest are moved to trash.
 *              Trash only, never force-deleted: restore from Products > Trash.
 *   3. EXTRAS  Products in the shop that are not in the CSV are reported only.
 *
 * Usage, from the WordPress root as the site user:
 *   wp eval-file ~/melinda-ops/scripts/reconcile-products.php            # report only
 *   wp eval-file ~/melinda-ops/scripts/reconcile-products.php apply      # make changes
 *
 * Take a backup before "apply":  wp db export ~/pre-reconcile.sql
 *
 * @package MK_Core
 */

defined( 'ABSPATH' ) || die( 'Run via wp eval-file.' );

if ( ! function_exists( 'wc_get_products' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

$apply = in_array( 'apply', (array) ( $args ?? array() ), true );

$csv_path = dirname( __DIR__ ) . '/data/melindas-products.csv';
if ( ! is_readable( $csv_path ) ) {
	WP_CLI::error( "CSV not found at $csv_path. Run scripts/deploy.sh first, or run this from ~/melinda-ops/scripts." );
}

/*
 * Source of truth: parent products from the CSV, keyed by title + type.
 */
$status_map = array(
	'1'  => 'publish',
	'0'  => 'draft',
	'-1' => 'private',
);

$fh     = fopen( $csv_path, 'r' );
$header = fgetcsv( $fh, 0, ',', '"', '' );
$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] ); // Strip BOM.
$col = array_flip( $header );

$source = array(); // key => list of expected statuses.
while ( ( $row = fgetcsv( $fh, 0, ',', '"', '' ) ) !== false ) {
	$type = $row[ $col['Type'] ] ?? '';
	if ( 'variation' === $type || '' === $type ) {
		continue;
	}
	$key              = mk_rp_key( $row[ $col['Name'] ], $type );
	$source[ $key ][] = $status_map[ $row[ $col['Published'] ] ] ?? 'draft';
}
fclose( $fh );

function mk_rp_key( $title, $type ) {
	$title = html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES, 'UTF-8' );
	$title = str_replace( array( '’', '‘' ), "'", $title );
	return mb_strtolower( trim( preg_replace( '/\s+/u', ' ', $title ) ) ) . '|' . $type;
}

/**
 * How complete a product is, so the best copy of a repeat is the one kept.
 */
function mk_rp_score( WC_Product $p ) {
	$score  = $p->get_image_id() ? 10 : 0;
	$score += $p->is_type( 'variable' ) ? count( $p->get_children() ) : 0;
	$score += $p->get_meta( '_product_addons' ) ? 5 : 0;
	if ( taxonomy_exists( 'mk_occasion' ) ) {
		$terms  = wp_get_post_terms( $p->get_id(), 'mk_occasion', array( 'fields' => 'ids' ) );
		$score += ( is_array( $terms ) && $terms ) ? 1 : 0;
	}
	return $score;
}

/*
 * Current shop state.
 */
$site = array(); // key => list of WC_Product.
foreach ( wc_get_products( array( 'status' => array( 'publish', 'draft', 'pending', 'private' ), 'limit' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) ) as $p ) {
	$site[ mk_rp_key( $p->get_name(), $p->get_type() ) ][] = $p;
}

$changes = array( 'status' => 0, 'trash' => 0 );
$extras  = array();

WP_CLI::log( $apply ? 'APPLYING CHANGES' : 'REPORT ONLY (add "apply" to make changes)' );
WP_CLI::log( '' );

foreach ( $site as $key => $products ) {
	if ( ! isset( $source[ $key ] ) ) {
		foreach ( $products as $p ) {
			$extras[] = sprintf( '#%d %s [%s]', $p->get_id(), $p->get_name(), $p->get_status() );
		}
		continue;
	}

	$expected = $source[ $key ];

	// Best-populated first, then oldest.
	usort(
		$products,
		static fn( $a, $b ) => array( mk_rp_score( $b ), $a->get_id() ) <=> array( mk_rp_score( $a ), $b->get_id() )
	);

	$keep   = array_slice( $products, 0, count( $expected ) );
	$repeat = array_slice( $products, count( $expected ) );

	// Prefer matching an already-correct status so we change as little as possible.
	foreach ( $keep as $p ) {
		$i = array_search( $p->get_status(), $expected, true );
		if ( false !== $i ) {
			$want = $expected[ $i ];
			unset( $expected[ $i ] );
		} else {
			$want = array_shift( $expected );
		}
		if ( $p->get_status() !== $want ) {
			WP_CLI::log( sprintf( '  STATUS  #%-5d %-50s %s -> %s', $p->get_id(), mb_substr( $p->get_name(), 0, 50 ), $p->get_status(), $want ) );
			$changes['status']++;
			if ( $apply ) {
				$p->set_status( $want );
				$p->save();
			}
		}
	}

	foreach ( $repeat as $p ) {
		WP_CLI::log( sprintf( '  TRASH   #%-5d %-50s [%s] (repeat, keeping #%d)', $p->get_id(), mb_substr( $p->get_name(), 0, 50 ), $p->get_status(), $keep[0]->get_id() ) );
		$changes['trash']++;
		if ( $apply ) {
			$p->delete( false ); // false = move to trash, variations kept with it.
		}
	}
}

$missing = array_diff_key( $source, $site );

WP_CLI::log( '' );
if ( $extras ) {
	WP_CLI::warning( 'In the shop but not in the CSV (left alone, review by hand):' );
	foreach ( $extras as $e ) {
		WP_CLI::log( '  ' . $e );
	}
}
if ( $missing ) {
	WP_CLI::warning( 'In the CSV but not in the shop:' );
	foreach ( array_keys( $missing ) as $m ) {
		WP_CLI::log( '  ' . str_replace( '|', ' [', $m ) . ']' );
	}
}

$published = count( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'return' => 'ids' ) ) );
WP_CLI::log( '' );
WP_CLI::log( sprintf( 'Status changes: %d   Repeats to trash: %d   Published now: %d (source: 29)', $changes['status'], $changes['trash'], $published ) );

if ( $apply ) {
	wc_delete_product_transients();
	wp_cache_flush();
	WP_CLI::success( 'Done. Trashed products can be restored from Products > Trash.' );
} elseif ( $changes['status'] || $changes['trash'] ) {
	WP_CLI::log( 'Re-run with "apply" to make these changes.' );
} else {
	WP_CLI::success( 'Shop matches the source. Nothing to do.' );
}
