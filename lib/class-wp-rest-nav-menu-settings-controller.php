<?php
/**
 * REST API: WP_REST_Nav_Menu_Settings_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 5.x
 */

/**
 * Core controller used to access menu settings via the REST API.
 *
 * @since 5.x
 *
 * @see WP_REST_Posts_Controller
 */
class WP_REST_Nav_Menu_Settings_Controller extends WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @since 5.x
	 *
	 * @var string
	 */
	protected $namespace = 'wp/v2';

	/**
	 * The base of this controller's route.
	 *
	 * @since 5.x
	 *
	 * @var string
	 */
	protected $rest_base = 'menus';

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 5.x
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/(?P<id>[\d]+)/settings",
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the menu.' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'auto_add' => array(
							'sanitize_callback' => 'wp_validate_boolean',
							'validate_callback' => 'rest_is_boolean',
							'required'          => true,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @since 5.x
	 *
	 * @param WP_REST_Request $request REST request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {

		$menu_id = $request['id'];
		$menu    = wp_get_nav_menu_object( $menu_id );

		if ( ! $menu ) {
			return new WP_Error( 'rest_invalid_menu_id', __( 'Invalid menu ID' ), array( 'status' => 404 ) );
		}

		$item = array(
			'id'       => $menu_id,
			'auto_add' => false,
		);

		$nav_menu_options = (array) get_option( 'nav_menu_options' );

		if ( isset( $nav_menu_options['auto_add'] ) ) {
			if ( in_array( $menu_id, $nav_menu_options['auto_add'], true ) ) {
				$item['auto_add'] = (bool) $nav_menu_options['auto_add'];
			}
		}

		return $this->prepare_item_for_response( $item, $request );
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @since 5.x
	 *
	 * @param WP_REST_Request $request REST request
	 *
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( "Sorry, you are not allowed to view this menu's settings." ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @since 5.x
	 *
	 * @param WP_REST_Request $request REST request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {

		$menu_id = $request['id'];
		$menu    = wp_get_nav_menu_object( $menu_id );

		if ( ! $menu ) {
			return new WP_Error( 'rest_invalid_menu_id', __( 'Invalid menu ID' ), array( 'status' => 404 ) );
		}

		$item = array(
			'id'       => $menu_id,
			'auto_add' => false,
		);

		$nav_menu_options = (array) get_option( 'nav_menu_options' );

		if ( isset( $nav_menu_options['auto_add'] ) ) {
			if ( in_array( $menu_id, $nav_menu_options['auto_add'], true ) ) {
				$item['auto_add'] = true;
			}
		} else {
			$nav_menu_options['auto_add'] = array();
		}

		// Update auto add pages setting
		$item['auto_add'] = $request->get_param( 'auto_add' );
		if ( $item['auto_add'] ) {
			$nav_menu_options['auto_add'][] = $menu_id;
		} else {
			$key = array_search( $menu_id, $nav_menu_options['auto_add'], true );
			if ( false !== $key ) {
				unset( $nav_menu_options['auto_add'][ $key ] );
			}
		}
		update_option( 'nav_menu_options', $nav_menu_options );

		return $this->prepare_item_for_response( $item, $request );
	}

	/**
	 * Checks if a given request has access to update a specific item.
	 *
	 * @since 5.x
	 *
	 * @param WP_REST_Request $request REST request
	 *
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new WP_Error( 'rest_cannot_update', __( "Sorry, you are not allowed to edit this menu's settings." ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @since 5.x
	 *
	 * @param array            $item Menu settings
	 * @param WP_REST_Request $request REST request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function prepare_item_for_response( $item, $request ) {

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $item, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filters a menu location returned from the REST API.
		 *
		 * Allows modification of the menu's settings data right before it is
		 * returned.
		 *
		 * @since 5.x
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $item The original status object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'rest_prepare_nav_menu_settings', $response, $item, $request );
	}

	/**
	 * Prepares links for the request.
	 *
	 * @since 5.x
	 *
	 * @param array $item Menu settings.
	 *
	 * @return array Links for the given menu settings.
	 */
	protected function prepare_links( $item ) {

		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self' => array(
				'href' => rest_url( trailingslashit( $base ) . $item['id'] . '/settings' ),
			),
			'menu' => array(
				'href'       => rest_url( trailingslashit( $base ) . $item['id'] ),
				'embeddable' => true,
			),
		);

		return $links;
	}

	/**
	 * Retrieves the menu settings schema, conforming to JSON Schema.
	 *
	 * @since 5.x
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'menu-settings',
			'type'       => 'object',
			'properties' => array(
				'id'       => array(
					'description' => 'Unique identifier for the menu.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'auto_add' => array(
					'description' => __( 'Whether or not to automatically add top level pages to the menu.' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

}
