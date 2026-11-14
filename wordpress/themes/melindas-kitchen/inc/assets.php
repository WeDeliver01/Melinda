<?php
/**
 * Vite asset loading.
 *
 * Reads dist/.vite/manifest.json and enqueues the hashed production files.
 * When MK_VITE_DEV is defined in wp-config.php, loads from the dev server
 * instead so you get HMR while building. Never define it in production.
 *
 * @package MK
 */

defined( 'ABSPATH' ) || exit;

const MK_VITE_ENTRY = 'src/js/main.js';
const MK_VITE_DEV_URL = 'http://localhost:5173';

add_action( 'wp_enqueue_scripts', 'mk_enqueue_assets' );
function mk_enqueue_assets() {

	if ( defined( 'MK_VITE_DEV' ) && MK_VITE_DEV ) {
		add_action( 'wp_head', 'mk_vite_dev_client', 1 );
		return;
	}

	$manifest = mk_vite_manifest();

	if ( empty( $manifest[ MK_VITE_ENTRY ] ) ) {
		return;
	}

	$entry = $manifest[ MK_VITE_ENTRY ];

	foreach ( (array) ( $entry['css'] ?? array() ) as $i => $css ) {
		wp_enqueue_style( 'mk-style-' . $i, MK_URI . '/dist/' . $css, array(), MK_VERSION );
	}

	if ( ! empty( $entry['file'] ) ) {
		wp_enqueue_script( 'mk-main', MK_URI . '/dist/' . $entry['file'], array(), MK_VERSION, true );
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

/**
 * Load the manifest once per request.
 */
function mk_vite_manifest() {
	static $manifest = null;

	if ( null !== $manifest ) {
		return $manifest;
	}

	$manifest = array();

	// Vite 5+ writes to .vite/manifest.json; older versions to manifest.json.
	foreach ( array( '/dist/.vite/manifest.json', '/dist/manifest.json' ) as $path ) {
		$file = MK_DIR . $path;
		if ( file_exists( $file ) ) {
			$decoded  = json_decode( (string) file_get_contents( $file ), true );
			$manifest = is_array( $decoded ) ? $decoded : array();
			break;
		}
	}

	return $manifest;
}

/**
 * Dev-server module tags. Cannot go through wp_enqueue_script because they
 * need type="module".
 */
function mk_vite_dev_client() {
	printf(
		'<script type="module" src="%s/@vite/client"></script>' . "\n",
		esc_url( MK_VITE_DEV_URL )
	);
	printf(
		'<script type="module" src="%s/%s"></script>' . "\n",
		esc_url( MK_VITE_DEV_URL ),
		esc_attr( MK_VITE_ENTRY )
	);
}

/**
 * Fonts.
 *
 * The previous site referenced a self-hosted Addington CF that returns 404,
 * so it has been rendering in the browser default serif. Her Adobe Fonts kit
 * serves sweet-sans-pro, which the current CSS does not use at all.
 *
 * Until Melinda decides (licence Addington CF, or pick a replacement), the
 * display face resolves through the stack in _tokens.css. Set mk_typekit_id
 * to load an Adobe kit; leave it empty to skip.
 */
add_action( 'wp_head', 'mk_font_links', 2 );
function mk_font_links() {
	$kit = get_option( 'mk_typekit_id', '' );

	if ( $kit ) {
		echo '<link rel="preconnect" href="https://use.typekit.net" crossorigin>' . "\n";
		printf(
			'<link rel="stylesheet" href="https://use.typekit.net/%s.css">' . "\n",
			esc_attr( $kit )
		);
	}

	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@300;400;500&display=swap">' . "\n";
}
