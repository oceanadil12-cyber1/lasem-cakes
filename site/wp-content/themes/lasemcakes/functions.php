<?php
/**
 * Lasem Cakes theme setup.
 *
 * @package lasemcakes
 */

defined( 'ABSPATH' ) || exit;

define( 'LASEM_VERSION', '2.0.0' );
define( 'LASEM_DIR', get_template_directory() );
define( 'LASEM_URI', get_template_directory_uri() );

/** DoorDash storefront the "Order Online" links point to until in-site ordering ships. */
define( 'LASEM_DEFAULT_ORDER_URL', 'https://order.online/business/la-sem-patisserie-88690' );

require LASEM_DIR . '/inc/template-tags.php';
require LASEM_DIR . '/inc/customizer.php';

/**
 * Theme supports, menus and image sizes.
 */
function lasem_setup() {
	load_theme_textdomain( 'lasemcakes', LASEM_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 140,
			'width'       => 280,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'header' => __( 'Header menu', 'lasemcakes' ),
			'footer' => __( 'Footer menu', 'lasemcakes' ),
		)
	);

	// Round icons beside each page in a section's navigation (Bakery, Awards).
	add_image_size( 'lasem-nav-icon', 80, 80, true );
}
add_action( 'after_setup_theme', 'lasem_setup' );

/**
 * Content width for embeds.
 */
function lasem_content_width() {
	$GLOBALS['content_width'] = 870;
}
add_action( 'after_setup_theme', 'lasem_content_width', 0 );

/**
 * Widget areas.
 */
function lasem_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Footer: Get In Touch', 'lasemcakes' ),
			'id'            => 'footer-contact',
			'description'   => __( 'Address, phone and email shown in the footer.', 'lasemcakes' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<div class="footer-header">',
			'after_title'   => '</div>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Articles & Videos sidebar', 'lasemcakes' ),
			'id'            => 'blog-sidebar',
			'description'   => __( 'Shown beside the Articles & Videos list and posts.', 'lasemcakes' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'lasem_widgets_init' );

/**
 * Styles and scripts.
 */
function lasem_enqueue_assets() {
	wp_enqueue_style(
		'lasem-fonts',
		'https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'lasem-style', get_stylesheet_uri(), array( 'lasem-fonts' ), LASEM_VERSION );

	// The original Black Jack webfont, once blackjack-webfont.woff2 is added to assets/fonts/.
	if ( file_exists( LASEM_DIR . '/assets/fonts/blackjack-webfont.woff2' ) ) {
		$fonts = LASEM_URI . '/assets/fonts/';
		wp_add_inline_style(
			'lasem-style',
			'@font-face{font-family:"black_jackregular";src:url("' . esc_url( $fonts . 'blackjack-webfont.woff2' ) . '") format("woff2"),url("' . esc_url( $fonts . 'blackjack-webfont.woff' ) . '") format("woff");font-weight:normal;font-style:normal;font-display:swap}'
		);
	}

	wp_enqueue_script( 'lasem-main', LASEM_URI . '/assets/js/main.js', array( 'jquery' ), LASEM_VERSION, true );
	wp_localize_script(
		'lasem-main',
		'lasemTheme',
		array(
			'openMenu'    => __( 'Open sub-menu', 'lasemcakes' ),
			'closeMenu'   => __( 'Close sub-menu', 'lasemcakes' ),
			'slideLabel'  => __( 'Show slide %d', 'lasemcakes' ),
			'popupMaxAge' => (int) get_theme_mod( 'lasem_announcement_frequency', 1 ),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'lasem_enqueue_assets' );

/**
 * Let the browser connect to Google Fonts early.
 *
 * @param array  $urls          URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed for.
 * @return array
 */
function lasem_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'lasem_resource_hints', 10, 2 );

/**
 * Open the "Order Online" storefront in a new tab when it points off-site.
 *
 * @param array    $atts Link attributes.
 * @param WP_Post  $item Menu item.
 * @return array
 */
function lasem_menu_link_attributes( $atts, $item ) {
	if ( ! empty( $atts['href'] ) && '#' !== $atts['href'] ) {
		$host = wp_parse_url( $atts['href'], PHP_URL_HOST );
		if ( $host && wp_parse_url( home_url(), PHP_URL_HOST ) !== $host ) {
			$atts['target'] = '_blank';
			$atts['rel']    = 'noopener';
		}
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'lasem_menu_link_attributes', 10, 2 );

/**
 * Body classes used by the templates.
 *
 * @param array $classes Body classes.
 * @return array
 */
function lasem_body_classes( $classes ) {
	if ( lasem_announcement_should_show() ) {
		$classes[] = 'has-announcement';
	}
	if ( file_exists( LASEM_DIR . '/assets/images/bg.gif' ) ) {
		$classes[] = 'has-bg-pattern';
	}
	return $classes;
}
add_filter( 'body_class', 'lasem_body_classes' );

/**
 * Shorter excerpts with the original "Continue reading..." link.
 *
 * @return int
 */
function lasem_excerpt_length() {
	return 40;
}
add_filter( 'excerpt_length', 'lasem_excerpt_length' );

/**
 * Replace the default "[…]" excerpt ending.
 *
 * @return string
 */
function lasem_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'lasem_excerpt_more' );
