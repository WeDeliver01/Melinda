<?php
/**
 * Puts the FAQ on the Contact page (brief, Stage 3: "move the FAQ block to
 * Contact") using data/page-content/contact.html.
 *
 * The FAQ is 12 core Details blocks, so Melinda edits questions in the normal
 * page editor. The FAQ heading carries id="faq", so the redirect
 * /faq/ -> /contact/#faq lands on it.
 *
 * {{FORM:<title>}} markers are replaced with the Fluent Forms form of that
 * title. If a form is missing, the marker becomes an email link and a warning
 * is printed, so the page never shows a raw marker.
 *
 * Usage, from the WordPress root as the site user:
 *   wp eval-file ~/melinda-ops/scripts/apply-contact.php          # report
 *   wp eval-file ~/melinda-ops/scripts/apply-contact.php apply    # write
 *
 * Writing creates a page revision, so the previous content can be restored
 * from the editor's Revisions panel.
 *
 * @package MK_Core
 */

defined( 'ABSPATH' ) || die( 'Run via wp eval-file.' );

global $wpdb;

$apply = in_array( 'apply', (array) ( $args ?? array() ), true );
$src   = dirname( __DIR__ ) . '/data/page-content/contact.html';

if ( ! is_readable( $src ) ) {
	WP_CLI::error( "Not found: $src" );
}

$page = get_page_by_path( 'contact', OBJECT, 'page' );
if ( ! $page ) {
	WP_CLI::error( 'No page with slug "contact".' );
}

$content = (string) file_get_contents( $src );

$forms_table = $wpdb->prefix . 'fluentform_forms';
$has_forms   = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $forms_table ) );

$content = preg_replace_callback(
	'/\{\{FORM:([^}]+)\}\}/',
	static function ( $m ) use ( $wpdb, $forms_table, $has_forms ) {
		$title = trim( $m[1] );
		$id    = $has_forms
			? (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$forms_table} WHERE title = %s AND status = 'published' ORDER BY id LIMIT 1", $title ) ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is not user input.
			: 0;

		if ( $id ) {
			WP_CLI::log( sprintf( '  form "%s" -> Fluent Forms #%d', $title, $id ) );
			return "<!-- wp:shortcode -->\n[fluentform id=\"{$id}\"]\n<!-- /wp:shortcode -->";
		}

		WP_CLI::warning( sprintf( 'Fluent Forms form "%s" not found. Using an email link instead. Create the form, then re-run.', $title ) );
		return "<!-- wp:paragraph -->\n<p>Email <a href=\"mailto:info@melindaskitchen.co.za\">info@melindaskitchen.co.za</a> and we will get back to you as soon as possible.</p>\n<!-- /wp:paragraph -->";
	},
	$content
);

$blocks  = parse_blocks( $content );
$details = count( array_filter( $blocks, static fn( $b ) => 'core/details' === $b['blockName'] ) );

WP_CLI::log( sprintf( 'Contact page #%d "%s"', $page->ID, $page->post_title ) );
WP_CLI::log( sprintf( '  current content: %d characters', strlen( $page->post_content ) ) );
WP_CLI::log( sprintf( '  new content:     %d characters, %d FAQ questions', strlen( $content ), $details ) );

if ( 12 !== $details ) {
	WP_CLI::warning( "Expected 12 FAQ questions, found $details. Check contact.html before applying." );
}

if ( ! $apply ) {
	WP_CLI::log( 'Re-run with "apply" to write.' );
	return;
}

$result = wp_update_post(
	array(
		'ID'           => $page->ID,
		'post_content' => $content,
	),
	true
);

if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}

$faq = get_page_by_path( 'faq', OBJECT, 'page' );
WP_CLI::success( 'Contact page updated. Previous version is in Revisions.' );
if ( $faq && 'publish' === $faq->post_status ) {
	WP_CLI::log( 'The /faq/ page is still published. Once the Rank Math redirect /faq/ -> /contact/#faq is in place, set it to draft.' );
}
