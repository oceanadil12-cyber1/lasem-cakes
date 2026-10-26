<?php
/**
 * Build the local La Sem site so it matches lasemcakes.ca:
 * the same pages and URLs, both menus, the home slider, the announcement
 * pop-up, and each page's text copied from the live site.
 *
 * Images are not copied: they still load from lasemcakes.ca until the real
 * content move (All-in-One WP Migration) in the content phase.
 *
 * Safe to run again: pages are updated in place and the menus rebuilt.
 *
 * Run from the project folder:
 *   php tools/wp-cli.phar --path=site --user=admin eval-file tools/seed-site.php
 *
 * @package lasemcakes
 */

defined( 'ABSPATH' ) || exit;

const LASEM_LIVE = 'https://lasemcakes.ca/';

/*
 * Page tree. Keys are the live URL paths; children inherit the path of their parent.
 */
$lasem_pages = array(
	'home'                => array( 'title' => 'Home' ),
	'about-us'            => array( 'title' => 'About Us' ),
	'bakery'              => array(
		'title'    => 'Bakery',
		'template' => 'templates/template-section.php',
		'children' => array(
			'cake-category'      => array( 'title' => 'Cake Category' ),
			'cake-type-fillings' => array(
				'title' => 'Cake Type & Fillings',
				'label' => 'Cake Type',
			),
			'pastries'           => array( 'title' => 'Pastries' ),
			'cookies'            => array( 'title' => 'Cookies' ),
			'gelato'             => array( 'title' => 'Gelato' ),
			'bread'              => array( 'title' => 'Bread' ),
			'zeppole'            => array( 'title' => 'Zeppole' ),
		),
	),
	'gallery'             => array(
		'title'          => 'Gallery',
		'template'       => 'templates/template-section.php',
		'child_template' => 'templates/template-gallery.php',
		'children'       => array(
			'birthday-novelty'        => array( 'title' => 'Birthday & Novelty' ),
			'religious-graduation'    => array( 'title' => 'Religious & Graduation' ),
			'specialties'             => array( 'title' => 'Specialties' ),
			'retail-cakes'            => array( 'title' => 'Retail Cakes' ),
			'retail-pastry-other'     => array( 'title' => 'Retail Pastry & other' ),
			'baby-shower'             => array( 'title' => 'Baby Shower' ),
			'bachelor-bachelorette'   => array( 'title' => 'Bachelor & Bachelorette' ),
			'engagement-anniversary'  => array( 'title' => 'Engagement & Anniversary' ),
			'wedding-shower'          => array( 'title' => 'Wedding & Shower' ),
			'cookies-other'           => array( 'title' => 'Cookies & Other' ),
			'christmas'               => array( 'title' => 'Christmas' ),
		),
	),
	'awards'              => array(
		'title'    => 'Awards',
		'template' => 'templates/template-section.php',
		'children' => array(
			'top-choice-award'     => array( 'title' => 'Top Choice Award' ),
			'readers-choice-award' => array( 'title' => 'Reader’s Choice Award' ),
			'peoples-choice'       => array( 'title' => 'People’s Choice' ),
		),
	),
	'articles-videos'     => array(
		'title' => 'Articles & Videos',
		'skip'  => true, // The posts page: its list comes from the posts.
	),
	'testimonials'        => array( 'title' => 'Testimonials' ),
	'contact-us'          => array( 'title' => 'Contact Us' ),
	'terms-conditions'    => array( 'title' => 'Terms & Conditions' ),
	'returns-exchanges'   => array( 'title' => 'Returns & Exchanges' ),
	'cancellation-policy' => array( 'title' => 'Cancellation Policy' ),
	'shipping'            => array( 'title' => 'Shipping' ),
	'privacy-policy'      => array( 'title' => 'Privacy Policy' ),
);

/**
 * GET a page from the live site.
 *
 * @param string $path Path under lasemcakes.ca.
 * @return string|null HTML, or null when it is not there.
 */
