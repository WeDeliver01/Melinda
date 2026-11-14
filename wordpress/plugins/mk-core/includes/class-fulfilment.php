<?php
/**
 * Collection and delivery: the date picker, delivery-only products, and the
 * settings Melinda edits herself.
 *
 * Works on both checkouts:
 *   - Checkout block and Store API (what the headless storefront will use):
 *     WooCommerce Additional Checkout Fields API.
 *   - Classic shortcode checkout: woocommerce_checkout_fields.
 * Both write the same order meta key, so admin, emails and reports see one field.
 *
 * Pickup itself is WooCommerce's native Local Pickup (configured by
 * scripts/setup-fulfilment.sh), not custom code.
 *
 * @package MK_Core
 */

namespace MK_Core;

defined( 'ABSPATH' ) || exit;

class Fulfilment {

	/** Field id for the Additional Checkout Fields API. */
	const FIELD_ID = 'mk-core/fulfilment-date';

	/** Order meta key WooCommerce uses for an "order" location additional field. */
	const META_KEY = '_wc_other/mk-core/fulfilment-date';

	/** Product meta flag: cannot be collected, must be delivered. */
	const DELIVERY_ONLY = '_mk_delivery_only';

	/** Shipping method ids that represent collection. */
	const PICKUP_METHODS = array( 'pickup_location', 'local_pickup' );

