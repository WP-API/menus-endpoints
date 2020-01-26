<?php

class WP_Test_REST_Nav_Menu_Items_Controller extends WP_Test_REST_Controller_Testcase {
	/**
	 * @var int
	 */
	public $menu_id;
	public $tag_id;
	public $menu_item_id;

	/**
	 * @var
	 */
	protected static $admin_id;

	/**
	 * @var
	 */
	protected static $subscriber_id;

	/**
	 * @param $factory
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$tag_id = self::factory()->tag->create();

		self::$menu_id = wp_create_nav_menu( rand_str() );

		self::$menu_item_id = wp_update_nav_menu_item(
			self::$menu_id,
			0,
			array(
				'menu-item-type'      => 'taxonomy',
				'menu-item-object'    => 'post_tag',
				'menu-item-object-id' => self::$tag_id,
				'menu-item-status'    => 'publish',
			)
		);

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

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 *
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/wp/v2/menu-items', $routes );
		$this->assertCount( 2, $routes['/wp/v2/menu-items'] );
		$this->assertArrayHasKey( '/wp/v2/menu-items/(?P<id>[\d]+)', $routes );
		$this->assertCount( 3, $routes['/wp/v2/menu-items/(?P<id>[\d]+)'] );
	}

	public function test_context_param() {
		// Collection
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/menu-items' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
		// Single
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/menu-items/' . self::$menu_item_id );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	public function test_registered_query_params() {
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/menu-items' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$keys     = array_keys( $data['endpoints'][0]['args'] );
		sort( $keys );
		$this->assertEquals(
			array(
				'after',
				'author',
				'author_exclude',
				'before',
				'categories',
				'categories_exclude',
				'context',
				'exclude',
				'include',
				'offset',
				'order',
				'orderby',
				'page',
				'per_page',
				'search',
				'slug',
				'status',
				'sticky',
				'tags',
				'tags_exclude',
				'tax_relation',
			),
			$keys
		);
	}

	public function test_registered_get_item_params() {
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( '/wp/v2/menu-items/%d', self::$menu_item_id ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$keys     = array_keys( $data['endpoints'][0]['args'] );
		sort( $keys );
		$this->assertEquals( array( 'context', 'id', 'password' ), $keys );
	}

	/**
	 * @ticket 43701
	 */
	public function test_allow_header_sent_on_options_request() {
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( '/wp/v2/menu-items/%d', self::$menu_item_id ) );
		$response = rest_get_server()->dispatch( $request );
		$response = apply_filters( 'rest_post_dispatch', $response, rest_get_server(), $request );
		$headers  = $response->get_headers();

		$this->assertNotEmpty( $headers['Allow'] );
		$this->assertEquals( $headers['Allow'], 'GET' );

		wp_set_current_user( self::$admin_id );

		$request  = new WP_REST_Request( 'OPTIONS', sprintf( '/wp/v2/menu-items/%d', self::$menu_item_id ) );
		$response = rest_get_server()->dispatch( $request );
		$response = apply_filters( 'rest_post_dispatch', $response, rest_get_server(), $request );
		$headers  = $response->get_headers();

		$this->assertNotEmpty( $headers['Allow'] );
		$this->assertEquals( $headers['Allow'], 'GET, POST, PUT, PATCH, DELETE' );
	}

	public function test_get_items() {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menu-items' );
		$response = rest_get_server()->dispatch( $request );

		$this->check_get_posts_response( $response );
	}

	/**
	 *
	 */
	public function test_get_item() {
	}

	/**
	 *
	 */
	public function test_create_item() {
	}

	/**
	 *
	 */
	public function test_update_item() {
	}

	/**
	 *
	 */
	public function test_delete_item() {
	}

	/**
	 *
	 */
	public function test_prepare_item() {
	}

	/**
	 *
	 */
	public function test_get_item_schema() {
	}

	/**
	 *
	 */
	public function test_get_items_no_permission() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menu-items' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 401 );
	}

	/**
	 *
	 */
	public function test_get_item_no_permission() {
		$post_id      = self::factory()->post->create();
		$menu_item_id = wp_update_nav_menu_item(
			$this->menu_id,
			0,
			array(
				'menu-item-type'      => 'post_type',
				'menu-item-object'    => 'post',
				'menu-item-object-id' => $post_id,
				'menu-item-status'    => 'publish',
			)
		);
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menu-items/' . $menu_item_id );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 401 );
	}

	/**
	 *
	 */
	public function test_get_items_wrong_permission() {
		wp_set_current_user( self::$subscriber_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menu-items' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 403 );
	}

	/**
	 *
	 */
	public function test_get_item_wrong_permission() {
		$post_id      = self::factory()->post->create();
		$menu_item_id = wp_update_nav_menu_item(
			$this->menu_id,
			0,
			array(
				'menu-item-type'      => 'post_type',
				'menu-item-object'    => 'post',
				'menu-item-object-id' => $post_id,
				'menu-item-status'    => 'publish',
			)
		);
		wp_set_current_user( self::$subscriber_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menu-items/' . $menu_item_id );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 403 );
	}
}