function lasem_seed_get( $path ) {
	$response = wp_remote_get(
		LASEM_LIVE . ltrim( $path, '/' ),
		array(
			'timeout'   => 30,
			// Local dev import of public pages only: XAMPP's PHP has no CA bundle.
			'sslverify' => false,
		)
	);
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}
	return wp_remote_retrieve_body( $response );
}

/**
 * Pull the main content out of a live page.
 *
 * @param string $html  Whole page.
 * @param string $title Page title, to drop the heading the template already prints.
 * @return DOMElement|null
 */
function lasem_seed_content_node( $html, &$dom ) {
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8"?>' . $html );
	libxml_clear_errors();

	$xpath = new DOMXPath( $dom );
	foreach ( $xpath->query( '//script|//style|//noscript' ) as $node ) {
		$node->parentNode->removeChild( $node );
	}
	$nodes = $xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " content-wrapper ")]' );
	return $nodes->length ? $nodes->item( 0 ) : null;
}

/**
 * Point links at the local site, but leave images on lasemcakes.ca.
 *
 * @param string $html Content.
 * @return string
 */
function lasem_seed_local_links( $html ) {
	return preg_replace(
		'#https?://(?:www\.)?lasemcakes\.ca/(?!wp-content/|wp-includes/)#i',
		home_url( '/' ),
		$html
	);
}

/**
 * The page body as one Custom HTML block, so WordPress does not add stray paragraphs.
 *
 * @param DOMElement  $node  Content node.
 * @param DOMDocument $dom   Its document.
 * @param string      $title Page title.
 * @return string
 */
function lasem_seed_html_block( $node, $dom, $title ) {
	// The template prints the title, so drop the copy at the top of the content.
	foreach ( $node->childNodes as $child ) {
		if ( XML_ELEMENT_NODE !== $child->nodeType ) {
			continue;
		}
		if ( in_array( $child->nodeName, array( 'h1', 'h2' ), true )
			&& wp_specialchars_decode( trim( $child->textContent ) ) === wp_specialchars_decode( $title ) ) {
			$node->removeChild( $child );
		}
		break;
	}

	$html = '';
	foreach ( $node->childNodes as $child ) {
		$html .= $dom->saveHTML( $child );
	}
	$html = trim( lasem_seed_local_links( $html ) );

	return $html ? "<!-- wp:html -->\n" . $html . "\n<!-- /wp:html -->" : '';
}

/**
 * A gallery page's photos as a core Gallery block (full image linked from each thumbnail).
 *
 * @param DOMElement $node Content node.
 * @return string Empty when the page has no linked photos.
 */
function lasem_seed_gallery_block( $node ) {
	$images = array();
	foreach ( $node->getElementsByTagName( 'a' ) as $link ) {
		$href = $link->getAttribute( 'href' );
		$img  = $link->getElementsByTagName( 'img' )->item( 0 );
		if ( ! $img || ! preg_match( '#\.(jpe?g|png|gif|webp)(\?.*)?$#i', $href ) ) {
			continue;
		}
		$src = $img->getAttribute( 'data-src' ) ?: $img->getAttribute( 'src' );
		if ( ! $src || str_starts_with( $src, 'data:' ) ) {
			$src = $href;
		}
		$images[ $href ] = array(
			'full'  => $href,
			'thumb' => $src,
			'alt'   => $img->getAttribute( 'alt' ) ?: $link->getAttribute( 'title' ),
		);
	}
	if ( ! $images ) {
		return '';
	}

	$out = '<!-- wp:gallery {"columns":4,"linkTo":"media","sizeSlug":"full"} -->' . "\n"
		. '<figure class="wp-block-gallery has-nested-images columns-4 is-cropped">';
	foreach ( $images as $image ) {
		$out .= "\n" . '<!-- wp:image {"sizeSlug":"full","linkDestination":"media"} -->'
			. '<figure class="wp-block-image size-full"><a href="' . esc_url( $image['full'] ) . '"><img src="' . esc_url( $image['thumb'] ) . '" alt="' . esc_attr( $image['alt'] ) . '"/></a></figure>'
			. '<!-- /wp:image -->';
	}
	return $out . "\n</figure>\n<!-- /wp:gallery -->";
}

