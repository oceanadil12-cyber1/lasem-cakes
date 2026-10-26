<?php
/**
 * Site header: awning, logo and main menu.
 *
 * @package lasemcakes
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'lasemcakes' ); ?></a>

<div class="wrapper">
	<header class="site-header">
		<div class="header-content">
			<div class="site-logo">
				<?php lasem_logo(); ?>
			</div>

			<?php if ( has_nav_menu( 'header' ) ) : ?>
				<button type="button" class="menu-toggle" aria-controls="site-navigation" aria-expanded="false">
					<span class="menu-toggle-bars" aria-hidden="true"></span>
					<?php esc_html_e( 'Menu', 'lasemcakes' ); ?>
				</button>
				<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Main menu', 'lasemcakes' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'header',
							'container'      => false,
							'menu_class'     => 'menu',
							'depth'          => 2,
						)
					);
					?>
				</nav>
			<?php endif; ?>
		</div>
	</header>

	<div id="content" class="site-content">
