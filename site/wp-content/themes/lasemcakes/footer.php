<?php
/**
 * Site footer: menu columns, contact details and the bottom bar.
 *
 * @package lasemcakes
 */

?>
	</div><!-- #content -->

	<footer class="site-footer">
		<div class="footer-content">
			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<nav class="footer-navigation" aria-label="<?php esc_attr_e( 'Footer menu', 'lasemcakes' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => false,
							'menu_class'     => 'menu',
							'depth'          => 2,
						)
					);
					?>
				</nav>
			<?php endif; ?>

			<div class="footer-contact-us">
				<?php
				if ( is_active_sidebar( 'footer-contact' ) ) {
					dynamic_sidebar( 'footer-contact' );
				} else {
					lasem_footer_contact_fallback();
				}
				?>
			</div>
		</div>

		<div class="footer-bottom">
			<div class="social-copyright">
				&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php esc_html_e( 'La Sem Patisserie', 'lasemcakes' ); ?>
				<?php lasem_social_links(); ?>
			</div>
			<div class="legal">
				<?php
				$privacy = get_page_by_path( 'privacy-policy' );
				$terms   = get_page_by_path( 'terms-conditions' );
				if ( $privacy ) {
					printf( '<a href="%s">%s</a>', esc_url( get_permalink( $privacy ) ), esc_html( get_the_title( $privacy ) ) );
				}
				if ( $terms ) {
					printf( '<a href="%s">%s</a>', esc_url( get_permalink( $terms ) ), esc_html( get_the_title( $terms ) ) );
				}
				?>
			</div>
			<div class="designed-by">
				<?php echo wp_kses_post( get_theme_mod( 'lasem_footer_credit', '' ) ); ?>
			</div>
		</div>
	</footer>
</div><!-- .wrapper -->

<?php lasem_announcement(); ?>
<?php wp_footer(); ?>
</body>
</html>