/**
 * Create or update one page.
 *
 * @param string $path     Full URL path, e.g. bakery/cookies.
 * @param array  $args     Title, template, label.
 * @param int    $parent   Parent page ID.
 * @param int    $order    Menu order.
 * @param string $template Page template.
 * @return int Page ID.
 */
function lasem_seed_page( $path, $args, $parent, $order, $template ) {
	$title   = $args['title'];
	$slug    = basename( $path );
	$content = '';

	if ( 'home' === $path ) {
		$content = lasem_seed_home_slider();
	} elseif ( empty( $args['skip'] ) ) {
		$html = lasem_seed_get( $path . '/' );
		$dom  = null;
		$node = $html ? lasem_seed_content_node( $html, $dom ) : null;
		if ( $node ) {
			if ( 'templates/template-gallery.php' === $template ) {
				$content = lasem_seed_gallery_block( $node );
			}
			if ( ! $content ) {
				$content = lasem_seed_html_block( $node, $dom, $title );
			}
		}
		// Section pages (Bakery, Gallery, Awards) may be empty: their header lists the sub-pages.
		if ( ! $content && empty( $args['children'] ) ) {
			$content = "<!-- wp:paragraph -->\n<p>Content for this page moves over from the live site in the content phase.</p>\n<!-- /wp:paragraph -->";
			WP_CLI::warning( "No live content found for /$path/" );
		}
	}

	$existing = get_page_by_path( $path, OBJECT, 'page' );
	$postarr  = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_parent'  => $parent,
		'menu_order'   => $order,
		'post_content' => $content,
	);
	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
	}

	$id = wp_insert_post( wp_slash( $postarr ), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( "/$path/: " . $id->get_error_message() );
	}

	update_post_meta( $id, '_wp_page_template', $template ?: 'default' );
	if ( ! empty( $args['label'] ) ) {
		update_post_meta( $id, '_lasem_nav_label', $args['label'] );
	}

	WP_CLI::log( sprintf( '%-8s /%s/', $existing ? 'updated' : 'created', $path ) );
	return $id;
}

/**
 * Home page slider: the live slider's images in the theme's slider markup.
 * Swap for a [masterslider] shortcode once Master Slider is set up.
 *
 * @return string
 */
function lasem_seed_home_slider() {
	$slides = array(
		'2017/03/1.jpg'                  => '',
		'2017/03/2.jpg'                  => 'Top Choice Award',
		'2017/03/3.gif'                  => 'La Sem wedding & specialty cakes',
		'2017/03/4.gif'                  => '',
		'2017/03/5.jpg'                  => 'La Sem Cakes since 1963',
		'2017/03/6.jpg'                  => 'Modern day La Sem Cakes',
		'2021/11/La-sem-Name-board.jpg' => 'La Sem name board',
		'2021/11/Staff-1-4.jpg'          => 'La Sem staff',
	);
	$html = '<div class="lasem-slider" data-delay="5000">';
	foreach ( $slides as $file => $alt ) {
		$html .= "\n\t" . '<img src="' . esc_url( LASEM_LIVE . 'wp-content/uploads/' . $file ) . '" alt="' . esc_attr( $alt ) . '" width="1070" height="460">';
	}
	$html .= "\n</div>";
	return "<!-- wp:html -->\n" . $html . "\n<!-- /wp:html -->";
}

/**
 * The announcement pop-up's page, with the live pop-up's current message.
 *
 * @return int Page ID.
 */
