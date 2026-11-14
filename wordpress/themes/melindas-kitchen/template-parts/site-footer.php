<?php
/**
 * Site footer. Three columns: contact and social, logo, legal.
 *
 * @package MK
 */
defined( 'ABSPATH' ) || exit;

$phone   = get_option( 'mk_phone', '071 676 6777' );
$email   = get_option( 'mk_email', 'info@melindaskitchen.co.za' );
$logo_id = (int) get_option( 'mk_img_footer_logo' );
?>
<footer class="site-footer">
	<div class="wrap site-footer__grid">

		<div class="site-footer__col">
			<p class="site-footer__contact">
				<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>">
					<?php echo esc_html( $phone ); ?>
				</a><br>
				<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
			</p>

			<ul class="social">
				<li><a href="https://www.instagram.com/melindas_kitchen_" target="_blank" rel="noopener">
					<span class="screen-reader-text">Instagram</span>
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2.2c3.2 0 3.6 0 4.9.07 1.2.05 1.8.25 2.2.42.6.22 1 .48 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c0 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2 0-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c0-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2Zm0 5.1a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4Zm0 7.75a3.05 3.05 0 1 1 0-6.1 3.05 3.05 0 0 1 0 6.1Zm5.98-7.94a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0Z"/></svg>
				</a></li>
				<li><a href="https://www.facebook.com/melindaskitchen1" target="_blank" rel="noopener">
					<span class="screen-reader-text">Facebook</span>
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.25-1.5 1.55-1.5h1.65V3.6c-.3 0-1.3-.1-2.45-.1-2.4 0-4.05 1.5-4.05 4.2v2.2H7.5V13h2.7v8h3.3Z"/></svg>
				</a></li>
				<li><a href="mailto:<?php echo esc_attr( $email ); ?>">
					<span class="screen-reader-text">Email</span>
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm9 7.2L4.3 7h15.4L12 12.2Zm0 2-8-5.4V17h16V8.8l-8 5.4Z"/></svg>
				</a></li>
			</ul>
		</div>

		<div class="site-footer__col site-footer__col--logo">
			<?php if ( $logo_id ) : ?>
				<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array(
					'class'   => 'site-footer__logo',
					'loading' => 'lazy',
				) ); ?>
			<?php endif; ?>
		</div>

		<div class="site-footer__col">
			<p class="site-footer__legal">
				<?php echo esc_html( sprintf( __( 'Copyright %s', 'mk' ), gmdate( 'Y' ) ) ); ?>
			</p>
			<p class="site-footer__legal">
				<a href="<?php echo esc_url( home_url( '/terms-and-conditions/' ) ); ?>">
					<?php esc_html_e( 'Terms and Conditions', 'mk' ); ?>
				</a>
				&amp;
				<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">
					<?php esc_html_e( 'Privacy Policy', 'mk' ); ?>
				</a>
			</p>
			<p class="site-footer__legal">
				<?php esc_html_e( 'Designed by', 'mk' ); ?>
				<a href="https://operandis.co.za" target="_blank" rel="noopener">Operandi</a>.
			</p>
		</div>

	</div>
</footer>
