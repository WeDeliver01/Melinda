<?php
/**
 * Custom taxonomies.
 *
 * @package MK_Core
 */

namespace MK_Core;

defined( 'ABSPATH' ) || exit;

class Taxonomies {

	const OCCASION = 'mk_occasion';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'mk_shop_filters', array( __CLASS__, 'render_occasion_dropdown' ) );
	}

	/**
	 * Occasion sits alongside product categories rather than inside them.
	 *
	 * The old site mixed the two: Cupcakes and Macaron (what a thing is)
	 * lived in the same list as Christmas and Mother's Day (when you buy
	 * it). A cupcake cannot be in two categories without duplicating the
	 * product, which is why the old catalogue has near-duplicates. Splitting
	 * the axis fixes that and gives the occasion dropdown the brief asks for.
	 */
	public static function register() {
		register_taxonomy(
			self::OCCASION,
			array( 'product' ),
			array(
				'labels'            => array(
					'name'          => __( 'Occasions', 'mk-core' ),
					'singular_name' => __( 'Occasion', 'mk-core' ),
					'menu_name'     => __( 'Occasions', 'mk-core' ),
					'add_new_item'  => __( 'Add occasion', 'mk-core' ),
					'edit_item'     => __( 'Edit occasion', 'mk-core' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'occasion', 'with_front' => false ),
			)
		);
	}

	/**
	 * Occasion filter for the shop archive. Plain form + select so it works
	 * with JavaScript disabled; Alpine only auto-submits it.
	 */
	public static function render_occasion_dropdown() {
		$terms = get_terms( array(
			'taxonomy'   => self::OCCASION,
			'hide_empty' => true,
		) );

		if ( is_wp_error( $terms ) || ! $terms ) {
			return;
		}

		$current = get_query_var( self::OCCASION );
		?>
		<form class="occasion-filter" method="get"
			action="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"
			x-data @change="$el.submit()">
			<label class="screen-reader-text" for="mk-occasion">
				<?php esc_html_e( 'Filter by occasion', 'mk-core' ); ?>
			</label>
			<select id="mk-occasion" name="<?php echo esc_attr( self::OCCASION ); ?>">
				<option value=""><?php esc_html_e( 'All occasions', 'mk-core' ); ?></option>
				<?php foreach ( $terms as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>"
						<?php selected( $current, $term->slug ); ?>>
						<?php echo esc_html( $term->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<noscript>
				<button type="submit"><?php esc_html_e( 'Filter', 'mk-core' ); ?></button>
			</noscript>
		</form>
		<?php
	}
}
