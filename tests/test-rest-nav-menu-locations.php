<?php

class WP_Test_REST_Nav_Menu_Locations_Controller extends WP_Test_REST_Controller_Testcase {

	/**
	 * Set up.
	 */
	function setUp() {
		parent::setUp();

		// Unregister all nav menu locations.
		foreach ( array_keys( get_registered_nav_menus() ) as $location ) {
			unregister_nav_menu( $location );
		}
	}

	/**
	 * Register nav menu locations.
	 *
	 * @param array $locations Location slugs.
	 */
	function register_nav_menu_locations( $locations ) {
		foreach ( $locations as $location ) {
			register_nav_menu( $location, ucfirst( $location ) );
		}
	}

	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wp/v2/menu-locations', $routes );
		$this->assertCount( 1, $routes['/wp/v2/menu-locations'] );
		$this->assertArrayHasKey( '/wp/v2/menu-locations/(?P<location>[\w-])', $routes );
		$this->assertCount( 1, $routes['/wp/v2/menu-locations/(?P<location>[\w-])'] );
	}

	public function test_context_param() {
		// Collection
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/menu-locations' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
		$menu_id = 'primary';
		$this->register_nav_menu_locations( array( $menu_id ) );
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/menu-locations/' . $menu_id );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	public function test_get_items() {
		$menus =  array( 'primary', 'secondary' ) ;
		$this->register_nav_menu_locations( array( 'primary', 'secondary' ) );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/menu-locations' );
		$response = rest_get_server()->dispatch( $request );
		$data = $response->get_data();
		$this->assertCount( 2, $data );
		$names = wp_list_pluck( $data, 'name' );
		$this->assertEquals( $menus, $names );
	}

	public function test_get_item() {
		$menu_id = 'primary';
		$this->register_nav_menu_locations( array( $menu_id ) );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/menu-locations/' . $menu_id );
		$response = rest_get_server()->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( $menu_id, $data['name'] );
	}

	public function test_create_item() {
	}

	public function test_update_item() {
	}

	public function test_delete_item() {
	}

	public function test_prepare_item() {
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wp/v2/menu-locations' );
		$response   = rest_get_server()->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 3, count( $properties ) );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'menu_id', $properties );
	}
}
