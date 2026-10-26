<?php
/**
 * Home page: the page content edge to edge (the slider), as on the original site.
 *
 * @package lasemcakes
 */

get_header();
?>

<main class="content-wrapper content-wrapper--flush">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<h1 class="screen-reader-text"><?php bloginfo( 'name' ); ?></h1>
		<?php
		the_content();
	endwhile;
	?>
</main>

<?php
get_footer();
