<?php
/**
 * Assigns mk_occasion terms to products from data/occasion-map.csv.
 *
 * The map was generated from the old site's categories, which mixed product
 * type with occasion. Rows whose basis says PROPOSED are inferences, not
 * Melinda's own classification: she should review the CSV (it opens in any
 * spreadsheet) before this is applied on the live store.
 *
 * After launch she manages occasions herself in Products > Occasions and on
 * each product. This script is for the initial assignment only.
 *
 * Usage, from the WordPress root as the site user:
 *   wp eval-file ~/melinda-ops/scripts/assign-occasions.php          # report
 *   wp eval-file ~/melinda-ops/scripts/assign-occasions.php apply    # write
 *
 * Products that already have occasions are skipped unless "force" is given,
 * so re-running never overwrites her own choices.
 *
 * @package MK_Core
 */

defined( 'ABSPATH' ) || die( 'Run via wp eval-file.' );

$apply = in_array( 'apply', (array) ( $args ?? array() ), true );
$force = in_array( 'force', (array) ( $args ?? array() ), true );
$tax   = 'mk_occasion';

if ( ! taxonomy_exists( $tax ) ) {
	WP_CLI::error( 'mk_occasion taxonomy not registered. Is mk-core active?' );
}

$path = dirname( __DIR__ ) . '/data/occasion-map.csv';
if ( ! is_readable( $path ) ) {
	WP_CLI::error( "Not found: $path" );
}

function mk_ao_norm( $title ) {
	$title = html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$title = str_replace( array( '’', '‘' ), "'", $title );
	return mb_strtolower( trim( preg_replace( '/\s+/u', ' ', $title ) ) );
}

$index = array();
foreach ( wc_get_products( array( 'status' => array( 'publish', 'draft', 'private' ), 'limit' => -1 ) ) as $p ) {
	$index[ mk_ao_norm( $p->get_name() ) ][] = $p;
}

$fh     = fopen( $path, 'r' );
$header = fgetcsv( $fh, 0, ',', '"', '' );
$col    = array_flip( $header );

WP_CLI::log( $apply ? 'APPLYING' : 'REPORT ONLY (add "apply" to write)' );
WP_CLI::log( '' );

$stats   = array( 'assigned' => 0, 'skipped' => 0, 'same' => 0 );
$missing = array();
$created = array();

while ( ( $row = fgetcsv( $fh, 0, ',', '"', '' ) ) !== false ) {
	$title = $row[ $col['product'] ];
	$names = array_filter( array_map( 'trim', explode( ';', (string) $row[ $col['occasions'] ] ) ) );

	if ( ! $names ) {
		continue;
	}

	$products = $index[ mk_ao_norm( $title ) ] ?? array();
	if ( ! $products ) {
		$missing[] = $title;
		continue;
	}

	$term_ids = array();
	foreach ( $names as $name ) {
		$term = term_exists( $name, $tax );
		if ( ! $term && $apply ) {
			$term = wp_insert_term( $name, $tax );
			if ( is_wp_error( $term ) ) {
				WP_CLI::warning( "Could not create occasion \"$name\": " . $term->get_error_message() );
				continue;
			}
			$created[ $name ] = true;
		} elseif ( ! $term ) {
			$created[ $name ] = true;
			continue;
		}
		$term_ids[] = (int) $term['term_id'];
	}

	foreach ( $products as $p ) {
		$current = wp_get_object_terms( $p->get_id(), $tax, array( 'fields' => 'ids' ) );
		$current = is_wp_error( $current ) ? array() : array_map( 'intval', $current );
		sort( $current );
		$want = $term_ids;
		sort( $want );

		if ( $apply && $current === $want && $want ) {
			$stats['same']++;
			continue;
		}
		if ( $current && ! $force ) {
			$stats['skipped']++;
			WP_CLI::log( sprintf( '  !  #%-5d %-45s already has occasions, skipped', $p->get_id(), mb_substr( $p->get_name(), 0, 45 ) ) );
			continue;
		}

		WP_CLI::log( sprintf( '  +  #%-5d %-45s %s', $p->get_id(), mb_substr( $p->get_name(), 0, 45 ), implode( ', ', $names ) ) );
		$stats['assigned']++;
		if ( $apply ) {
			wp_set_object_terms( $p->get_id(), $term_ids, $tax, false );
		}
	}
}
fclose( $fh );

WP_CLI::log( '' );
if ( $created ) {
	WP_CLI::log( ( $apply ? 'Created' : 'Will create' ) . ' occasions: ' . implode( ', ', array_keys( $created ) ) );
}
if ( $missing ) {
	WP_CLI::warning( 'No product matched: ' . implode( '; ', $missing ) );
}
WP_CLI::log( sprintf( '%s %d, skipped %d, already correct %d.', $apply ? 'Assigned' : 'Would assign', $stats['assigned'], $stats['skipped'], $stats['same'] ) );

if ( $apply ) {
	wp_cache_flush();
	WP_CLI::success( 'Done. The shop occasion dropdown only lists occasions with published products.' );
}
