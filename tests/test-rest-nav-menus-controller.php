<?php

class WP_Test_REST_Nav_Menus_Controller extends WP_Test_REST_Controller_Testcase {
	/**
	 * @var int
	 */
	public $menu_id;

	/**
	 * @var
	 */
	protected static $admin_id;

	/**
	 * @var
	 */
	protected static $subscriber_id;

	/**
	 *
	 */
	const TAXONOMY = 'nav_menu';

	/**
	 * @var int
	 */
	protected static $per_page = 50;

	/**
	 * @param $factory
	 */
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

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->menu_id = wp_create_nav_menu( rand_str() );
	}

	/**
	 *
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wp/v2/menus', $routes );
		$this->assertArrayHasKey( '/wp/v2/menus/(?P<id>[\d]+)', $routes );
	}

	/**
	 *
	 */
	public function test_context_param() {
		// Collection
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/menus' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEqualSets( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
		// Single
		$tag1     = $this->factory->tag->create( array( 'name' => 'Season 5' ) );
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/menus/' . $tag1 );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEqualSets( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	/**
	 *
	 */
	public function test_registered_query_params() {
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/menus' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$keys     = array_keys( $data['endpoints'][0]['args'] );
		sort( $keys );
		$this->assertEquals(
			array(
				'context',
				'exclude',
				'hide_empty',
				'include',
				'offset',
				'order',
				'orderby',
				'page',
				'per_page',
				'post',
				'search',
				'slug',
			),
			$keys
		);
	}

	/**
	 *
	 */
	public function test_get_items() {
		$nav_menu_id = wp_update_nav_menu_object(
			0,
			array(
				'description' => 'Test get',
				'menu-name'   => 'test Name get',
				'slug'        => 'test-slug-get',
			)
		);
		$request     = new WP_REST_Request( 'GET', '/wp/v2/menus' );
		$request->set_param( 'per_page', self::$per_page );
		$response = rest_get_server()->dispatch( $request );
		$this->check_get_taxonomy_terms_response( $response );
	}

	/**
	 *
	 */
	public function test_get_item() {
		wp_set_current_user( self::$admin_id );
		$nav_menu_id = wp_update_nav_menu_object(
			0,
			array(
				'description' => 'Test menu',
				'menu-name'   => 'test Name',
				'slug'        => 'test-slug',
			)
		);
		$request     = new WP_REST_Request( 'GET', '/wp/v2/menus/' . $nav_menu_id );
		$response    = rest_get_server()->dispatch( $request );
		$this->check_get_taxonomy_term_response( $response );
	}

	/**
	 *
	 */
	public function test_create_item() {
		wp_set_current_user( self::$admin_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/menus' );
		$request->set_param( 'name', 'My Awesome menus' );
		$request->set_param( 'description', 'This menu is so awesome.' );
		$request->set_param( 'slug', 'so-awesome' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );
		$headers = $response->get_headers();
		$data    = $response->get_data();
		$this->assertContains( '/wp/v2/menus/' . $data['id'], $headers['Location'] );
		$this->assertEquals( 'My Awesome menus', $data['name'] );
		$this->assertEquals( 'This menu is so awesome.', $data['description'] );
		$this->assertEquals( 'so-awesome', $data['slug'] );
	}

	/**
	 *
	 */
	public function test_update_item() {
		wp_set_current_user( self::$admin_id );

		$nav_menu_id = wp_update_nav_menu_object(
			0,
			array(
				'description' => 'Original Description',
				'menu-name'   => 'Original Name',
				'slug'        => 'original-slug',
			)
		);

		$term = get_term_by( 'id', $nav_menu_id, self::TAXONOMY );

		$request = new WP_REST_Request( 'POST', '/wp/v2/menus/' . $term->term_id );
		$request->set_param( 'name', 'New Name' );
		$request->set_param( 'description', 'New Description' );
		$request->set_param( 'slug', 'new-slug' );
		$request->set_param(
			'meta',
			array(
				'test_single'     => 'just meta',
				'test_tag_single' => 'tag-specific meta',
				'test_cat_meta'   => 'category-specific meta',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'New Name', $data['name'] );
		$this->assertEquals( 'New Description', $data['description'] );
		$this->assertEquals( 'new-slug', $data['slug'] );
		$this->assertEquals( 'just meta', $data['meta']['test_single'] );
		$this->assertEquals( 'tag-specific meta', $data['meta']['test_tag_single'] );
		$this->assertFalse( isset( $data['meta']['test_cat_meta'] ) );
	}

	/**
	 *
	 */
	public function test_delete_item() {
		wp_set_current_user( self::$admin_id );

		$nav_menu_id = wp_update_nav_menu_object(
			0,
			array(
				'description' => 'Deleted Menu',
				'menu-name'   => 'Deleted Menu',
			)
		);

		$term = get_term_by( 'id', $nav_menu_id, self::TAXONOMY );

		$request = new WP_REST_Request( 'DELETE', '/wp/v2/menus/' . $term->term_id );
		$request->set_param( 'force', true );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['deleted'] );
		$this->assertEquals( 'Deleted Menu', $data['previous']['name'] );
	}

	/**
	 *
	 */
	public function test_prepare_item() {
		$nav_menu_id = wp_update_nav_menu_object(
			0,
			array(
				'description' => 'Foo Menu',
				'menu-name'   => 'Foo Menu',
			)
		);

		$term = get_term_by( 'id', $nav_menu_id, self::TAXONOMY );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/menus/' . $term->term_id );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->check_taxonomy_term( $term, $data, $response->get_links() );
	}

	/**
	 *
	 */
	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wp/v2/menus' );
		$response   = rest_get_server()->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 5, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'meta', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
	}

	/**
	 *
	 */
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

	/**
	 *
	 */
	public function test_get_items_no_permission() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menus' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 401 );
	}

	/**
	 *
	 */
	public function test_get_item_no_permission() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menus/' . $this->menu_id );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 401 );
	}

	/**
	 *
	 */
	public function test_get_items_wrong_permission() {
		wp_set_current_user( self::$subscriber_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menus' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 403 );
	}

	/**
	 *
	 */
	public function test_get_item_wrong_permission() {
		wp_set_current_user( self::$subscriber_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menus/' . $this->menu_id );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 403 );
	}

	/**
	 * @param $response
	 */
	protected function check_get_taxonomy_terms_response( $response ) {
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$args = array(
			'hide_empty' => false,
		);
		$tags = get_terms( self::TAXONOMY, $args );
		$this->assertEquals( count( $tags ), count( $data ) );
		$this->assertEquals( $tags[0]->term_id, $data[0]['id'] );
		$this->assertEquals( $tags[0]->name, $data[0]['name'] );
		$this->assertEquals( $tags[0]->slug, $data[0]['slug'] );
		$this->assertEquals( $tags[0]->taxonomy, $data[0]['taxonomy'] );
		$this->assertEquals( $tags[0]->description, $data[0]['description'] );
		$this->assertEquals( $tags[0]->count, $data[0]['count'] );
	}

	/**
	 * @param $response
	 */
	protected function check_get_taxonomy_term_response( $response ) {
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$menu = get_term( 1, self::TAXONOMY );
		$this->check_taxonomy_term( $menu, $data, $response->get_links() );
	}

	/**
	 * @param $term
	 * @param $data
	 * @param $links
	 */
	protected function check_taxonomy_term( $term, $data, $links ) {
		$this->assertEquals( $term->term_id, $data['id'] );
		$this->assertEquals( $term->name, $data['name'] );
		$this->assertEquals( $term->slug, $data['slug'] );
		$this->assertEquals( $term->description, $data['description'] );
		$this->assertEquals( get_term_link( $term ), $data['link'] );
		$this->assertEquals( $term->count, $data['count'] );
		$taxonomy = get_taxonomy( $term->taxonomy );
		if ( $taxonomy->hierarchical ) {
			$this->assertEquals( $term->parent, $data['parent'] );
		} else {
			$this->assertFalse( isset( $term->parent ) );
		}

		$relations = array(
			'self',
			'collection',
			'about',
			'https://api.w.org/post_type',
		);

		if ( ! empty( $data['parent'] ) ) {
			$relations[] = 'up';
		}

		$this->assertEqualSets( $relations, array_keys( $links ) );
		$this->assertContains( 'wp/v2/taxonomies/' . $term->taxonomy, $links['about'][0]['href'] );
		$this->assertEquals( add_query_arg( 'categories', $term->term_id, rest_url( 'wp/v2/posts' ) ), $links['https://api.w.org/post_type'][0]['href'] );
	}

}
