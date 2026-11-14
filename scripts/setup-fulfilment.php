<?php
/**
 * Configures collection and delivery on a site running mk-core 1.1+.
 *
 *   1. Native WooCommerce Local Pickup (the block-checkout one, which also
 *      works through the Store API for the headless build) with the studio
 *      as the single pickup location.
 *   2. Phone field set to required on the block checkout (mk-core enforces it
 *      on the classic checkout and the Store API).
 *   3. "Delivery only" flag on wedding cakes. The FAQ says: "All wedding cakes
 *      are delivered by Melinda's Kitchen." Sample boxes are excluded because
 *      they are not cakes for the wedding itself. Melinda can change any
 *      product's flag in Product data > Shipping.
 *   4. Default collection-date settings (5 days lead time, 270-day window), only
 *      if not already set, so her edits are never overwritten.
 *
 * Usage, from the WordPress root as the site user:
 *   wp eval-file ~/melinda-ops/scripts/setup-fulfilment.php                       # report
 *   wp eval-file ~/melinda-ops/scripts/setup-fulfilment.php apply address="<street>"
 *
 * The street address is passed in rather than committed: the studio is
 * Melinda's home. Faerie Glen, Pretoria, 0081, Gauteng are filled in.
 *
 * Delicate Courier is NOT configured here: its plugin is installed and set up
 * from the Commit 1 build. See docs/runbooks/fulfilment.md.
 *
 * @package MK_Core
 */

defined( 'ABSPATH' ) || die( 'Run via wp eval-file.' );

$apply = false;
$opts  = array();
foreach ( (array) ( $args ?? array() ) as $a ) {
	if ( 'apply' === $a ) {
		$apply = true;
	} elseif ( str_contains( $a, '=' ) ) {
		list( $k, $v ) = explode( '=', $a, 2 );
		$opts[ $k ]    = $v;
	}
}

if ( ! class_exists( '\MK_Core\Fulfilment' ) ) {
	WP_CLI::error( 'mk-core 1.1+ is not active. Run scripts/deploy.sh first.' );
}

WP_CLI::log( $apply ? 'APPLYING' : 'REPORT ONLY (add "apply" to make changes)' );

/*
 * 1. Local Pickup (blocks).
 */
WP_CLI::log( '' );
WP_CLI::log( '1. Local Pickup' );

$pickup_settings = get_option( 'woocommerce_pickup_location_settings', array() );
$locations       = get_option( 'pickup_location_pickup_locations', array() );

WP_CLI::log( '   currently: ' . ( 'yes' === ( $pickup_settings['enabled'] ?? 'no' ) ? 'enabled' : 'disabled' ) . ', ' . count( (array) $locations ) . ' location(s)' );

if ( $apply ) {
	if ( empty( $opts['address'] ) && ! $locations ) {
		WP_CLI::warning( '   No address="..." given and no location exists. Skipping pickup setup.' );
	} else {
		update_option(
			'woocommerce_pickup_location_settings',
			array_merge(
				(array) $pickup_settings,
				array(
					'enabled'    => 'yes',
					'title'      => __( 'Collect from our studio', 'mk-core' ),
					'tax_status' => 'none',
					'cost'       => '',
				)
			)
		);

		if ( ! empty( $opts['address'] ) ) {
			$studio = array(
				'name'    => "Melinda's Kitchen studio",
				'address' => array(
					'address_1' => sanitize_text_field( $opts['address'] ),
					'city'      => 'Pretoria',
					'state'     => 'GP',
					'postcode'  => '0081',
					'country'   => 'ZA',
				),
				'details' => 'Faerie Glen. Tuesday to Saturday. Your collection time is confirmed by phone.',
				'enabled' => true,
			);
			update_option( 'pickup_location_pickup_locations', array( $studio ) );
		}
		WP_CLI::log( '   enabled, studio location set' );
	}
}

/*
 * 2. Phone required on the block checkout.
 */
WP_CLI::log( '' );
WP_CLI::log( '2. Phone field on block checkout: currently ' . get_option( 'woocommerce_checkout_phone_field', 'optional' ) );
if ( $apply ) {
	update_option( 'woocommerce_checkout_phone_field', 'required' );
	WP_CLI::log( '   set to required' );
}

/*
 * 3. Delivery-only flag on wedding cakes.
 */
WP_CLI::log( '' );
WP_CLI::log( '3. Delivery only (Wedding Cakes category, excluding sample boxes)' );
$wedding = wc_get_products(
	array(
		'category' => array( 'wedding-cakes' ),
		'status'   => array( 'publish', 'draft', 'private' ),
		'limit'    => -1,
	)
);
if ( ! $wedding ) {
	WP_CLI::warning( '   No products in category slug "wedding-cakes". Check the slug in Products > Categories.' );
}
foreach ( $wedding as $p ) {
	$sample = (bool) preg_match( '/sample/i', $p->get_name() );
	$want   = $sample ? 'no' : 'yes';
	$have   = $p->get_meta( \MK_Core\Fulfilment::DELIVERY_ONLY ) ?: 'no';
	WP_CLI::log( sprintf( '   %s #%-5d %s', 'yes' === $want ? 'DELIVERY ONLY' : 'collectable  ', $p->get_id(), $p->get_name() ) );
	if ( $apply && $have !== $want ) {
		$p->update_meta_data( \MK_Core\Fulfilment::DELIVERY_ONLY, $want );
		$p->save();
	}
}

/*
 * 4. Date settings defaults.
 */
WP_CLI::log( '' );
WP_CLI::log( '4. Date settings' );
foreach ( array( 'mk_lead_days' => 5, 'mk_window_days' => 270 ) as $key => $default ) {
	$current = get_option( $key, null );
	WP_CLI::log( sprintf( '   %-15s %s', $key, null === $current ? "unset, will set $default" : "already $current, left alone" ) );
	if ( $apply && null === $current ) {
		update_option( $key, $default );
	}
}

$next = \MK_Core\Fulfilment::rules()->available_dates();
WP_CLI::log( '   next available dates: ' . implode( ', ', array_slice( $next, 0, 5 ) ) );

WP_CLI::log( '' );
if ( $apply ) {
	wp_cache_flush();
	WP_CLI::success( 'Done. Test: add a wedding cake to cart, collection should not be offered.' );
} else {
	WP_CLI::log( 'Re-run with apply address="<street address>" to make these changes.' );
}
