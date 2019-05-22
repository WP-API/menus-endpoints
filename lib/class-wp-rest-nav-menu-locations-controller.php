<?php
/**
 * REST API: WP_REST_Nav_Menu_Locations_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 5.x
 */

/**
 * Core controller used to access menu locations via the REST API.
 *
 * @since 5.x
 *
 * @see WP_REST_Controller
 */
class WP_REST_Nav_Menu_Locations_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'menu-locations';

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 5.x
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<location>[\w-]+)',
			array(
				'args'   => array(
					'location' => array(
						'description' => __( 'An alphanumeric identifier for the menu location.' ),
						'type'        => 'string',
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
						'menu' => array(
							'validate_callback' => function( $id ) {
								return 0 === $id || false !== wp_get_nav_menu_object( $id );
							},
							'required'          => true,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Checks whether a given request has permission to read menu locations.
	 *
	 * @since 5.x
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new WP_Error( 'rest_cannot_view', __( 'Sorry, you are not allowed to view menu locations.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves all menu locations, depending on user context.
	 *
	 * @since 5.x
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		$data = array();

		$locations = $this->get_locations();

		foreach ( $locations as $location ) {
			$item                    = $this->prepare_item_for_response( $location, $request );
			$data[ $location->name ] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Checks if a given request has access to read a menu location.
	 *
	 * @since 5.x
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new WP_Error( 'rest_cannot_view', __( 'Sorry, you are not allowed to view menu locations.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		if ( ! array_key_exists( $request['location'], get_registered_nav_menus() ) ) {
			return new WP_Error( 'rest_menu_location_invalid', __( 'Invalid menu location.' ), array( 'status' => 404 ) );
		}

		return true;
	}

	/**
	 * Retrieves a specific menu location.
	 *
	 * @since 5.x
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {

		$locations = $this->get_locations();
		if ( ! array_key_exists( $request['location'], $locations ) ) {
			return new WP_Error( 'rest_menu_location_invalid', __( 'Invalid menu location.' ), array( 'status' => 404 ) );
		}

		$item = $locations[ $request['location'] ];

		return rest_ensure_response( $this->prepare_item_for_response( $item, $request ) );
	}

	/**
	 * Checks if a given request has access to update a menu location.
	 *
	 * @since 5.x
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new WP_Error( 'rest_cannot_view', __( 'Sorry, you are not allowed to manage menu locations.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		if ( ! array_key_exists( $request['location'], get_registered_nav_menus() ) ) {
			return new WP_Error( 'rest_menu_location_invalid', __( 'Invalid menu location.' ), array( 'status' => 404 ) );
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

		$locations      = $this->get_locations();
		$location       = $locations[ $request['location'] ];
		$location->menu = $request['menu'];

		// Update theme mod
		$theme_mod                    = get_nav_menu_locations();
		$theme_mod[ $location->name ] = $request['menu'];
		set_theme_mod( 'nav_menu_locations', $theme_mod );

		return rest_ensure_response( $this->prepare_item_for_response( $location, $request ) );
	}

	/**
	 * Prepares a menu location object for serialization.
	 *
	 * @since 5.x
	 *
	 * @param stdClass        $location  Menu location data.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $location, $request ) {

		$item = (array) $location;

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $item, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filters a menu location returned from the REST API.
		 *
		 * Allows modification of the menu location data right before it is
		 * returned.
		 *
		 * @since 5.x
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param stdClass        $location The original location object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'rest_prepare_nav_menu_location', $response, $location, $request );
	}

	/**
	 * Retrieves the menu location's schema, conforming to JSON Schema.
	 *
	 * @since 5.x
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'menu-location',
			'type'       => 'object',
			'properties' => array(
				'name'        => array(
					'description' => __( 'The name of the menu location.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'The description of the menu location.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'menu'        => array(
					'description' => __( 'The ID of the assigned menu.' ),
					'type'        => 'int',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @since 5.x
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}

	/**
	 * Prepares links for the request.
	 *
	 * @since 5.x
	 *
	 * @param array $location Menu location.
	 *
	 * @return array Links for the given menu location.
	 */
	protected function prepare_links( $location ) {
		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $location['name'] ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		if ( $location['menu'] > 0 ) {
			$links['menu'] = array(
				'href'       => rest_url( sprintf( '%s/menus/%d', $this->namespace, $location['menu'] ) ),
				'embeddable' => true,
			);
		}

		return $links;
	}

	/**
	 * Get all nav menu locations as well as assigned menu IDs.
	 *
	 * @since 5.x
	 *
	 * @return stdClass[]
	 */
	protected function get_locations() {

		$locations = array();

		$registered = get_registered_nav_menus();
		$assigned   = get_nav_menu_locations();

		foreach ( $registered as $name => $description ) {
			$locations[ $name ] = (object) array(
				'name'        => $name,
				'description' => $description,
				'menu'        => isset( $assigned[ $name ] ) ? absint( $assigned[ $name ] ) : 0,
			);
		}

		return $locations;

	}

}
