<?php
/**
 * Plugin Name: Melinda's Kitchen Staging Guard
 * Description: Stops a staging copy from emailing customers, taking real payments or being indexed. Only active when WP_ENVIRONMENT_TYPE is not "production". Install as an mu-plugin on staging only.
 * Version:     1.0.0
 * Author:      Operandi
 *
 * Configure in wp-config.php:
 *   MK_STAGING_MAIL_TO          Redirect every outgoing email to this address.
 *                               If unset, outgoing email is blocked entirely.
 *   MK_STAGING_ALLOW_GATEWAYS   Comma-separated payment gateway IDs to allow,
 *                               e.g. 'paystack'. Only list a gateway after it
 *                               is switched to sandbox / test mode.
 *
 * @package MK_Core
 */

defined( 'ABSPATH' ) || exit;

if ( 'production' === wp_get_environment_type() ) {
	return;
}

/**
 * Mail: redirect to one inbox, or block. Customer addresses in a cloned
 * database must never receive staging order emails.
 */
add_filter(
	'pre_wp_mail',
	function ( $return, $atts ) {
		if ( defined( 'MK_STAGING_MAIL_TO' ) && is_email( MK_STAGING_MAIL_TO ) ) {
			return $return;
		}
		error_log( '[mk-staging-guard] blocked mail: ' . wp_strip_all_tags( (string) ( $atts['subject'] ?? '' ) ) );
		return true; // Report success so checkout does not error.
	},
	1,
	2
);

add_filter(
	'wp_mail',
	function ( $atts ) {
		if ( ! defined( 'MK_STAGING_MAIL_TO' ) || ! is_email( MK_STAGING_MAIL_TO ) ) {
			return $atts;
		}
		$original        = is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : (string) $atts['to'];
		$atts['subject'] = '[STAGING to ' . $original . '] ' . $atts['subject'];
		$atts['to']      = MK_STAGING_MAIL_TO;

		// Drop Cc/Bcc headers so copies cannot leak to real recipients.
		$headers = is_array( $atts['headers'] ) ? $atts['headers'] : explode( "\n", str_replace( "\r\n", "\n", (string) $atts['headers'] ) );
		$atts['headers'] = array_values(
			array_filter(
				$headers,
				static fn( $h ) => ! preg_match( '/^\s*(cc|bcc)\s*:/i', (string) $h )
			)
		);
		return $atts;
	},
	PHP_INT_MAX
);

/**
 * Payments: offline methods only, plus any gateway explicitly allowed.
 */
add_filter(
	'woocommerce_available_payment_gateways',
	function ( $gateways ) {
		$allowed = array( 'cod', 'bacs', 'cheque' );
		if ( defined( 'MK_STAGING_ALLOW_GATEWAYS' ) ) {
			$allowed = array_merge( $allowed, array_map( 'trim', explode( ',', (string) MK_STAGING_ALLOW_GATEWAYS ) ) );
		}
		return array_intersect_key( (array) $gateways, array_flip( $allowed ) );
	},
	PHP_INT_MAX
);

/**
 * Indexing: belt and braces on top of blog_public = 0.
 */
add_action(
	'send_headers',
	function () {
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}
);
add_filter( 'pre_option_blog_public', static fn() => '0' );

/**
 * Visible marker in the admin bar so nobody mistakes staging for live.
 */
add_action(
	'admin_bar_menu',
	function ( $bar ) {
		$bar->add_node(
			array(
				'id'    => 'mk-staging',
				'title' => 'STAGING',
				'meta'  => array( 'html' => '<style>#wp-admin-bar-mk-staging>.ab-item{background:#B69576!important;color:#fff!important;font-weight:600}</style>' ),
			)
		);
	},
	1
);
