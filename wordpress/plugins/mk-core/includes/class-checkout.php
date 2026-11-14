<?php
/**
 * Checkout rules and South African localisation.
 *
 * @package MK_Core
 */

namespace MK_Core;

defined( 'ABSPATH' ) || exit;

class Checkout {

	public static function init() {
		add_filter( 'woocommerce_order_button_text', array( __CLASS__, 'order_button_text' ) );
		add_filter( 'woocommerce_get_country_locale', array( __CLASS__, 'za_locale' ) );
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'address_field_labels' ) );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'checkout_fields' ) );
	}

	/**
	 * Carried over from the old site, which set this with a loose filter in
	 * the theme's functions.php.
	 */
	public static function order_button_text() {
		return __( 'Place order', 'mk-core' );
	}

	/**
	 * Woo ships "Province" for ZA but the old build showed the generic
	 * "State / County" and "Postcode / ZIP" because the locale was not
	 * applied. Pin it.
	 */
	public static function za_locale( $locale ) {
		$locale['ZA']['state']['label']    = __( 'Province', 'mk-core' );
		$locale['ZA']['postcode']['label'] = __( 'Postal code', 'mk-core' );
		$locale['ZA']['city']['label']     = __( 'Town or city', 'mk-core' );
		return $locale;
	}

	public static function address_field_labels( $fields ) {
		if ( isset( $fields['state'] ) ) {
			$fields['state']['label'] = __( 'Province', 'mk-core' );
		}
		if ( isset( $fields['postcode'] ) ) {
			$fields['postcode']['label'] = __( 'Postal code', 'mk-core' );
		}
		if ( isset( $fields['address_2'] ) ) {
			$fields['address_2']['placeholder'] = __( 'Complex, unit or suburb (optional)', 'mk-core' );
		}
		return $fields;
	}

	/**
	 * Phone is not optional for a made-to-order bakery: every order needs a
	 * delivery confirmation call.
	 */
	public static function checkout_fields( $fields ) {
		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['required'] = true;
			$fields['billing']['billing_phone']['label']    = __( 'Mobile number', 'mk-core' );
		}
		return $fields;
	}
}
