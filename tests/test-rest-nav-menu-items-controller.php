<?php

class WP_Test_REST_Nav_Menu_Items_Controller extends WP_Test_REST_Post_Type_Controller_Testcase {
	/**
	 * @var int
	 */
	protected static $menu_id;
	/**
	 * @var
	 */
	protected static $tag_id;
	/**
	 * @var
	 */
	protected static $menu_item_id;

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
	const POST_TYPE = 'nav_menu_item';

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

	/**
	 *
	 */
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

	/**
	 *
	 */
	public function test_registered_query_params() {
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/menu-items' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$keys     = array_keys( $data['endpoints'][0]['args'] );
		sort( $keys );
		$this->assertEquals(
			array(
				'after',
				'before',
				'context',
				'exclude',
				'include',
				'menu_order',
				'menus',
				'menus_exclude',
				'offset',
				'order',
				'orderby',
				'page',
				'per_page',
				'search',
				'slug',
				'status',
			),
			$keys
		);
	}

	/**
	 *
	 */
	public function test_registered_get_item_params() {
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( '/wp/v2/menu-items/%d', self::$menu_item_id ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$keys     = array_keys( $data['endpoints'][0]['args'] );
		sort( $keys );
		$this->assertEquals( array( 'context', 'id' ), $keys );
	}

	/**
	 *
	 */
	public function test_get_items() {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/menu-items' );
		$response = rest_get_server()->dispatch( $request );

		$this->check_get_menu_items_response( $response );
	}

	/**
	 *
	 */
	public function test_get_item() {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', sprintf( '/wp/v2/menu-items/%d', self::$menu_item_id ) );
		$response = rest_get_server()->dispatch( $request );

		$this->check_get_post_response( $response, 'view' );
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

	/**
	 * @param $response
	 * @param string $context
	 */
	protected function check_get_menu_items_response( $response, $context = 'view' ) {
		$this->assertNotWPError( $response );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'X-WP-Total', $headers );
		$this->assertArrayHasKey( 'X-WP-TotalPages', $headers );

		$all_data = $response->get_data();
		foreach ( $all_data as $data ) {
			$post = get_post( $data['id'] );
			// Base fields for every post.
			$menu_item = wp_setup_nav_menu_item( $post );
			// as the links for the post are "response_links" format in the data array we have to pull them
			// out and parse them.
			$links = $data['_links'];
			foreach ( $links as &$links_array ) {
				foreach ( $links_array as &$link ) {
					$attributes         = array_diff_key(
						$link,
						array(
							'href' => 1,
							'name' => 1,
						)
					);
					$link               = array_diff_key( $link, $attributes );
					$link['attributes'] = $attributes;
				}
			}

			$this->check_menu_item_data( $menu_item, $data, $context, $links );
		}
	}

	/**
	 * @param $post
	 * @param $data
	 * @param $context
	 * @param $links
	 */
	protected function check_menu_item_data( $post, $data, $context, $links ) {
		$post_type_obj = get_post_type_object( self::POST_TYPE );

		// Standard fields
		$this->assertEquals( $post->ID, $data['id'] );
		$this->assertEquals( wpautop( $post->post_content ), $data['description'] );

		// Check filtered values.
		if ( post_type_supports( self::POST_TYPE, 'title' ) ) {
			add_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );
			$this->assertEquals( $post->title, $data['title']['rendered'] );
			remove_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );
			if ( 'edit' === $context ) {
				$this->assertEquals( $post->post_title, $data['title']['raw'] );
			} else {
				$this->assertFalse( isset( $data['title']['raw'] ) );
			}
		} else {
			$this->assertFalse( isset( $data['title'] ) );
		}

		// post_parent

		$this->assertArrayHasKey( 'parent', $data );
		if ( $post->post_parent ) {
			if ( is_int( $data['parent'] ) ) {
				$this->assertEquals( $post->post_parent, $data['parent'] );
			} else {
				$this->assertEquals( $post->post_parent, $data['parent']['id'] );
				$this->check_get_menu_items_response( $data['parent'], get_post( $data['parent']['id'] ), 'view-parent' );
			}
		} else {
			$this->assertEmpty( $data['parent'] );
		}

		// page attributes
		$this->assertEquals( $post->menu_order, $data['menu_order'] );

		$taxonomies = wp_list_filter( get_object_taxonomies( self::POST_TYPE, 'objects' ), array( 'show_in_rest' => true ) );
		foreach ( $taxonomies as $taxonomy ) {
			$this->assertTrue( isset( $data[ $taxonomy->rest_base ] ) );
			$terms = wp_get_object_terms( $post->ID, $taxonomy->name, array( 'fields' => 'ids' ) );
			sort( $terms );
			sort( $data[ $taxonomy->rest_base ] );
			$this->assertEquals( $terms, $data[ $taxonomy->rest_base ] );
		}

		// test links
		if ( $links ) {
			$links     = test_rest_expand_compact_links( $links );
			$this->assertEquals( $links['self'][0]['href'], rest_url( 'wp/v2/' . $post_type_obj->rest_base . '/' . $data['id'] ) );
			$this->assertEquals( $links['collection'][0]['href'], rest_url( 'wp/v2/' . $post_type_obj->rest_base ) );
			$this->assertEquals( $links['about'][0]['href'], rest_url( 'wp/v2/types/' . self::POST_TYPE ) );

			$num = 0;
			foreach ( $taxonomies as $key => $taxonomy ) {
				$this->assertEquals( $taxonomy->name, $links['https://api.w.org/term'][ $num ]['attributes']['taxonomy'] );
				$this->assertEquals( add_query_arg( 'post', $data['id'], rest_url( 'wp/v2/' . $taxonomy->rest_base ) ), $links['https://api.w.org/term'][ $num ]['href'] );
				$num++;
			}
		}
	}
}
