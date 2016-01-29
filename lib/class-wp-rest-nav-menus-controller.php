<?php

/**
 * Manage Menus for a WordPress site
 */
class WP_REST_Nav_Menus_Controller extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'wp/v2';
		$this->rest_base = 'nav-menus';
	}

	public function register_routes() {
		// @todo

		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => $this->get_collection_params(),
			),

			'schema' => array( $this, 'get_public_item_schema' ),
		) );

	}

	public function get_items_permissions_check( $request ) {

		return true;

	}

	public function get_items( $request ) {

		$nav_menu_items = array();
		$response = rest_ensure_response( $nav_menu_items );
		return $response;

	}

	public function get_item_permissions_check( $request ) {

	}

	public function get_item( $request ) {

	}

	public function delete_item_permission_check( $request ) {

	}

	public function delete_item( $request ) {

	}

	public function prepare_item_for_response( $item, $request ) {

	}

	public function get_item_schema() {

	}

	public function get_collection_params() {

	}

}