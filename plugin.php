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

if ( class_exists( 'WP_REST_Controller' )
	&& ! class_exists( 'WP_REST_Nav_Menus_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-wp-rest-nav-menus-controller.php';
}

if ( class_exists( 'WP_REST_Controller' )
	&& ! class_exists( 'WP_REST_Nav_Menu_Items_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-wp-rest-nav-menu-items-controller.php';
}

add_action( 'rest_api_init', 'create_initial_nav_menu_routes', 0 );


function create_initial_nav_menu_routes() {

	$nav_menu_route = new WP_REST_Nav_Menus_Controller();
	$nav_menu_route->register_routes();

	$nav_menu_item_route = new WP_REST_Nav_Menu_Items_Controller();
	$nav_menu_item_route->register_routes();

}

if ( class_exists( 'WP_REST_Controller' )
	&& ! class_exists( 'WP_REST_Widgets_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-wp-rest-widgets-controller.php';
}

new WP_REST_Widgets_Controller();
