<?php

class WP_Test_REST_Nav_Menus_Controller extends WP_Test_REST_Controller_Testcase {

	public function test_register_routes() {
	}

	public function test_context_param() {
	}

	public function test_get_items() {
	}

	public function test_get_item() {
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
	}

	public function test_get_item_links() {
		$nav_menu_id = wp_update_nav_menu_object(
			0,
			array(
				'description' => 'Foo Menu',
				'menu-name'   => 'Foo Menu',
			)
		);

		register_nav_menu( 'foo', 'Bar' );

		set_theme_mod( 'nav_menu_locations', array( 'foo' => $nav_menu_id ) );

		$request  = new WP_REST_Request( 'GET', sprintf( '/wp/v2/menus/%d', $nav_menu_id ) );
		$response = rest_get_server()->dispatch( $request );

		$links = $response->get_links();
		$this->assertArrayHasKey( 'https://api.w.org/location', $links );

		$location_url = rest_url( '/wp/v2/menu-locations/foo' );
		$this->assertEquals($location_url, $links['https://api.w.org/location'][0]['href']);
	}

}
