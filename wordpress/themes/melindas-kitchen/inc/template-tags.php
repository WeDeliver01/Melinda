<?php
/**
 * Small output helpers used by the templates.
 *
 * @package MK
 */

defined( 'ABSPATH' ) || exit;

/**
 * Logo, or the site name if no logo is set.
 */
function mk_branding() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	printf(
		'<a class="site-title" href="%s">%s</a>',
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

/**
 * Page intro block. Pulls the excerpt if one is set, otherwise nothing.
 */
function mk_page_intro() {
	if ( ! has_excerpt() ) {
		return;
	}
	printf( '<p class="page-intro">%s</p>', esc_html( get_the_excerpt() ) );
}

/**
 * Responsive image for a card at the right size.
 */
function mk_card_image( $post_id = null, $size = 'woocommerce_thumbnail' ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	if ( has_post_thumbnail( $post_id ) ) {
		echo get_the_post_thumbnail( $post_id, $size, array(
			'loading'  => 'lazy',
			'decoding' => 'async',
		) );
		return;
	}

	if ( function_exists( 'wc_placeholder_img' ) ) {
		echo wc_placeholder_img( $size );
	}
}

/**
 * Attachment IDs stored as a comma list in an option.
 */
function mk_home_images( $option ) {
	$raw = (string) get_option( $option, '' );
	$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
	return array_values( $ids );
}

/**
 * First product image found in an occasion term, used as its tile backdrop.
 * Cached so the homepage does not run a query per term on every load.
 */
function mk_occasion_thumb( $term ) {
	$key    = 'mk_occ_thumb_' . $term->term_id;
	$cached = get_transient( $key );

	if ( false !== $cached ) {
		return (int) $cached;
	}

	$ids = get_posts( array(
		'post_type'      => 'product',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_thumbnail_id',
		'tax_query'      => array( array(
			'taxonomy' => $term->taxonomy,
			'terms'    => $term->term_id,
		) ),
	) );

	$thumb = $ids ? (int) get_post_thumbnail_id( $ids[0] ) : 0;
	set_transient( $key, $thumb, DAY_IN_SECONDS );

	return $thumb;
}

add_action( 'set_object_terms', 'mk_flush_occasion_thumbs' );
add_action( 'edited_mk_occasion', 'mk_flush_occasion_thumbs' );
function mk_flush_occasion_thumbs() {
	$terms = get_terms( array( 'taxonomy' => 'mk_occasion', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) {
		return;
	}
	foreach ( $terms as $t ) {
		delete_transient( 'mk_occ_thumb_' . $t->term_id );
	}
}

/**
 * Top-level items of a menu, split into two halves so the logo can sit
 * between them, as Total's header-five does.
 */
function mk_split_menu( $location ) {
	$empty = array( 'left' => array(), 'right' => array() );

	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return $empty;
	}

	$items = wp_get_nav_menu_items( $locations[ $location ] );
	if ( ! $items ) {
		return $empty;
	}

	$top = array();
	foreach ( $items as $item ) {
		if ( empty( $item->menu_item_parent ) ) {
			$top[] = $item;
		}
	}

	$half = (int) ceil( count( $top ) / 2 );

	return array(
		'left'  => array_slice( $top, 0, $half ),
		'right' => array_slice( $top, $half ),
	);
}

/**
 * Menu item output for the split header. Deliberately not wp_nav_menu,
 * because the two halves need to be separate lists.
 */
function mk_render_menu_items( $items ) {
	$request = isset( $GLOBALS['wp']->request ) ? $GLOBALS['wp']->request : '';
	$current = untrailingslashit( home_url( $request ) );

	foreach ( $items as $item ) {
		$url    = $item->url;
		$active = untrailingslashit( $url ) === $current;

		printf(
			'<li class="primary-nav__item%1$s"><a href="%2$s"%3$s><span class="link-inner">%4$s</span></a></li>',
			$active ? ' is-current' : '',
			esc_url( $url ),
			$active ? ' aria-current="page"' : '',
			esc_html( $item->title )
		);
	}
}
