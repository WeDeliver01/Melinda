<?php
/**
 * @package MK
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap">
	<h1 class="mk-h1"><?php the_archive_title(); ?></h1>
	<div class="archive-grid">
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'card' ); ?>>
				<a class="card__link" href="<?php the_permalink(); ?>">
					<?php mk_card_image(); ?>
					<h2 class="card__title mk-h2"><?php the_title(); ?></h2>
				</a>
			</article>
		<?php endwhile; ?>
	</div>
	<?php the_posts_pagination(); ?>
</div>
<?php
get_footer();
