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
		$this->widgets   = $widgets;

		$this->sidebars = wp_get_sidebars_widgets();

		// @todo Now given $this->widgets, inject schema information for Core widgets in lieu of them being in core now. See #35574.
	}

	public function register_routes() {

		// /wp/v2/widgets
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
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),

				'schema' => array( $this, 'get_public_items_schema' ),
			)
		);

		// /wp/v2/widgets/:id_base
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id_base>[^/]+)',
			array(
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
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_items_schema' ),
			)
		);

		// /wp/v2/widgets/:id_base/:number
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id_base>[^/]+)/(?P<number>[\d]+)',
			array(
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
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),

				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// /wp/v2/widget-types/
		register_rest_route(
			$this->namespace,
			'/widget-types',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_types' ),
					'permission_callback' => array( $this, 'get_types_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/types/(?P<type>[\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_type' ),
					'permission_callback' => array( $this, 'get_types_permissions_check' ),
				),

				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
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
					'id'          => $widget->id_base . '-' . $key,
					'array_index' => $key,
					'id_base'     => $widget->id_base,
					'settings'    => $values,
				);
			}
		}

		if ( empty( $this->instances ) ) {
			return rest_ensure_response( array() );
		};

		$args            = array();
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
			$data        = $this->prepare_item_for_response( $instance, $request );
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
		if ( $sidebar === false || $sidebar == 'wp_inactive_widgets' ) {
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

	public function get_item( $request ) {
	}

	public function delete_item_permission_check( $request ) {
		return true;
	}

	public function delete_item( $request ) {
	}

	/**
	 * Prepare a single widget output for response
	 *
	 * @param array           $instance Widget instance
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $instance, $request ) {
		$values         = $instance['settings'];
		$values['id']   = $instance['id'];
		$values['type'] = $instance['id_base'];

		$schema = $this->get_type_schema( $instance['id_base'] );

		$data = array();
		foreach ( $schema['properties'] as $property_id => $property ) {

			// TODO check for public visibility of property and run permissions
			// check for private properties.

			if ( isset( $values[ $property_id ] ) && gettype( $values[ $property_id ] ) === $property['type'] ) {
				$data[ $property_id ] = $values[ $property_id ];
			} elseif ( isset( $property['default'] ) ) {
				$data[ $property_id ] = $property['default'];
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

		if ( $schema === false ) {
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
			'id'   => array(
				'description' => __( 'Unique identifier for the object.' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit', 'embed' ),
				'readonly'    => true,
			),
			'type' => array(
				'description' => __( 'Type of Widget for the object.' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit', 'embed' ),
				'readonly'    => true,
			),
		);

		$core_widget_schemas = array(
			'archives'        => array(
				'title'    => array(
					'type'    => 'string',
					'default' => '',
				),
				'count'    => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'dropdown' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'calendar'        => array(
				'title'    => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'categories'      => array(
				'title'    => array(
					'type'    => 'string',
					'default' => '',
				),
				'count'    => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'hierarchical' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'dropdown'     => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'custom_html'     => array(
				'title'   => array(
					'type'    => 'string',
					'default' => '',
				),
				'content' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'links'           => array(
				'images'    => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'name'        => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'description'        => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'rating'        => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'category'        => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'orderby'        => array(
					'type'    => 'string',
					'default' => 'name',
				),
				'limit'        => array(
					'type'    => 'integer',
					'default' => -1,
				),
			),
			'media_audio'   => array(
				'attachment_id' => array(
					'type'    => 'integer',
					'default' => 0,
				),
				'url'           => array(
					'type'    => 'string',
					'default' => '',
				),
				'title'       	=> array(
					'type'    => 'string',
					'default' => '',
				),
				'preload'     	=> array(
					'type'    => 'string',
					'default' => 'none',
				),
				'loop'        	=> array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'media_gallery'     => array(
				'attachment_id' => array(
					'type'    => 'integer',
					'default' => 0,
				),
				'url'        	=> array(
					'type'    => 'string',
					'default' => '',
				),
				'title'        	=> array(
					'type'    => 'string',
					'default' => '',
				),
				'ids'        	=> array(
					'type'    => 'array',
					'items'   => array(
						'type' 	=> 'integer',
					),
					'default' => array(),
				),
				'columns'     	=> array(
					'type'    => 'integer',
					'default' => 3,
				),
				'size'			=> array (
					'type'	  => 'string',
					'default' => 'thumbnail'
				),
				'link_type'     => array(
					'type'    => 'string',
					'default' => 'post',
		        ),
	            'orderby_random'=> array(
					'type'    => 'boolean',
			        'default' => false,
				),
			),
			'media_image'     => array(
				'attachment_id'      => array(
					'type'    => 'integer',
					'default' => 0,
				),
				'url'        	     => array(
					'type'    => 'string',
					'default' => '',
				),
				'title'        	     => array(
					'type'    => 'string',
					'default' => '',
				),
				'size'			     => array (
					'type'	  => 'string',
					'default' => 'medium'
				),
				'width'              => array(
					'type'    => 'integer',
					'default' => 0,
				),
				'height'             => array(
					'type'    => 'integer',
					'default' => 0,
				),
				'caption'            => array(
					'type'    => 'string',
					'default' => '',
				),
				'alt'                => array(
					'type'    => 'string',
					'default' => '',
		        ),
				'link_type'          => array(
					'type'    => 'string',
					'default' => 'custom',
				),
				'link_url'           => array(
					'type'    => 'string',
					'default' => '',
		        ),
	            'image_classes'      => array(
					'type'    => 'string',
			        'default' => '',
				),
				'link_classes'       => array(
					'type'    => 'string',
			        'default' => '',
				),
				'link_rel'           => array(
					'type'    => 'string',
			        'default' => '',
				),
				'link_target_blank'  => array(
					'type'    => 'string',
			        'default' => '',
				),
				'image_title'        => array(
					'type'    => 'string',
			        'default' => '',
				),
			),
			'media_video'   => array(
				'attachment_id' => array(
					'type'    => 'integer',
					'default' => 0,
				),
				'url'           => array(
					'type'    => 'string',
					'default' => '',
				),
				'title'       	=> array(
					'type'    => 'string',
					'default' => '',
				),
				'preload'     	=> array(
					'type'    => 'string',
					'default' => 'none',
				),
				'loop'        	=> array(
					'type'    => 'boolean',
					'default' => false,
				),
				'content'       => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'meta'            => array(
				'title'	  => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'nav_menu'        => array(
				'title'	  => array(
					'type'    => 'string',
					'default' => '',
				),
				'nav_menu' => array(
					'type'    => 'string',
					'default' => '',
				),
				'sortby'  => array(
					'type'    => 'string',
					'default' => 'post_title',
				),
				'exclude' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'pages'           => array(
				'title'	  => array(
					'type'    => 'string',
					'default' => '',
				),
				'sortby'  => array(
					'type'    => 'string',
					'default' => 'post_title',
				),
				'exclude' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'recent-comments' => array(
				'title'	 => array(
					'type'    => 'string',
					'default' => '',
				),
				'number' => array(
					'type'    => 'integer',
					'default' => 5,
				),
			),
			'recent-posts'    => array(
				'title'	    => array(
					'type'    => 'string',
					'default' => '',
				),
				'number'    => array(
					'type'    => 'integer',
					'default' => 5,
				),
				'show_date' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'rss'             => array(
				'title'	       => array(
					'type'    => 'string',
					'default' => '',
				),
				'url'          => array(
					'type'    => 'string',
					'default' => '',
				),
				'link'         => array(
					'type'    => 'string',
					'default' => '',
				),
				'items'        => array(
					'type'    => 'integer',
					'default' => 10,
				),
				'error'        => array(
					'type'    => 'string',
					'default' => null,
				),
				'show_summary' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'show_author'  => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'show_date'    => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'search'          => array(
				'title'	   => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'tag_cloud'       => array(
				'title'	  => array(
					'type'    => 'string',
					'default' => '',
				),
				'taxonomy' => array(
					'type'    => 'string',
					'default' => 'post_tag',
				),
			),
			'text'            => array(
				'title'	 => array(
					'type'    => 'string',
					'default' => '',
				),
				'text'   => array(
					'type'    => 'string',
					'default' => '',
				),
				'filter' => array(
					'type'    => 'boolean',
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
