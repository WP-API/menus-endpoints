<?php
/**
 * Plugin Name: WP REST API - Menus and Widgets Endpoints
 * Description: Menu endpoints for the WP REST API
 * Author: WP REST API Team
 * Author URI: http://wp-api.org
 * Version: 0.1.0
 * Plugin URI: https://github.com/WP-API/wp-api-menus-widgets-endpoints
 * License: GPL2+
 */

add_action( 'rest_api_init', 'wp_api_nav_menus_widgets_init_controllers', 0 );

function wp_api_nav_menus_widgets_init_controllers() {

	if ( ! class_exists( 'WP_REST_Controller' ) ) {
		return;
	}

	if ( ! class_exists( 'WP_REST_Nav_Menus_Controller' ) ) {
		require_once dirname( __FILE__ ) . '/lib/class-wp-rest-nav-menus-controller.php';
	}

	if ( ! class_exists( 'WP_REST_Nav_Menu_Items_Controller' ) ) {
		require_once dirname( __FILE__ ) . '/lib/class-wp-rest-nav-menu-items-controller.php';
	}

	if ( ! class_exists( 'WP_REST_Nav_Menu_Locations_Controller' ) ) {
		require_once dirname( __FILE__ ) . '/lib/class-wp-rest-nav-menu-locations-controller.php';
	}

	if ( ! class_exists( 'WP_REST_Nav_Menu_Settings_Controller' ) ) {
		require_once dirname( __FILE__ ) . '/lib/class-wp-rest-nav-menu-settings-controller.php';
	}

	if ( ! class_exists( 'WP_REST_Widgets_Controller' ) ) {
		require_once dirname( __FILE__ ) . '/lib/class-wp-rest-widgets-controller.php';
	}

	global $wp_taxonomies;
	$wp_taxonomies['nav_menu']->show_in_rest          = true;
	$wp_taxonomies['nav_menu']->rest_base             = 'menus';
	$wp_taxonomies['nav_menu']->rest_controller_class = 'WP_REST_Nav_Menus_Controller';

	$nav_menu_controller = new WP_REST_Nav_Menus_Controller( 'nav_menu' );
	$nav_menu_controller->register_routes();

	$nav_menu_item_controller = new WP_REST_Nav_Menu_Items_Controller( 'nav_menu_item' );
	$nav_menu_item_controller->register_routes();

	$nav_menu_location_controller = new WP_REST_Nav_Menu_Locations_Controller();
	$nav_menu_location_controller->register_routes();

	$nav_menu_settings_controller = new WP_REST_Nav_Menu_Settings_Controller();
	$nav_menu_settings_controller->register_routes();

	/**
	 * @type WP_Widget_Factory $wp_widget_factory
	 */
	global $wp_widget_factory;

	$widgets_controller = new WP_REST_Widgets_Controller( $wp_widget_factory->widgets );
	$widgets_controller->register_routes();
}

add_filter( 'register_post_type_args', 'wp_api_nav_menus_widgets_post_type_args', 10, 2 );

function wp_api_nav_menus_widgets_post_type_args( $args, $post_type ) {
	if ( 'nav_menu_item' === $post_type ) {
		$args['show_in_rest']    = true;
		$args['rest_base']       = 'menu-items';
		$args['rest_controller'] = 'WP_REST_Menu_Items_Controller';
	}

	return $args;
}