function lasem_seed_announcement() {
	$uploads = LASEM_LIVE . 'wp-content/uploads/';
	$order   = get_theme_mod( 'lasem_order_url', LASEM_DEFAULT_ORDER_URL );
	$content = <<<HTML
<!-- wp:paragraph {"className":"is-alert"} -->
<p class="is-alert">PRICE REDUCTION FOR ALL CAKES &amp; PASTRIES FROM SEPTEMBER 1st. <a href="{$uploads}2026/09/Reduction.jpg" target="_blank" rel="noopener">Click Here</a></p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Introducing Our Focaccia Sandwiches</h3>
<!-- /wp:heading -->

<!-- wp:image -->
<figure class="wp-block-image"><img src="{$uploads}2026/05/Foccacia-pict.jpg" alt="La Sem Focaccia"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>Our focaccia has been made the same way for over 60 years. Our traditional recipe. New obsession and for good reason!</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Now, we're taking that golden, herb-kissed bread and turning it into something you won't be able to stop thinking about: our brand-new focaccia sandwiches.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Same beloved recipe. A whole new way to enjoy it. Come in and taste the tradition for yourself. Visit us in store, we can't wait to see you!</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a class="lasem-button" href="https://www.facebook.com/reel/871530355970305" target="_blank" rel="noopener">Facebook</a> <a class="lasem-button" href="https://www.instagram.com/p/DYNMr5OOGlA/" target="_blank" rel="noopener">Instagram</a></p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Online Ordering Is Available</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Click Order Online in the menu, or the button below, to go to our online store.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a class="lasem-button" href="{$order}" target="_blank" rel="noopener">ORDER ONLINE</a></p>
<!-- /wp:paragraph -->
HTML;

	$existing = get_page_by_path( 'announcement', OBJECT, 'page' );
	$id       = wp_insert_post(
		wp_slash(
			array(
				'ID'           => $existing ? $existing->ID : 0,
				'post_type'    => 'page',
				'post_status'  => 'private', // Only shown inside the pop-up, never as its own page.
				'post_title'   => 'Announcement',
				'post_name'    => 'announcement',
				'post_content' => $content,
			)
		)
	);
	WP_CLI::log( 'saved    announcement pop-up page' );
	return $id;
}

/**
 * A live post's full body. The feed only has summaries, so read the post's page.
 * Videos become [video] shortcodes so WordPress renders and edits them as usual.
 *
 * @param string $link Live post URL.
 * @return string Empty when the page could not be read.
 */
function lasem_seed_post_content( $link ) {
	$html = lasem_seed_get( wp_parse_url( $link, PHP_URL_PATH ) );
	if ( ! $html ) {
		return '';
	}

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8"?>' . $html );
	libxml_clear_errors();
	$xpath = new DOMXPath( $dom );

	foreach ( $xpath->query( '//script|//style|//noscript' ) as $node ) {
		$node->parentNode->removeChild( $node );
	}
	$body = $xpath->query( '//article//div[contains(concat(" ", normalize-space(@class), " "), " entry-content ")]' )->item( 0 );
	if ( ! $body ) {
		return '';
	}

	foreach ( iterator_to_array( $xpath->query( './/div[contains(@class, "wp-video")]', $body ) ) as $player ) {
		$source = $xpath->query( './/source', $player )->item( 0 );
		if ( ! $source ) {
			continue;
		}
		$src       = preg_replace( '/\?_=\d+$/', '', $source->getAttribute( 'src' ) );
		$shortcode = $dom->createTextNode( '[video src="' . esc_url( $src ) . '"]' );
		$player->parentNode->replaceChild( $shortcode, $player );
	}

	$out = '';
	foreach ( $body->childNodes as $child ) {
		$out .= $dom->saveHTML( $child );
	}
	return trim( lasem_seed_local_links( $out ) );
}

/**
 * Copy the latest Articles & Videos posts (the ones in the live RSS feed).
 */
