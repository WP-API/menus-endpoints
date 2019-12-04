<?php

class WP_Test_REST_Nav_Menus_Controller extends WP_Test_REST_Controller_Testcase {

	protected static $administrator;
	protected static $contributor;
	protected static $term_id;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$administrator = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		self::$contributor   = $factory->user->create(
			array(
				'role' => 'contributor',
			)
		);
	}


	/**
	 * Set up.
	 */
	function setUp() {
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

		self::$term_id = $this->factory->term->create( $orig_args );
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
		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'POST', '/wp/v2/menus' );
		$request->set_param( 'name', 'My Awesome Term' );
		$request->set_param( 'slug', 'so-awesome' );
		$request->set_param( 'locations', 'primary' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );
		$data    = $response->get_data();
		$term_id = $data['id'];
		$locations = get_nav_menu_locations();
		$this->assertEquals( $locations['primary'], $term_id );
	}

	public function test_create_item_with_location_permisson_incorrect() {
		wp_set_current_user( self::$contributor );
		$request = new WP_REST_Request( 'POST', '/wp/v2/menus' );
		$request->set_param( 'name', 'My Awesome Term' );
		$request->set_param( 'slug', 'so-awesome' );
		$request->set_param( 'locations', 'primary' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( rest_authorization_required_code(), $response->get_status() );
		$this->assertErrorResponse( 'rest_cannot_assign_location', $response, rest_authorization_required_code() );
	}

	public function test_create_item_with_location_permisson_no_location() {
		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'POST', '/wp/v2/menus' );
		$request->set_param( 'name', 'My Awesome Term' );
		$request->set_param( 'slug', 'so-awesome' );
		$request->set_param( 'locations', 'bar' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertErrorResponse( 'rest_menu_location_invalid', $response, 404 );
	}

	public function test_update_item_with_no_location() {
		$this->register_nav_menu_locations( array( 'primary', 'secondary' ) );
		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'POST', '/wp/v2/menus/' . self::$term_id );
		$request->set_param( 'name', 'New Name' );
		$request->set_param( 'description', 'New Description' );
		$request->set_param( 'slug', 'new-slug' );
		$request->set_param( 'locations', 'bar' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_update_item_with_location_permisson_correct() {
		$this->register_nav_menu_locations( array( 'primary', 'secondary' ) );
		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'POST', '/wp/v2/menus/' . self::$term_id );
		$request->set_param( 'name', 'New Name' );
		$request->set_param( 'description', 'New Description' );
		$request->set_param( 'slug', 'new-slug' );
		$request->set_param( 'locations', 'primary' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$locations = get_nav_menu_locations();
		$this->assertEquals( $locations['primary'], self::$term_id );
	}

	public function test_update_item_with_location_permisson_incorrect() {
		$this->register_nav_menu_locations( array( 'primary', 'secondary' ) );
		wp_set_current_user( self::$contributor );
		$request = new WP_REST_Request( 'POST', '/wp/v2/menus/' . self::$term_id );
		$request->set_param( 'name', 'New Name' );
		$request->set_param( 'description', 'New Description' );
		$request->set_param( 'slug', 'new-slug' );
		$request->set_param( 'locations', 'primary' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( rest_authorization_required_code(), $response->get_status() );
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
		$locations['primary']   = self::$term_id;
		$locations['secondary'] = $secondary_id;
		set_theme_mod( 'nav_menu_locations', $locations );

		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'PUT', '/wp/v2/menus/' . self::$term_id );
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
		$this->assertEquals( self::$term_id, $locations['secondary'] );
	}
}
