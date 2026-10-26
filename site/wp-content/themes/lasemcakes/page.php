<?php
/**
 * Default page: script title on white, then the content.
 *
 * @package lasemcakes
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="page-header">
		<div class="page-title">
			<h1><?php the_title(); ?></h1>
		</div>
	</div>

	<main class="content-wrapper">
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php
			the_content();
			wp_link_pages();
			?>
		</article>
	</main>
	<?php
endwhile;

get_footer();
