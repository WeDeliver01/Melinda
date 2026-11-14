<?php
/**
 * Shop and product archives.
 *
 * Parallax banner, then the Shop page's own content (customisation note and
 * the three info cards), then the product loop. Matches the previous site.
 *
 * @package MK
 */
defined( 'ABSPATH' ) || exit;

get_header();

$banner = (int) get_option( 'mk_img_shop_banner' );
?>

<?php if ( $banner && is_shop() ) : ?>
	<section class="page-parallax" style="background-image:url('<?php echo esc_url( wp_get_attachment_image_url( $banner, 'full' ) ); ?>')"></section>
<?php endif; ?>

<div class="container shop">

	<?php
	if ( is_shop() ) {
		$shop_id = (int) wc_get_page_id( 'shop' );
		$intro   = $shop_id ? get_post_field( 'post_content', $shop_id ) : '';
		if ( trim( wp_strip_all_tags( $intro ) ) ) {
			echo '<div class="shop-intro">' . apply_filters( 'the_content', $intro ) . '</div>';
		}
	} else {
		?>
		<header class="archive-head">
			<h1><?php woocommerce_page_title(); ?></h1>
		</header>
		<?php
		do_action( 'woocommerce_archive_description' );
	}
	?>

	<?php do_action( 'woocommerce_before_main_content' ); ?>

	<?php if ( woocommerce_product_loop() ) : ?>

		<div class="shop__bar">
			<?php woocommerce_result_count(); ?>
			<?php woocommerce_catalog_ordering(); ?>
		</div>

		<?php woocommerce_product_loop_start(); ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php do_action( 'woocommerce_shop_loop' ); ?>
				<?php wc_get_template_part( 'content', 'product' ); ?>
			<?php endwhile; ?>
		<?php woocommerce_product_loop_end(); ?>

		<?php do_action( 'woocommerce_after_shop_loop' ); ?>

	<?php else : ?>
		<?php do_action( 'woocommerce_no_products_found' ); ?>
	<?php endif; ?>

</div>
<?php
get_footer();
