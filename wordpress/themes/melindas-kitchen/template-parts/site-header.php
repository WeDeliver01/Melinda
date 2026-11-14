<?php
/**
 * Site header.
 *
 * Reproduces Total's header-five: one centred list with the logo sitting
 * inside it as a middle item.
 *
 * @package MK
 */
defined( 'ABSPATH' ) || exit;

$items = mk_split_menu( 'primary' );
?>
<header class="site-header" x-data="{ open: false }">
	<div class="site-header__inner container">

		<button class="nav-toggle" type="button" @click="open = !open"
			:aria-expanded="open ? 'true' : 'false'" aria-controls="primary-nav">
			<span class="nav-toggle__box" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'mk' ); ?></span>
		</button>

		<nav aria-label="<?php esc_attr_e( 'Primary', 'mk' ); ?>">
			<ul id="primary-nav" class="site-header__bar" :class="open && 'is-open'">
				<li class="primary-nav"><?php mk_render_menu_items( $items['left'] ); ?></li>

				<li class="menu-item-logo">
					<div class="site-header__brand"><?php mk_branding(); ?></div>
				</li>

				<li class="primary-nav"><?php mk_render_menu_items( $items['right'] ); ?></li>
			</ul>
		</nav>

	</div>
</header>
