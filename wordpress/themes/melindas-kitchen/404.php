<?php
/**
 * @package MK
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap wrap--narrow page-404">
	<h1 class="mk-h1"><?php esc_html_e( 'That page has moved or never existed', 'mk' ); ?></h1>
	<p class="mk-body">
		<?php esc_html_e( 'Browse the cakes, or get in touch and we will point you the right way.', 'mk' ); ?>
	</p>
	<p>
		<a class="mk-btn" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">
			<?php esc_html_e( 'View the range', 'mk' ); ?>
		</a>
	</p>
</div>
<?php
get_footer();
