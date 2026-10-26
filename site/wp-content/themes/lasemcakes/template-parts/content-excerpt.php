<?php
/**
 * One post in a list.
 *
 * @package lasemcakes
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' ); ?>
		<?php lasem_posted_on(); ?>
	</header>

	<?php if ( has_post_thumbnail() ) : ?>
		<a class="entry-featured-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'large' ); ?>
		</a>
	<?php endif; ?>

	<div class="entry-content">
		<?php the_excerpt(); ?>
		<div class="entry-continue-reading">
			<a href="<?php the_permalink(); ?>">
				<?php
				/* translators: %s: post title, for screen readers */
				printf( esc_html__( 'Continue reading%s...', 'lasemcakes' ), '<span class="screen-reader-text"> ' . esc_html( get_the_title() ) . '</span>' );
				?>
			</a>
		</div>
	</div>
</article>
