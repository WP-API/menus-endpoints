<?php
/**
 * Menu endpoints for the WP REST API.
 *
 * Plugin Name: WordPress REST API Menus Endpoints
 * Description: REST endpoints for accessing and interacting with Navigation Menus.
 * Author: WP REST API Team
 * Author URI: http://wp-api.org
 * Version: 0.4.0
 * Plugin URI: https://github.com/WP-API/menus-endpoints
 * License: GPL2+
 *
 * @package         REST_API
 * @author          WP REST API Team
 * @license         GPL-2.0+
 */

add_action( 'rest_api_init', 'wp_api_nav_menus_init_controllers', 0 );

/**
 * Bootstrap endpoints.
 */
function wp_api_nav_menus_init_controllers() {
	if ( ! class_exists( 'WP_REST_Controller' ) ) {
		return;
	}

	if ( ! class_exists( 'WP_REST_Menus_Controller' ) ) {
		require_once dirname( __FILE__ ) . '/lib/class-wp-rest-menus-controller.php';
	}
	if ( ! class_exists( 'WP_REST_Menu_Items_Controller' ) ) {
		require_once dirname( __FILE__ ) . '/lib/class-wp-rest-menu-items-controller.php';
	}

	if ( ! class_exists( 'WP_REST_Menu_Locations_Controller' ) ) {
		require_once dirname( __FILE__ ) . '/lib/class-wp-rest-menu-locations-controller.php';
	}

	$nav_menu_location = new WP_REST_Menu_Locations_Controller();
	$nav_menu_location->register_routes();
}


add_filter( 'register_post_type_args', 'wp_api_nav_menus_post_type_args', 10, 2 );

/**
 * Hook in to the nav menu item post type and enable a post type rest endpoint.
 *
 * @param array  $args Current registered post type args.
 * @param string $post_type Name of post type.
 *
 * @return array
 */
function wp_api_nav_menus_post_type_args( $args, $post_type ) {
	if ( 'nav_menu_item' === $post_type ) {
		$args['show_in_rest']          = true;
		$args['rest_base']             = 'menu-items';
		$args['rest_controller_class'] = 'WP_REST_Menu_Items_Controller';
	}

	return $args;
}


add_filter( 'register_taxonomy_args', 'wp_api_nav_menus_taxonomy_args', 10, 2 );

/**
 * Hook in to the nav_menu taxonomy and enable a taxonomy rest endpoint.
 *
 * @param array  $args Current registered taxonomy args.
 * @param string $taxonomy Name of taxonomy.
 *
 * @return array
 */
function wp_api_nav_menus_taxonomy_args( $args, $taxonomy ) {
	if ( 'nav_menu' === $taxonomy ) {
		$args['show_in_rest']          = true;
		$args['rest_base']             = 'menus';
		$args['rest_controller_class'] = 'WP_REST_Menus_Controller';
	}

	return $args;
}
