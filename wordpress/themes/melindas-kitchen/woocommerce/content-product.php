<?php
/**
 * Product card in the loop.
 *
 * Reproduces Total's structure, including the hover image swap to the first
 * gallery image. Price and add-to-cart are absent, matching the Customizer
 * settings on the original.
 *
 * @package MK
 */
defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$gallery   = $product->get_gallery_image_ids();
$secondary = $gallery ? (int) $gallery[0] : 0;
?>
<li <?php wc_product_class( 'entry has-media col wpex-woo-entry span_1_of_4', $product ); ?>>
	<div class="product-inner">

		<div class="wpex-loop-product-images">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="woocommerce-LoopProduct-link">
				<div class="woo-entry-image-swap">
					<?php
					echo get_the_post_thumbnail( $product->get_id(), 'woocommerce_thumbnail', array(
						'class'   => 'woo-entry-image-main',
						'loading' => 'lazy',
						'alt'     => esc_attr( $product->get_name() ),
					) );

					if ( $secondary ) {
						echo wp_get_attachment_image( $secondary, 'woocommerce_thumbnail', false, array(
							'class'   => 'woo-entry-image-secondary',
							'loading' => 'lazy',
							'alt'     => '',
						) );
					}
					?>
				</div>
			</a>
		</div>

		<div class="product-details">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
				<h2 class="woocommerce-loop-product__title"><?php echo esc_html( $product->get_name() ); ?></h2>
			</a>
		</div>

	</div>
</li>
