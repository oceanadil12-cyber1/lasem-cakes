<?php
/**
 * Post lists: Articles & Videos (the posts page), categories, dates and search.
 *
 * @package lasemcakes
 */

get_header();

if ( is_home() && ! is_front_page() ) {
	$lasem_title = single_post_title( '', false );
} elseif ( is_search() ) {
	/* translators: %s: search terms */
	$lasem_title = sprintf( __( 'Search: %s', 'lasemcakes' ), get_search_query() );
} elseif ( is_archive() ) {
	$lasem_title = get_the_archive_title();
} else {
	$lasem_title = __( 'Articles & Videos', 'lasemcakes' );
}
?>

<div class="page-header">
	<div class="page-title">
		<h1><?php echo wp_kses_post( $lasem_title ); ?></h1>
	</div>
</div>

<div class="content-wrapper">
	<div class="blog-layout">
		<main id="main" class="site-main">
			<?php if ( have_posts() ) : ?>
				<div class="post-wrapper">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content', 'excerpt' );
					endwhile;
					?>
				</div>
				<?php
				the_posts_pagination(
					array(
						'prev_text' => __( '&laquo; Newer', 'lasemcakes' ),
						'next_text' => __( 'Older &raquo;', 'lasemcakes' ),
					)
				);
				?>
			<?php else : ?>
				<p><?php esc_html_e( 'Nothing found here yet.', 'lasemcakes' ); ?></p>
				<?php get_search_form(); ?>
			<?php endif; ?>
		</main>

		<?php get_sidebar(); ?>
	</div>
</div>

<?php
get_footer();
