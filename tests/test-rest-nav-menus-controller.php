<?php

class WP_Test_REST_Nav_Menus_Controller extends WP_Test_REST_Controller_Testcase {

	/**
	 * @var int
	 */
	public $menu_id;

	protected static $admin_id;

	protected static $subscriber_id;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id      = $factory->user->create(
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

	public function setUp() {
		parent::setUp();

		// Unregister all nav menu locations.
		foreach ( array_keys( get_registered_nav_menus() ) as $location ) {
			unregister_nav_menu( $location );
		}

		$orig_args = array(
			'name'        => 'Original Name',
			'description' => 'Original Description',
			'slug'        => 'original-slug',
			'taxonomy'    => 'nav_menu',
		);

		$this->menu_id = $this->factory->term->create( $orig_args );
	}

	/**
	 * Register nav menu locations.
	 *
	 * @param array $locations Location slugs.
	 */
	public function register_nav_menu_locations( $locations ) {
		foreach ( $locations as $location ) {
			register_nav_menu( $location, ucfirst( $location ) );
		}
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

	public function test_create_item_with_location_permission_correct() {
		$this->register_nav_menu_locations( array( 'primary', 'secondary' ) );
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'POST', '/wp/v2/menus' );
		$request->set_param( 'name', 'My Awesome Term' );
		$request->set_param( 'slug', 'so-awesome' );
		$request->set_param( 'locations', 'primary' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );
		$data      = $response->get_data();
		$term_id   = $data['id'];
		$locations = get_nav_menu_locations();
		$this->assertEquals( $locations['primary'], $term_id );
	}

	public function test_create_item_with_location_permission_incorrect() {
		wp_set_current_user( self::$subscriber_id );
		$request = new WP_REST_Request( 'POST', '/wp/v2/menus' );
		$request->set_param( 'name', 'My Awesome Term' );
		$request->set_param( 'slug', 'so-awesome' );
		$request->set_param( 'locations', 'primary' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( rest_authorization_required_code(), $response->get_status() );
		$this->assertErrorResponse( 'rest_cannot_assign_location', $response, rest_authorization_required_code() );
	}

	public function test_create_item_with_location_permission_no_location() {
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'POST', '/wp/v2/menus' );
		$request->set_param( 'name', 'My Awesome Term' );
		$request->set_param( 'slug', 'so-awesome' );
		$request->set_param( 'locations', 'bar' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertErrorResponse( 'rest_menu_location_invalid', $response, 400 );
	}

	public function test_update_item_with_no_location() {
		$this->register_nav_menu_locations( array( 'primary', 'secondary' ) );
		wp_set_current_user( self::$admin_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/menus/' . $this->menu_id );
		$request->set_param( 'name', 'New Name' );
		$request->set_param( 'description', 'New Description' );
		$request->set_param( 'slug', 'new-slug' );
		$request->set_param( 'locations', 'bar' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_update_item_with_location_permission_correct() {
		$this->register_nav_menu_locations( array( 'primary', 'secondary' ) );
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'POST', '/wp/v2/menus/' . $this->menu_id );
		$request->set_param( 'name', 'New Name' );
		$request->set_param( 'description', 'New Description' );
		$request->set_param( 'slug', 'new-slug' );
		$request->set_param( 'locations', 'primary' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$locations = get_nav_menu_locations();
		$this->assertEquals( $locations['primary'], $this->menu_id );
	}

	public function test_update_item_with_location_permission_incorrect() {
		$this->register_nav_menu_locations( array( 'primary', 'secondary' ) );
		wp_set_current_user( self::$subscriber_id );
		$request = new WP_REST_Request( 'POST', '/wp/v2/menus/' . $this->menu_id );
		$request->set_param( 'name', 'New Name' );
		$request->set_param( 'description', 'New Description' );
		$request->set_param( 'slug', 'new-slug' );
		$request->set_param( 'locations', 'primary' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( rest_authorization_required_code(), $response->get_status() );
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

	public function test_change_menu_location() {
		$this->register_nav_menu_locations( array( 'primary', 'secondary' ) );
		$secondary_id = self::factory()->term->create(
			array(
				'name'        => 'Secondary Name',
				'description' => 'Secondary Description',
				'slug'        => 'secondary-slug',
				'taxonomy'    => 'nav_menu',
			)
		);

		$locations              = get_nav_menu_locations();
		$locations['primary']   = $this->menu_id;
		$locations['secondary'] = $secondary_id;
		set_theme_mod( 'nav_menu_locations', $locations );

		wp_set_current_user( self::$admin_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/menus/' . $this->menu_id );
		$request->set_body_params(
			array(
				'locations' => array( 'secondary' ),
			)
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$locations = get_nav_menu_locations();
		$this->assertArrayNotHasKey( 'primary', $locations );
		$this->assertArrayHasKey( 'secondary', $locations );
		$this->assertEquals( $this->menu_id, $locations['secondary'] );
	}

	public function test_get_items_no_permission() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menus' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 401 );
	}

	public function test_get_item_no_permission() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menus/' . $this->menu_id );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 401 );
	}

	public function test_get_items_wrong_permission() {
		wp_set_current_user( self::$subscriber_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menus' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 403 );
	}

	public function test_get_item_wrong_permission() {
		wp_set_current_user( self::$subscriber_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menus/' . $this->menu_id );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 403 );
	}
}
