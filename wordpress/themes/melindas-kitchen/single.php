<?php
/**
 * @package MK
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'entry' ); ?>>
		<header class="entry__head wrap">
			<h1 class="mk-h1"><?php the_title(); ?></h1>
			<time class="entry__date mk-h3" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
		</header>
		<div class="entry__content wrap mk-body"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;

get_footer();
