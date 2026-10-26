<?php
/**
 * Template Name: Gallery page
 * Template Post Type: page
 *
 * One gallery category: the title centred over the photos (NextGEN or a core gallery block).
 *
 * @package lasemcakes
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="page-header page-header--center">
		<div class="page-title">
			<h1><?php the_title(); ?></h1>
		</div>
	</div>

	<main class="content-wrapper">
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php the_content(); ?>
		</article>
	</main>
	<?php
endwhile;

get_footer();