function lasem_seed_posts() {
	$feed = lasem_seed_get( 'feed/' );
	if ( ! $feed ) {
		WP_CLI::warning( 'Could not read the live feed; no posts imported.' );
		return;
	}
	$xml = simplexml_load_string( $feed );
	if ( ! $xml ) {
		return;
	}

	foreach ( $xml->channel->item as $item ) {
		$link = (string) $item->link;
		$slug = basename( untrailingslashit( wp_parse_url( $link, PHP_URL_PATH ) ) );
		$date = gmdate( 'Y-m-d H:i:s', strtotime( (string) $item->pubDate ) );

		$content = lasem_seed_post_content( $link );
		if ( ! $content ) {
			// Fall back to the feed's summary, minus its "appeared first on" line.
			$content = preg_replace( '#<p>The post .*?appeared first on .*?</p>#s', '', (string) $item->description );
			WP_CLI::warning( "Used the feed summary for $slug" );
		}

		$categories = array();
		foreach ( $item->category as $category ) {
			$term = term_exists( (string) $category, 'category' ) ?: wp_insert_term( (string) $category, 'category' );
			if ( ! is_wp_error( $term ) ) {
				$categories[] = (int) $term['term_id'];
			}
		}

		$existing = get_posts(
			array(
				'name'        => $slug,
				'post_type'   => 'post',
				'post_status' => 'any',
				'numberposts' => 1,
			)
		);

		wp_insert_post(
			wp_slash(
				array(
					'ID'            => $existing ? $existing[0]->ID : 0,
					'post_type'     => 'post',
					'post_status'   => 'publish',
					'post_title'    => wp_specialchars_decode( (string) $item->title, ENT_QUOTES ),
					'post_name'     => $slug,
					'post_date_gmt' => $date,
					'post_date'     => get_date_from_gmt( $date ),
					'post_content'  => $content,
					'post_category' => $categories ?: array( 1 ),
				)
			)
		);
		WP_CLI::log( 'post     ' . $slug );
	}
}

/**
 * Rebuild a menu from a nested list of items.
 *
 * @param string $name     Menu name.
 * @param string $location Theme location.
 * @param array  $items    Items: [ 'title', 'page' => id | 'url' => url, 'children' => [...] ].
 */
function lasem_seed_menu( $name, $location, $items ) {
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu ) {
		wp_delete_nav_menu( $menu->term_id );
	}
	$menu_id = wp_create_nav_menu( $name );

	$add = function ( $items, $parent = 0 ) use ( &$add, $menu_id ) {
		foreach ( $items as $item ) {
			$args = array(
				'menu-item-title'     => $item['title'],
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent,
			);
			if ( isset( $item['page'] ) ) {
				$args['menu-item-type']      = 'post_type';
				$args['menu-item-object']    = 'page';
				$args['menu-item-object-id'] = $item['page'];
			} else {
				$args['menu-item-type'] = 'custom';
				$args['menu-item-url']  = $item['url'];
			}
			$id = wp_update_nav_menu_item( $menu_id, 0, $args );
			if ( ! empty( $item['children'] ) ) {
				$add( $item['children'], $id );
			}
		}
	};
	$add( $items );

	$locations              = get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
	WP_CLI::log( "menu     $name" );
}

/* ----------------------------------------------------------------------------
 * Run
 * ------------------------------------------------------------------------- */

// Remove WordPress's sample content.
foreach ( array( 'sample-page' ) as $sample ) {
	$page = get_page_by_path( $sample );
	if ( $page ) {
		wp_delete_post( $page->ID, true );
	}
}
$hello = get_posts( array( 'name' => 'hello-world', 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => 1 ) );
if ( $hello ) {
	wp_delete_post( $hello[0]->ID, true );
}

// Pages.
$ids   = array();
$order = 0;
foreach ( $lasem_pages as $slug => $args ) {
	$ids[ $slug ] = lasem_seed_page( $slug, $args, 0, $order++, $args['template'] ?? '' );
	if ( ! empty( $args['children'] ) ) {
		$child_order = 0;
		foreach ( $args['children'] as $child_slug => $child ) {
			$ids[ $child_slug ] = lasem_seed_page(
				"$slug/$child_slug",
				$child,
				$ids[ $slug ],
				$child_order++,
				$args['child_template'] ?? $args['template'] ?? ''
			);
		}
	}
}

// Home and Articles & Videos.
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $ids['home'] );
update_option( 'page_for_posts', $ids['articles-videos'] );
update_option( 'wp_page_for_privacy_policy', $ids['privacy-policy'] );

// Same post URLs as the live site, e.g. /2021/01/31/top-choice-2019/.
update_option( 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );

lasem_seed_posts();

