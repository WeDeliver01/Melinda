<?php
/**
 * Single product.
 *
 * @package MK
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap single-product-wrap">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php wc_get_template_part( 'content', 'single-product' ); ?>
	<?php endwhile; ?>
</div>
<?php
get_footer();
