<?php
/**
 * Template helpers.
 *
 * @package lasemcakes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the site logo: the Customizer logo, else the theme's logo.png, else a text logo.
 */
function lasem_logo() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}

	$home = esc_url( home_url( '/' ) );
	$name = esc_attr( get_bloginfo( 'name' ) );

	if ( file_exists( LASEM_DIR . '/assets/images/logo.png' ) ) {
		printf(
			'<a href="%1$s" rel="home"><img src="%2$s" alt="%3$s" width="280" height="140"></a>',
			$home,
			esc_url( LASEM_URI . '/assets/images/logo.png' ),
			$name
		);
		return;
	}

	printf( '<a class="site-logo-text" href="%1$s" rel="home" aria-label="%2$s">la sem</a>', $home, $name );
}

/**
 * The top-level page a page belongs to (Bakery, Gallery, Awards...).
 *
 * @param int|WP_Post|null $post Page.
 * @return WP_Post|null
 */
function lasem_section_root( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$ancestors = get_post_ancestors( $post );
	return $ancestors ? get_post( end( $ancestors ) ) : $post;
}

/**
 * Print the section's sub-page menu shown in the page header.
 *
 * @param int|WP_Post|null $post Current page.
 */
function lasem_section_nav( $post = null ) {
	$post = get_post( $post );
	$root = lasem_section_root( $post );
	if ( ! $root ) {
		return;
	}

	$children = get_pages(
		array(
			'parent'      => $root->ID,
			'sort_column' => 'menu_order,post_title',
		)
	);
	if ( ! $children ) {
		return;
	}

	echo '<nav class="section-navigation" aria-label="' . esc_attr( sprintf( /* translators: %s: section name, e.g. Bakery */ __( '%s pages', 'lasemcakes' ), get_the_title( $root ) ) ) . '"><ul>';
	foreach ( $children as $child ) {
		$current = ( $child->ID === $post->ID );
		printf(
			'<li%1$s><a href="%2$s"%3$s>%4$s<span class="section-navigation-title">%5$s</span></a></li>',
			$current ? ' class="is-current"' : '',
			esc_url( get_permalink( $child ) ),
			$current ? ' aria-current="page"' : '',
			get_the_post_thumbnail( $child, 'lasem-nav-icon', array( 'alt' => '' ) ),
			esc_html( get_post_meta( $child->ID, '_lasem_nav_label', true ) ?: get_the_title( $child ) )
		);
	}
	echo '</ul></nav>';
}

/**
 * URL the "Order Online" buttons use.
 *
 * @return string
 */
function lasem_order_url() {
	return get_theme_mod( 'lasem_order_url', LASEM_DEFAULT_ORDER_URL );
}

/**
 * Print the social icons set in the Customizer.
 */
function lasem_social_links() {
	$networks = array(
		'facebook'  => array(
			'label' => 'Facebook',
			'path'  => 'M14 8h3V4h-3c-2.8 0-5 2.2-5 5v2H7v4h2v9h4v-9h3l1-4h-4V9c0-.6.4-1 1-1z',
		),
		'instagram' => array(
			'label' => 'Instagram',
			'path'  => 'M12 7.3A4.7 4.7 0 1 0 16.7 12 4.7 4.7 0 0 0 12 7.3zm0 7.7a3 3 0 1 1 3-3 3 3 0 0 1-3 3zm6-7.9a1.1 1.1 0 1 1-1.1-1.1A1.1 1.1 0 0 1 18 7.1zM21.9 7a5.4 5.4 0 0 0-1.5-3.9A5.4 5.4 0 0 0 16.5 1.6C15 1.5 9 1.5 7.5 1.6a5.4 5.4 0 0 0-3.9 1.5A5.4 5.4 0 0 0 2.1 7C2 8.5 2 15.5 2.1 17a5.4 5.4 0 0 0 1.5 3.9 5.4 5.4 0 0 0 3.9 1.5c1.5.1 7.5.1 9 0a5.4 5.4 0 0 0 3.9-1.5 5.4 5.4 0 0 0 1.5-3.9c.1-1.5.1-8.5 0-10z',
		),
		'twitter'   => array(
			'label' => 'X (Twitter)',
			'path'  => 'M17.8 3h3.1l-6.8 7.7L22 21h-6.2l-4.9-6.4L5.3 21H2.2l7.2-8.3L2 3h6.4l4.4 5.8zm-1.1 16.2h1.7L7.4 4.7H5.6z',
		),
	);

	$out = '';
	foreach ( $networks as $key => $network ) {
		$url = get_theme_mod( 'lasem_social_' . $key );
		if ( ! $url ) {
			continue;
		}
		$out .= sprintf(
			'<a href="%1$s" target="_blank" rel="noopener"><span class="screen-reader-text">%2$s</span><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="%3$s"/></svg></a>',
			esc_url( $url ),
			esc_html( $network['label'] ),
			esc_attr( $network['path'] )
		);
	}

	if ( $out ) {
		echo '<span class="social-links">' . $out . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	}
}

