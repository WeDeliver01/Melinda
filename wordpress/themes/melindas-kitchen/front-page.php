<?php
/**
 * Homepage. Structure and spacing taken from the rendered previous site.
 *
 * @package MK
 */
defined( 'ABSPATH' ) || exit;

get_header();

$slides = mk_home_images( 'mk_hero_slides' );
$story  = (int) get_option( 'mk_img_story' );
$banner = (int) get_option( 'mk_img_banner' );

$tiles = array(
	array( 'opt' => 'mk_img_tile_weddings', 'label' => __( 'Wedding Cakes', 'mk' ), 'url' => home_url( '/weddings/' ) ),
	array( 'opt' => 'mk_img_tile_shop',     'label' => __( 'Shop', 'mk' ),          'url' => wc_get_page_permalink( 'shop' ) ),
	array( 'opt' => 'mk_img_tile_contact',  'label' => __( 'Contact', 'mk' ),       'url' => home_url( '/contact/' ) ),
);
?>

<?php if ( $slides ) : ?>
<section class="slider" x-data="mkSlider(<?php echo count( $slides ); ?>)" x-init="start()">
	<?php foreach ( $slides as $i => $id ) : ?>
		<div class="slider__slide" :class="i === <?php echo (int) $i; ?> && 'is-active'">
			<?php
			echo wp_get_attachment_image( $id, 'full', false, array(
				'class'         => 'slider__img',
				'loading'       => 0 === $i ? 'eager' : 'lazy',
				'fetchpriority' => 0 === $i ? 'high' : 'auto',
			) );
			?>
		</div>
	<?php endforeach; ?>
	<div class="slider__dots">
		<?php foreach ( $slides as $i => $id ) : ?>
			<button type="button" class="slider__dot" :class="i === <?php echo (int) $i; ?> && 'is-on'"
				@click="go(<?php echo (int) $i; ?>)">
				<span class="screen-reader-text"><?php echo esc_html( sprintf( __( 'Slide %d', 'mk' ), $i + 1 ) ); ?></span>
			</button>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<section class="home-statement">
	<div class="container">
		<h1><?php echo esc_html( get_option( 'mk_statement', "Melinda’s Kitchen is a studio-based bakery located in Faerie Glen, Pretoria. We specialize in all things sweet from baked treats to wedding cakes. All our products are made-to-order with the finest ingredients and a lot of love." ) ); ?></h1>
	</div>
</section>

<section class="home-story">
	<div class="container home-story__grid">
		<div class="home-story__figure">
			<?php if ( $story ) : ?>
				<?php echo wp_get_attachment_image( $story, 'large', false, array( 'loading' => 'lazy' ) ); ?>
			<?php endif; ?>
		</div>
		<div class="home-story__text">
			<?php
			$content = get_the_content( null, false, get_option( 'page_on_front' ) );
			echo apply_filters( 'the_content', $content );
			?>
			<p class="home-story__cta">
				<a class="theme-button" href="<?php echo esc_url( home_url( '/about/' ) ); ?>">
					<?php esc_html_e( 'Find out more', 'mk' ); ?>
				</a>
			</p>
		</div>
	</div>
</section>

<section class="page-banner"<?php if ( $banner ) : ?> style="background-image:url('<?php echo esc_url( wp_get_attachment_image_url( $banner, 'full' ) ); ?>')"<?php endif; ?>>
	<div class="page-banner__plate">
		<h2 class="page-banner__title"><?php esc_html_e( 'The Experience', 'mk' ); ?></h2>
		<p class="page-banner__quote">
			<?php esc_html_e( '“When wedding cake artistry', 'mk' ); ?><br>
			<?php esc_html_e( 'collides with the world of luxury.”', 'mk' ); ?>
		</p>
	</div>
</section>

<section class="home-tiles">
	<div class="container home-tiles__grid">
		<?php foreach ( $tiles as $tile ) :
			$img = (int) get_option( $tile['opt'] );
			if ( ! $img ) {
				continue;
			}
			?>
			<a class="image-tile" href="<?php echo esc_url( $tile['url'] ); ?>">
				<span class="image-tile__frame">
					<?php echo wp_get_attachment_image( $img, 'large', false, array( 'loading' => 'lazy' ) ); ?>
				</span>
				<span class="image-tile__overlay">
					<span class="image-tile__title"><?php echo esc_html( $tile['label'] ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>

<?php
get_footer();
