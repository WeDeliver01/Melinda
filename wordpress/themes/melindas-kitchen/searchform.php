<?php
/**
 * @package MK
 */
defined( 'ABSPATH' ) || exit;
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label>
		<span class="screen-reader-text"><?php esc_html_e( 'Search', 'mk' ); ?></span>
		<input type="search" class="search-form__input" name="s"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			placeholder="<?php esc_attr_e( 'Search the range', 'mk' ); ?>">
	</label>
	<button class="mk-btn" type="submit"><?php esc_html_e( 'Search', 'mk' ); ?></button>
</form>