/**
 * Footer contact details used when the "Footer: Get In Touch" widget area is empty.
 */
function lasem_footer_contact_fallback() {
	?>
	<div class="footer-header"><?php esc_html_e( 'Get In Touch', 'lasemcakes' ); ?></div>
	<div class="textwidget">
		1275 Eglinton Ave. East<br>
		Mississauga, ON<br>
		L4W 2Z2<br>
		Canada<br><br>
		<?php esc_html_e( 'Tel:', 'lasemcakes' ); ?> <a href="tel:+19056248888">(905) 624-8888</a><br>
		<?php esc_html_e( 'Fax:', 'lasemcakes' ); ?> (905) 238-3232<br>
		<?php esc_html_e( 'Email:', 'lasemcakes' ); ?> <a href="mailto:info@lasemcakes.ca"><?php esc_html_e( 'Email Us Here', 'lasemcakes' ); ?></a>
	</div>
	<?php
}

/**
 * Post date and category line, as on the original Articles & Videos page.
 */
function lasem_posted_on() {
	echo '<div class="entry-meta">';
	echo esc_html( get_the_date( 'l, F jS, Y \a\t g:i a' ) );
	$categories = get_the_category_list( ', ' );
	if ( $categories ) {
		echo '<br>' . esc_html__( 'Category:', 'lasemcakes' ) . ' ' . $categories . '.'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup.
	}
	echo '</div>';
}

/**
 * Whether the announcement pop-up should print on this request.
 *
 * @return bool
 */
function lasem_announcement_should_show() {
	if ( ! get_theme_mod( 'lasem_announcement_enabled', false ) ) {
		return false;
	}
	if ( ! get_theme_mod( 'lasem_announcement_page' ) ) {
		return false;
	}
	return 'all' === get_theme_mod( 'lasem_announcement_where', 'home' ) || is_front_page();
}

/**
 * Print the announcement pop-up. Its content is a normal page staff edit in the block editor.
 */
function lasem_announcement() {
	if ( ! lasem_announcement_should_show() ) {
		return;
	}

	$page = get_post( (int) get_theme_mod( 'lasem_announcement_page' ) );
	if ( ! $page || 'trash' === $page->post_status ) {
		return;
	}

	// The page's last change resets "already seen", so updated news shows again.
	$version = strtotime( $page->post_modified_gmt );
	?>
	<div class="lasem-announcement" id="lasem-announcement" data-version="<?php echo esc_attr( $version ); ?>" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( get_the_title( $page ) ); ?>" hidden>
		<div class="lasem-announcement-backdrop" data-close></div>
		<div class="lasem-announcement-dialog">
			<button type="button" class="lasem-announcement-close" data-close aria-label="<?php esc_attr_e( 'Close', 'lasemcakes' ); ?>">&times;</button>
			<?php echo apply_filters( 'the_content', $page->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- post content. ?>
		</div>
	</div>
	<?php
}
