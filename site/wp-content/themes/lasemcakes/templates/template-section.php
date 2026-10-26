<?php
/**
 * Template Name: Section page
 * Template Post Type: page
 *
 * A page inside a section (Bakery, Awards): the section name as the big script
 * title, the section's pages beside it, then this page's title and content.
 * The page's own title is the H1 so search engines see the right heading.
 *
 * @package lasemcakes
 */

get_header();

while ( have_posts() ) :
	the_post();
	$lasem_root    = lasem_section_root();
	$lasem_is_root = ! $lasem_root || $lasem_root->ID === get_the_ID();
	?>
	<div class="page-header">
		<div class="page-title">
			<?php if ( $lasem_is_root ) : ?>
				<h1><?php the_title(); ?></h1>
			<?php else : ?>
				<p class="section-name"><a href="<?php echo esc_url( get_permalink( $lasem_root ) ); ?>"><?php echo esc_html( get_the_title( $lasem_root ) ); ?></a></p>
			<?php endif; ?>
		</div>
		<?php lasem_section_nav(); ?>
	</div>

	<main class="content-wrapper">
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php if ( ! $lasem_is_root ) : ?>
				<h1 class="entry-title page-subtitle"><?php the_title(); ?></h1>
			<?php endif; ?>
			<?php
			the_content();
			wp_link_pages();
			?>
		</article>
	</main>
	<?php
endwhile;

get_footer();
