<?php
/**
 * Page template.
 *
 * A page with a featured image gets the full-bleed banner treatment, with
 * the title and optional subtitle in a translucent plate. Set the subtitle
 * in the page excerpt. Without a featured image the title sits plainly
 * above the content. Both are controlled from the editor, not code.
 *
 * @package MK
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$show_banner = has_post_thumbnail() && ! get_post_meta( get_the_ID(), '_mk_hide_banner', true );
	?>

	<?php if ( $show_banner ) : ?>
		<section class="page-banner" style="background-image:url('<?php echo esc_url( get_the_post_thumbnail_url( null, 'full' ) ); ?>')">
			<div class="page-banner__plate">
				<h1 class="page-banner__title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="page-banner__quote"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<article <?php post_class( 'page-entry' ); ?>>
		<?php if ( ! $show_banner ) : ?>
			<header class="page-entry__head container">
				<h1><?php the_title(); ?></h1>
			</header>
		<?php endif; ?>

		<div class="page-entry__content container"><?php the_content(); ?></div>
	</article>

	<?php
endwhile;

get_footer();
