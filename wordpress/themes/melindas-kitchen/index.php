<?php
/**
 * Fallback template.
 *
 * @package MK
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'entry' ); ?>>
				<h1 class="mk-h1"><?php the_title(); ?></h1>
				<div class="entry__content mk-body"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p class="mk-body"><?php esc_html_e( 'Nothing here yet.', 'mk' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
