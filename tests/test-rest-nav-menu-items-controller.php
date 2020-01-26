<?php

class WP_Test_REST_Nav_Menu_Items_Controller extends WP_Test_REST_Controller_Testcase {
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
	}

	/**
	 *
	 */
	public function test_context_param() {
	}

	/**
	 *
	 */
	public function test_get_items() {
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
