<?php
/**
 * Page not found.
 *
 * @package lasemcakes
 */

get_header();
?>

<div class="page-header">
	<div class="page-title">
		<h1><?php esc_html_e( 'Page not found', 'lasemcakes' ); ?></h1>
	</div>
</div>

<main class="content-wrapper">
	<p><?php esc_html_e( 'Sorry, we couldn\'t find that page. Try the menu above, or search below.', 'lasemcakes' ); ?></p>
	<?php get_search_form(); ?>
</main>

<?php
get_footer();
