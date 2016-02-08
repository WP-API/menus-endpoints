<?php

/**
 * Manage Menu Items for a WordPress site
 */
class WP_REST_Widgets_Controller extends WP_REST_Controller {

	/**
	 * Widget objects.
	 *
	 * @see WP_Widget_Factory::$widgets
	 * @var WP_Widget[]
	 */
	public $widgets;

	/**
	 * Widget instances.
	 */
	public $instances = array();

	/**
	 * Sidebars
	 */
	public $sidebars;

	/**
	 * WP_REST_Widgets_Controller constructor.
	 *
	 * @param WP_Widget[] $widgets Widget objects.
	 */
	public function __construct( $widgets ) {
		$this->namespace = 'wp/v2';
		$this->rest_base = 'widgets';
		$this->widgets = $widgets;

		$this->sidebars = wp_get_sidebars_widgets();

		// @todo Now given $this->widgets, inject schema information for Core widgets in lieu of them being in core now. See #35574.
	}

	public function register_routes() {

		// /wp/v2/widgets
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args' => $this->get_collection_params(),
			),
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args' => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),

			'schema' => array( $this, 'get_public_items_schema' ),
		) );

		// /wp/v2/widgets/:id_base
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id_base>[^/]+)', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => array(
					'context'          => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_items_schema' ),
		) );

		// /wp/v2/widgets/:id_base/:number
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id_base>[^/]+)/(?P<number>[\d]+)', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => array(
					'context'          => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
			),

			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		// /wp/v2/widget-types/
		register_rest_route( $this->namespace, '/widget-types', array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_types' ),
				'permission_callback' => array( $this, 'get_types_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		register_rest_route( $this->namespace, '/' . $this->rest_base .'/types/(?P<type>[\w-]+)', array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_type' ),
				'permission_callback' => array( $this, 'get_types_permissions_check' ),
			),

			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * Get a collection of widgets
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		foreach ( $this->widgets as $widget ) {
			$settings = $widget->get_settings();
			foreach ( $settings as $key => $values ) {
				$this->instances[] = array(
					'id' => $widget->id_base . '-' . $key,
					'array_index' => $key,
					'id_base' => $widget->id_base,
					'settings' => $values,
				);
			}
		}

		if ( empty( $this->instances ) ) {
			return rest_ensure_response( array() );
		};

		$args = array();
		$args['sidebar'] = $request['sidebar'];

		// TODO pagination

		$instances = array();
		foreach ( $this->instances as $instance ) {
			if ( ! $this->get_instance_permissions_check( $instance['id'] ) ) {
				continue;
			}
			if ( ! is_null( $args['sidebar'] ) && $args['sidebar'] !== $this->get_instance_sidebar( $instance['id'] ) ) {
				continue;
			}
			$data = $this->prepare_item_for_response( $instance, $request );
			$instances[] = $this->prepare_response_for_collection( $data );
		}

		if ( ! empty( $instances ) && ! is_null( $args['sidebar'] ) ) {
			$instances = $this->sort_widgets_by_sidebar_order( $args['sidebar'], $instances );
		}

		return rest_ensure_response( $instances );
	}

	public function get_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if current user can get the widget instance.
	 *
	 * @param string $instance_id Instance id
	 * @return bool
	 */
	public function get_instance_permissions_check( $instance_id ) {
		// Require `edit_theme_options` to view unassigned widgets
		$sidebar = $this->get_instance_sidebar( $instance_id );
		if ( false === $sidebar || 'wp_inactive_widgets' == $sidebar ) {
			return current_user_can( 'edit_theme_options' );
		}

		return true;
	}

	/**
	 * Get the sidebar a widget instance is assigned to
	 *
	 * @param string id Widget instance id
	 * @return bool|string Sidebar id it is assigned to or false if not found. Will
	 *  return `wp_inactive_widgets` as sidebar for unassigned widgets
	 */
	public function get_instance_sidebar( $id ) {
		foreach ( $this->sidebars as $sidebar_id => $widgets ) {
			if ( in_array( $id, $widgets ) ) {
				return $sidebar_id;
			}
		}

		return false;
	}

	/**
	 * Sort the widgets by their order in the sidebar.
	 *
	 * Widgets not assigned to the specified sidebar will be discarded.
	 *
	 * @param string sidebar Sidebar id
	 * @param array instances Widget instances to sort
	 * @return array
	 */
	public function sort_widgets_by_sidebar_order( $sidebar, $instances ) {
		if ( empty( $this->sidebars[ $sidebar ] ) ) {
			return array();
		}

		$new_widgets = array();
		foreach ( $this->sidebars[ $sidebar ] as $widget_id ) {
			foreach ( $instances as $instance ) {
				if ( $widget_id === $instance['id'] ) {
					$new_widgets[] = $instance;
					break;
				}
			}
		}

		return $new_widgets;
	}

	/**
	 * Retrieves the specified widget in the request.
	 *
	 * @param  WP_REST_Request $request Request body.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		foreach ( $this->widgets as $widget ) {
			if ( $request['id_base'] === $widget->id_base ) {
				$the_widget = $widget;
				break;
			}
		}

		if ( isset( $the_widget ) ) {
			$settings = $the_widget->get_settings();
			foreach ( $settings as $key => $values ) {
				if ( absint( $request['number'] ) === $key ) {
					$this->instances[] = array(
						'id'          => $the_widget->id_base . '-' . $key,
						'array_index' => $key,
						'id_base'     => $the_widget->id_base,
						'settings'    => $values,
					);
				}
			}

			// Set up return data.
			$instances = array();

			// Loop through the requested widget instances. Should only be one in this case.
			foreach ( $this->instances as $instance ) {
				if ( ! $this->get_instance_permissions_check( $instance['id'] ) ) {
					continue;
				}
				if ( ! is_null( $request['sidebar'] ) && $request['sidebar'] !== $this->get_instance_sidebar( $instance['id'] ) ) {
					continue;
				}
				$data = $this->prepare_item_for_response( $instance, $request );
				$instances = $this->prepare_response_for_collection( $data );
			}

			// Return the widget response.
			return rest_ensure_response( $instances );
		} else {
			return new WP_Error( 'cant-get-widget', __( 'The widget you requested does not exist. Please modify your request and try again.', 'non-existant-text-domain' ), array( 'status' => 400 ) );
		}

		// If everything goes haywire.
		return new WP_Error( 'cant-get-widget', __( 'An error occured while processing your request. Please try again.', 'non-existant-text-domain' ), array( 'status' => 500 ) );
	}

	public function delete_item_permission_check( $request ) {
		return true;
	}

	public function delete_item( $request ) {

	}

	/**
	 * Prepare a single widget output for response
	 *
	 * @param array $instance Widget instance
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $instance, $request ) {

		$data['id']         = $instance['id'];
		$data['type']       = $instance['id_base'];
		$values['settings'] = $instance['settings'];

		$schema = $this->get_type_schema( $instance['id_base'] );

		foreach ( $schema['properties'] as $property_id => $property ) {

			// TODO check for public visibility of property and run permissions
			// check for private properties.

			// Test instance properties against schema and add them to the resource output.
			if ( isset( $values['settings'][ $property_id ] ) && gettype( $values['settings'][ $property_id ] ) === $property['type'] ) {
				$data['settings'][ $property_id ] = $values['settings'][ $property_id ];
			} elseif ( isset( $property['default'] ) ) {
				$data['settings'][ $property_id ] = $property['default'];
			}
		}

		$response = rest_ensure_response( $data );

		// @TODO Add _link to sidebar if assigned?

		/**
		 * Filter the widget data for a response.
		 *
		 * @param WP_REST_Response   $response   The response object.
		 * @param array              $widget     Widget instance.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( 'rest_prepare_widget', $response, $instance, $request );
	}

	public function get_item_schema() {

	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['sidebar'] = array(
			'description'       => __( 'Limit result set to widgets assigned to this sidebar.' ),
			'type'              => 'string',
			'default'           => null,
			'sanitize_callback' => 'sanitize_key',
		);

		return $params;
	}

	/**
	 * Get the available widget type schemas
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_types( $request ) {
		if ( empty( $this->widgets ) ) {
			return rest_ensure_response( array() );
		}

		$schemas = array();
		foreach ( $this->widgets as $key => $type ) {
			if ( empty( $type->id_base ) ) {
				continue;
			}
			$schemas[] = $this->get_type_schema( $type->id_base );
		}

		$response = rest_ensure_response( $schemas );

		return $response;
	}

	/**
	 * Get the requested widget type schema
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_type( $request ) {

		if ( empty( $request['type'] ) ) {
			return new WP_Error( 'rest_widget_missing_type', __( 'Request missing widget type.' ), array( 'status' => 400 ) );
		}

		$schema = $this->get_type_schema( $request['type'] );

		if ( false === $schema ) {
			return new WP_Error( 'rest_widget_type_not_found', __( 'Requested widget type was not found.' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $schema );
	}

	/**
	 * Check if a given request has access to read /widgets/types
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_types_permissions_check( $request ) {
		return true; // @todo edit_theme_options
	}

	/**
	 * Return a schema matching this widget type
	 *
	 * @param string $id_base Registered widget type
	 * @return array $schema
	 */
	public function get_type_schema( $id_base ) {

		$widget = null;
		foreach ( $this->widgets as $this_widget ) {
			if ( $id_base === $this_widget->id_base ) {
				$widget = $this_widget;
				break;
			}
		}
		if ( empty( $widget ) ) {
			return false;
		}

		$properties = array(
			'id'              => array(
				'description' => __( 'Unique identifier for the object.' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit', 'embed' ),
				'readonly'    => true,
			),
			'type'            => array(
				'description' => __( 'Type of Widget for the object.' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit', 'embed' ),
				'readonly'    => true,
			),
		);

		$core_widget_schemas = array(
			'archives' => array(
				'count' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'dropdown' => array(
					'type' => 'boolean',
					'default' => false,
				),
			),
			'calendar' => array(),
			'categories' => array(
				'count' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'hierarchical' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'dropdown' => array(
					'type' => 'boolean',
					'default' => false,
				),
			),
			'meta' => array(),
			'nav_menu' => array(
				'sortby' => array(
					'type' => 'string',
					'default' => 'post_title',
				),
				'exclude' => array(
					'type' => 'string',
					'default' => '',
				),
			),
			'pages' => array(
				'sortby' => array(
					'type' => 'string',
					'default' => 'post_title',
				),
				'exclude' => array(
					'type' => 'string',
					'default' => '',
				),
			),
			'recent-comments' => array(
				'number' => array(
					'type' => 'integer',
					'default' => 5,
				),
			),
			'recent-posts' => array(
				'number' => array(
					'type' => 'integer',
					'default' => 5,
				),
				'show_date' => array(
					'type' => 'boolean',
					'default' => false,
				),
			),
			'rss' => array(
				'url' => array(
					'type' => 'string',
					'default' => '',
				),
				'link' => array(
					'type' => 'string',
					'default' => '',
				),
				'items' => array(
					'type' => 'integer',
					'default' => 10,
				),
				'error' => array(
					'type' => 'string',
					'default' => null,
				),
				'show_summary' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'show_author' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'show_date' => array(
					'type' => 'boolean',
					'default' => false,
				),
			),
			'search' => array(),
			'tag_cloud' => array(
				'taxonomy' => array(
					'type' => 'string',
					'default' => 'post_tag',
				),
			),
			'text' => array(
				'text' => array(
					'type' => 'string',
					'default' => '',
				),
				'filter' => array(
					'type' => 'boolean',
					'default' => false,
				),
			),
		);

		if ( in_array( $id_base, array( 'pages', 'calendar', 'archives', 'meta', 'search', 'text', 'categories', 'recent-posts', 'recent-comments', 'rss', 'tag_cloud', 'nav_menu', 'next_recent_posts' ), true ) ) {
			$properties['title'] = array(
				'description' => __( 'The title for the object.' ),
				'type'        => 'string',
			);
		}
		if ( isset( $core_widget_schemas[ $id_base ] ) ) {
			$properties = array_merge( $properties, $core_widget_schemas[ $id_base ] );
		}
		foreach ( array_keys( $properties ) as $field_id ) {
			if ( ! isset( $properties[ $field_id ]['context'] ) ) {
				$properties[ $field_id ]['context'] = array( 'view', 'edit', 'embed' );
			}
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $widget->id_base,
			'type'       => 'object',

			/*
			 * Base properties for every Widget.
			 */
			'properties' => $properties,
		);

		return $schema;
	}
}
