<?php
/**
 * Plugin Name: WP REST API - Menu Endpoints
 * Description: Menu endpoints for the WP REST API
 * Author: WP REST API Team
 * Author URI: http://wp-api.org
 * Version: 0.1.0
 * Plugin URI: https://github.com/WP-API/wp-api-menus-endpoints
 * License: GPL2+
 */

if ( class_exists( 'WP_REST_Controller' )
	&& ! class_exists( 'WP_REST_Menus_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-wp-rest-menus-controller.php';
}

if ( class_exists( 'WP_REST_Controller' )
	&& ! class_exists( 'WP_REST_Menu_Items_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-wp-rest-menu-items-controller.php';
}
