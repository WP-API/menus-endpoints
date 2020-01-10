<?php

class WP_Test_REST_Nav_Menus_Controller extends WP_Test_REST_Controller_Testcase {
	/**
	 * @var int
	 */
	public $menu_id;

	protected static $admin_id;

	protected static $subscriber_id;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		self::$subscriber_id = $factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);
	}

	function setUp() {
		parent::setUp();
		$this->menu_id = wp_create_nav_menu( rand_str() );
	}

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
		wp_set_current_user( self::$admin_id );

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
		$this->assertArrayHasKey( 'https://api.w.org/menu-location', $links );

		$location_url = rest_url( '/wp/v2/menu-locations/foo' );
		$this->assertEquals( $location_url, $links['https://api.w.org/menu-location'][0]['href'] );
	}

	public function test_get_items_no_permission() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/menus' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 401 );
	}

	public function test_get_item_no_permission() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/menus/' . $this->menu_id  );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 401 );
	}

	public function test_get_items_wrong_permission() {
		wp_set_current_user( self::$subscriber_id );
		$request = new WP_REST_Request( 'GET', '/wp/v2/menus' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 403 );
	}

	public function test_get_item_wrong_permission() {
		wp_set_current_user( self::$subscriber_id );
		$request = new WP_REST_Request( 'GET', '/wp/v2/menus/' . $this->menu_id  );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 403 );
	}

}
