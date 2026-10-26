<?php
/**
 * Articles & Videos sidebar.
 *
 * @package lasemcakes
 */

?>
<aside class="widget-area" aria-label="<?php esc_attr_e( 'Articles & Videos sidebar', 'lasemcakes' ); ?>">
	<?php if ( is_active_sidebar( 'blog-sidebar' ) ) : ?>
		<?php dynamic_sidebar( 'blog-sidebar' ); ?>
	<?php else : ?>
		<section class="widget widget_search">
			<?php get_search_form(); ?>
		</section>
		<section class="widget widget_categories">
			<h2 class="widget-title"><?php esc_html_e( 'Categories', 'lasemcakes' ); ?></h2>
			<ul><?php wp_list_categories( array( 'title_li' => '' ) ); ?></ul>
		</section>
		<section class="widget widget_archive">
			<h2 class="widget-title"><?php esc_html_e( 'Archives', 'lasemcakes' ); ?></h2>
			<ul><?php wp_get_archives( array( 'type' => 'yearly' ) ); ?></ul>
		</section>
	<?php endif; ?>
</aside>