// Empty the theme's widget areas: WordPress drops its default widgets into the
// first one (the footer's "Get In Touch"). Both areas have their own fallbacks.
$sidebars                        = wp_get_sidebars_widgets();
$sidebars['wp_inactive_widgets'] = array_merge(
	$sidebars['wp_inactive_widgets'] ?? array(),
	$sidebars['footer-contact'] ?? array(),
	$sidebars['blog-sidebar'] ?? array()
);
$sidebars['footer-contact'] = array();
$sidebars['blog-sidebar']   = array();
wp_set_sidebars_widgets( $sidebars );

// Theme settings.
$order_url = get_theme_mod( 'lasem_order_url', LASEM_DEFAULT_ORDER_URL );
set_theme_mod( 'lasem_social_facebook', 'https://www.facebook.com/lasembakery' );
set_theme_mod( 'lasem_social_twitter', 'https://twitter.com/lasembakery' );
set_theme_mod( 'lasem_announcement_page', lasem_seed_announcement() );
set_theme_mod( 'lasem_announcement_enabled', true );

// Menus.
$page_items = function ( $slugs ) use ( $lasem_pages, $ids ) {
	$items = array();
	foreach ( $slugs as $slug ) {
		$title = $lasem_pages[ $slug ]['title'] ?? null;
		if ( ! $title ) {
			foreach ( $lasem_pages as $parent ) {
				if ( isset( $parent['children'][ $slug ] ) ) {
					$title = $parent['children'][ $slug ]['title'];
				}
			}
		}
		$items[] = array(
			'title' => $title,
			'page'  => $ids[ $slug ],
		);
	}
	return $items;
};

$bakery   = array_keys( $lasem_pages['bakery']['children'] );
$gallery  = array_keys( $lasem_pages['gallery']['children'] );
$awards   = array_keys( $lasem_pages['awards']['children'] );
$policies = array( 'terms-conditions', 'returns-exchanges', 'cancellation-policy', 'shipping', 'privacy-policy' );

lasem_seed_menu(
	'Header',
	'header',
	array(
		array( 'title' => 'About Us', 'page' => $ids['about-us'] ),
		array( 'title' => 'Bakery', 'url' => '#', 'children' => $page_items( $bakery ) ),
		array( 'title' => 'Gallery', 'url' => '#', 'children' => $page_items( $gallery ) ),
		array( 'title' => 'Articles & Videos', 'page' => $ids['articles-videos'] ),
		array( 'title' => 'Awards', 'url' => '#', 'children' => $page_items( $awards ) ),
		array( 'title' => 'Testimonials', 'page' => $ids['testimonials'] ),
		array( 'title' => 'Order Online', 'url' => $order_url, 'children' => $page_items( $policies ) ),
	)
);

lasem_seed_menu(
	'Footer',
	'footer',
	array(
		array(
			'title'    => 'La Sem Bakery',
			'url'      => '#',
			'children' => array(
				array( 'title' => 'Home', 'page' => $ids['home'] ),
				array( 'title' => 'Awards', 'page' => $ids['top-choice-award'] ),
				array( 'title' => 'Bakery', 'page' => $ids['cake-category'] ),
				array( 'title' => 'Testimonials', 'page' => $ids['testimonials'] ),
				array( 'title' => 'Gallery', 'page' => $ids['birthday-novelty'] ),
				array( 'title' => 'Articles & Videos', 'page' => $ids['articles-videos'] ),
				array( 'title' => 'About Us', 'page' => $ids['about-us'] ),
				array( 'title' => 'Contact Us', 'page' => $ids['contact-us'] ),
			),
		),
		array(
			'title'    => 'Gallery',
			'url'      => '#',
			'children' => $page_items( array_diff( $gallery, array( 'cookies-other' ) ) ),
		),
		array(
			'title'    => 'ORDER ONLINE',
			'url'      => $order_url,
			'children' => $page_items( array( 'shipping', 'returns-exchanges', 'cancellation-policy' ) ),
		),
	)
);

flush_rewrite_rules();
WP_CLI::success( 'La Sem site seeded: ' . count( $ids ) . ' pages.' );
