<?php

/**
 * Manage Menus for a WordPress site
 */
class WP_REST_Menus_Controller extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'wp/v2';
		$this->rest_base = 'menus';
	}

	public function register_routes() {
		// @todo
	}

	public function get_items_permissions_check( $request ) {

	}

	public function get_items( $request ) {

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
