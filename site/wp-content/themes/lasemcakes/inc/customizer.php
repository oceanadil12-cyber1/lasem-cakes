<?php
/**
 * Customizer settings: announcement pop-up, ordering link, social links.
 *
 * @package lasemcakes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Customizer sections and settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function lasem_customize_register( $wp_customize ) {
	$wp_customize->add_panel(
		'lasem_options',
		array(
			'title'    => __( 'La Sem options', 'lasemcakes' ),
			'priority' => 30,
		)
	);

	// Announcement pop-up.
	$wp_customize->add_section(
		'lasem_announcement',
		array(
			'title'       => __( 'Announcement pop-up', 'lasemcakes' ),
			'description' => __( 'The pop-up shows the content of the page you pick. Edit that page to change the message.', 'lasemcakes' ),
			'panel'       => 'lasem_options',
		)
	);

	$wp_customize->add_setting(
		'lasem_announcement_enabled',
		array(
			'default'           => false,
			'sanitize_callback' => 'wp_validate_boolean',
		)
	);
	$wp_customize->add_control(
		'lasem_announcement_enabled',
		array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the announcement pop-up', 'lasemcakes' ),
			'section' => 'lasem_announcement',
		)
	);

	$wp_customize->add_setting(
		'lasem_announcement_page',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		'lasem_announcement_page',
		array(
			'type'    => 'dropdown-pages',
			'label'   => __( 'Page with the announcement', 'lasemcakes' ),
			'section' => 'lasem_announcement',
		)
	);

	$wp_customize->add_setting(
		'lasem_announcement_where',
		array(
			'default'           => 'home',
			'sanitize_callback' => 'lasem_sanitize_where',
		)
	);
	$wp_customize->add_control(
		'lasem_announcement_where',
		array(
			'type'    => 'radio',
			'label'   => __( 'Show it on', 'lasemcakes' ),
			'section' => 'lasem_announcement',
			'choices' => array(
				'home' => __( 'Home page only', 'lasemcakes' ),
				'all'  => __( 'Every page', 'lasemcakes' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lasem_announcement_frequency',
		array(
			'default'           => 1,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		'lasem_announcement_frequency',
		array(
			'type'        => 'number',
			'label'       => __( 'Show again after (days)', 'lasemcakes' ),
			'description' => __( '0 = every visit. Editing the page shows it again to everyone.', 'lasemcakes' ),
			'section'     => 'lasem_announcement',
			'input_attrs' => array(
				'min' => 0,
				'max' => 60,
			),
		)
	);

	// Ordering.
	$wp_customize->add_section(
		'lasem_ordering',
		array(
			'title' => __( 'Online ordering', 'lasemcakes' ),
			'panel' => 'lasem_options',
		)
	);
	$wp_customize->add_setting(
		'lasem_order_url',
		array(
			'default'           => LASEM_DEFAULT_ORDER_URL,
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'lasem_order_url',
		array(
			'type'    => 'url',
			'label'   => __( 'Order Online link', 'lasemcakes' ),
			'section' => 'lasem_ordering',
		)
	);

	// Social links.
	$wp_customize->add_section(
		'lasem_social',
		array(
			'title' => __( 'Social links', 'lasemcakes' ),
			'panel' => 'lasem_options',
		)
	);
	foreach ( array(
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'twitter'   => 'X (Twitter)',
	) as $key => $label ) {
		$wp_customize->add_setting(
			'lasem_social_' . $key,
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			'lasem_social_' . $key,
			array(
				'type'    => 'url',
				'label'   => $label,
				'section' => 'lasem_social',
			)
		);
	}
}
add_action( 'customize_register', 'lasem_customize_register' );

/**
 * Sanitize the "show on" choice.
 *
 * @param string $value Choice.
 * @return string
 */
function lasem_sanitize_where( $value ) {
	return in_array( $value, array( 'home', 'all' ), true ) ? $value : 'home';
}
