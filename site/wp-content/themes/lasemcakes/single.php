<?php
/**
 * A single article or video post.
 *
 * @package lasemcakes
 */

get_header();

$lasem_posts_page = (int) get_option( 'page_for_posts' );
?>

<div class="page-header">
	<div class="page-title">
		<p class="section-name">
			<?php if ( $lasem_posts_page ) : ?>
				<a href="<?php echo esc_url( get_permalink( $lasem_posts_page ) ); ?>"><?php echo esc_html( get_the_title( $lasem_posts_page ) ); ?></a>
			<?php else : ?>
				<?php esc_html_e( 'Articles & Videos', 'lasemcakes' ); ?>
			<?php endif; ?>
		</p>
	</div>
</div>

<div class="content-wrapper">
	<div class="blog-layout">
		<main id="main" class="site-main">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<header class="entry-header">
						<?php the_title( '<h1 class="entry-title page-subtitle">', '</h1>' ); ?>
						<?php lasem_posted_on(); ?>
					</header>

					<?php if ( has_post_thumbnail() ) : ?>
						<div class="entry-featured-image"><?php the_post_thumbnail( 'large' ); ?></div>
					<?php endif; ?>

					<div class="entry-content">
						<?php
						the_content();
						wp_link_pages();
						?>
					</div>
				</article>

				<?php
				the_post_navigation(
					array(
						'prev_text' => '&laquo; %title',
						'next_text' => '%title &raquo;',
					)
				);

				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
			endwhile;
			?>
		</main>

		<?php get_sidebar(); ?>
	</div>
</div>

<?php
get_footer();