	public static function init() {
		// Settings: WooCommerce > Settings > Shipping > Collection & delivery.
		add_filter( 'woocommerce_get_sections_shipping', array( __CLASS__, 'settings_section' ) );
		add_filter( 'woocommerce_get_settings_shipping', array( __CLASS__, 'settings_fields' ), 10, 2 );

		// Date field.
		add_action( 'woocommerce_init', array( __CLASS__, 'register_block_field' ) );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'classic_field' ) );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'classic_validate' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'classic_save' ), 10, 2 );

		// Delivery-only products.
		add_action( 'woocommerce_product_options_shipping_product_data', array( __CLASS__, 'product_field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'product_save' ) );
		add_filter( 'woocommerce_package_rates', array( __CLASS__, 'hide_pickup_for_delivery_only' ), 100, 2 );

		// Phone required on the Store API too (classic is handled in Checkout).
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'store_api_require_phone' ), 10, 2 );
	}

	/*
	 * ------------------------------------------------------------------
	 * Rules
	 * ------------------------------------------------------------------
	 */

	public static function rules(): Fulfilment_Rules {
		$closed = preg_split( '/[\s,]+/', (string) get_option( 'mk_closed_dates', '' ), -1, PREG_SPLIT_NO_EMPTY );
		return new Fulfilment_Rules(
			(int) get_option( 'mk_lead_days', 5 ),
			(int) get_option( 'mk_window_days', 270 ),
			$closed,
			Fulfilment_Rules::DEFAULT_OPEN_DAYS,
			wp_timezone_string()
		);
	}

	private static function label_for( string $ymd ): string {
		$d = \DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, wp_timezone() );
		return $d ? wp_date( 'l j F Y', $d->getTimestamp() ) : $ymd;
	}

	private static function options(): array {
		$out = array();
		foreach ( self::rules()->available_dates() as $ymd ) {
			$out[ $ymd ] = self::label_for( $ymd );
		}
		return $out;
	}

	private static function message_for( string $reason ): string {
		switch ( $reason ) {
			case 'lead_time':
				/* translators: %d: number of days */
				return sprintf( __( 'We need at least %d days to make your order. Please choose a later date, or WhatsApp or call us for shorter notice.', 'mk-core' ), (int) get_option( 'mk_lead_days', 5 ) );
			case 'closed_day':
				return __( 'We collect and deliver Tuesday to Saturday. Please choose another day.', 'mk-core' );
			case 'public_holiday':
				return __( 'We are closed on public holidays. Please choose another day.', 'mk-core' );
			case 'fully_booked':
				return __( 'We are fully booked on that day. Please choose another day.', 'mk-core' );
			case 'too_far':
				return __( 'That date is too far ahead for online orders. Please contact us.', 'mk-core' );
			default:
				return __( 'Please choose a collection or delivery date.', 'mk-core' );
		}
	}

	/*
	 * ------------------------------------------------------------------
	 * Checkout block and Store API
	 * ------------------------------------------------------------------
	 */

	public static function register_block_field() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		$options = array();
		foreach ( self::options() as $value => $label ) {
			$options[] = array(
				'value' => $value,
				'label' => $label,
			);
		}

		woocommerce_register_additional_checkout_field(
			array(
				'id'                => self::FIELD_ID,
				'label'             => __( 'Collection or delivery date', 'mk-core' ),
				'location'          => 'order',
				'type'              => 'select',
				'required'          => true,
				'options'           => $options,
				'validate_callback' => static function ( $value ) {
					$reason = self::rules()->reason_unavailable( (string) $value );
					return null === $reason ? null : new \WP_Error( 'mk_fulfilment_date_' . $reason, self::message_for( $reason ) );
				},
			)
		);
	}

	/**
	 * @param \WC_Order        $order
	 * @param \WP_REST_Request $request
	 */
	public static function store_api_require_phone( $order, $request ) {
		if ( '' === trim( (string) $order->get_billing_phone() ) ) {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				'mk_phone_required',
				esc_html__( 'Please add a mobile number. We call to confirm every order.', 'mk-core' ),
				400
			);
		}
	}

	/*
	 * ------------------------------------------------------------------
	 * Classic checkout
	 * ------------------------------------------------------------------
	 */

	public static function classic_field( $fields ) {
		$fields['order']['mk_fulfilment_date'] = array(
			'type'     => 'select',
			'label'    => __( 'Collection or delivery date', 'mk-core' ),
			'required' => true,
			'options'  => array( '' => __( 'Choose a date', 'mk-core' ) ) + self::options(),
			'priority' => 5,
		);
		return $fields;
	}

	public static function classic_validate( $data, $errors ) {
		// Only validate when our field was actually rendered (a custom checkout
		// that drops it should not become impossible to submit).
		if ( ! isset( $_POST['mk_fulfilment_date'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce.
			return;
		}
		$value  = sanitize_text_field( wp_unslash( $_POST['mk_fulfilment_date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$reason = self::rules()->reason_unavailable( $value );
		if ( null !== $reason ) {
			$errors->add( 'mk_fulfilment_date', self::message_for( '' === $value ? 'invalid' : $reason ) );
		}
	}

	public static function classic_save( $order, $data ) {
		if ( ! empty( $data['mk_fulfilment_date'] ) ) {
			$order->update_meta_data( self::META_KEY, sanitize_text_field( $data['mk_fulfilment_date'] ) );
		}
	}

	/*
	 * ------------------------------------------------------------------
	 * Delivery-only products
	 * ------------------------------------------------------------------
	 */

	public static function product_field() {
		woocommerce_wp_checkbox(
			array(
				'id'          => self::DELIVERY_ONLY,
				'label'       => __( 'Delivery only', 'mk-core' ),
				'description' => __( 'Hide studio collection at checkout when this product is in the cart. Used for wedding cakes, which Melinda\'s Kitchen always delivers.', 'mk-core' ),
			)
		);
	}

	/**
	 * @param \WC_Product $product
	 */
	public static function product_save( $product ) {
		// Nonce and capability are checked by WooCommerce before this action.
		$product->update_meta_data( self::DELIVERY_ONLY, isset( $_POST[ self::DELIVERY_ONLY ] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	public static function is_delivery_only( $product ): bool {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}
		$id = $product->get_parent_id() ?: $product->get_id();
		return 'yes' === get_post_meta( $id, self::DELIVERY_ONLY, true );
	}

	public static function hide_pickup_for_delivery_only( $rates, $package ) {
		foreach ( (array) ( $package['contents'] ?? array() ) as $item ) {
			if ( self::is_delivery_only( $item['data'] ?? null ) ) {
				foreach ( $rates as $id => $rate ) {
					if ( in_array( $rate->get_method_id(), self::PICKUP_METHODS, true ) ) {
						unset( $rates[ $id ] );
					}
				}
				break;
			}
		}
		return $rates;
	}

	/*
	 * ------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------
	 */

	public static function settings_section( $sections ) {
		$sections['mk_fulfilment'] = __( 'Collection & delivery', 'mk-core' );
		return $sections;
	}

	public static function settings_fields( $settings, $section ) {
		if ( 'mk_fulfilment' !== $section ) {
			return $settings;
		}
		return array(
			array(
				'title' => __( 'Collection & delivery dates', 'mk-core' ),
				'type'  => 'title',
				'desc'  => __( 'Customers choose a date at checkout. Tuesday to Saturday only. South African public holidays are closed automatically.', 'mk-core' ),
				'id'    => 'mk_fulfilment',
			),
			array(
				'title'             => __( 'Lead time (days)', 'mk-core' ),
				'id'                => 'mk_lead_days',
				'type'              => 'number',
				'default'           => 5,
				'custom_attributes' => array( 'min' => 0, 'max' => 60 ),
			),
			array(
				'title'             => __( 'Furthest date customers can choose (days ahead)', 'mk-core' ),
				'id'                => 'mk_window_days',
				'type'              => 'number',
				'default'           => 270,
				'custom_attributes' => array( 'min' => 7, 'max' => 400 ),
			),
			array(
				'title'       => __( 'Fully booked or closed dates', 'mk-core' ),
				'id'          => 'mk_closed_dates',
				'type'        => 'textarea',
				'default'     => '',
				'desc'        => __( 'One date per line, as YYYY-MM-DD. These dates disappear from checkout.', 'mk-core' ),
				'desc_tip'    => false,
				'css'         => 'min-height:120px;font-family:monospace',
				'placeholder' => "2026-12-24\n2027-01-02",
			),
			array(
				'type' => 'sectionend',
				'id'   => 'mk_fulfilment',
			),
		);
	}
}
