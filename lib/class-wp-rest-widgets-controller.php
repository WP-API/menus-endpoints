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
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => array(
					'sidebar_id' => array(
						'description'       => __( 'Sidebar ID the widget will be placed in.' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'position' => array(
						'description'       => __( 'Position for the widget in sidebar.' ),
						'type'              => 'int',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'id_base' => array(
						'description'       => __( 'Type of widget that you want to be created, i.e. pages, tag_cloud, calendar etc.' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
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
	 * @param  WP_REST_Request $request Full details about the request.
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
	 * Create one widget for the collection.
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function create_item( $request ) {
		$valid_widget = false;
		foreach ( $this->widgets as $widget ) {
			if ( $request['id_base'] === $widget->id_base ) {
				$valid_widget = true;

				$this_widget = $widget;

				break;
			}
		}

		$valid_sidebar = array_key_exists( $request['sidebar_id'], $this->sidebars );

		if ( ( true === $valid_widget ) && ( true === $valid_sidebar ) ) {
			// Set widget number for new instance.
			$widget_number = $widget->number + 1;

			// Create the widget instance and store the instance data for a widget type.
			$widget_instances = $this->create_widget_instance( $widget, $widget_number, $request );

			$this->set_widget_position( $this->sidebars, $widget_number, $request );

			return new WP_REST_Response( $widget_instances, 201 );

		} else {
			// Error Handling.
			if ( false === $valid_widget ) {
				return new WP_Error( 'invalid-widget-id-base', 'The requested widget ID base does not exist. Please revise your request.', array( 'status' => 404 ) );
			}

			if ( false === $valid_sidebar ) {
				return new WP_Error( 'invalid-sidebar-id', 'The requested sidebar ID does not exist. Please revise your request.', array( 'status' => 404 ) );
			}
		}

		return new WP_Error( 'cant-create', __( 'There was an error creating the widget.', 'be-rest-endpoints' ), array( 'status' => 500 ) );
	}

	public function create_item_permissions_check( $request ) {
		// @TODO return current_user_can( 'edit_theme_options' );
		return true;
	}

	/**
	 * Sets the widgets position in a sidebar.
	 *
	 * @access public
	 *
	 * @param array           $sidebars_widgets Equivalent to wp_get_sidebars_widgets().
	 * @param int             $widget_number    Instance number of the widget.
	 * @param WP_REST_Request $request          Request body.
	 * @return void
	 */
	public function set_widget_position( $sidebars_widgets, $widget_number, $request ) {
		$widget_id = $request['id_base'] . '-' . $widget_number;

		// Check to make sure the sidebar is a valid array. If not then set widget position to first.
		if ( is_array( $sidebars_widgets[ $request['sidebar_id'] ] ) ) {
			$sidebar_length = count( $sidebars_widgets[ $request['sidebar_id'] ] );
			// If the specified position is greater than the number of widgets in sidebar then set position to the end of the sidebar.
			$position = ( $sidebar_length < $request['position'] ) ? $sidebar_length : $request['position'];

			// If the request is only one greater than the sidebar length then this will be equivalent to appending the new widget to the end.
			if ( $sidebar_length + 1 === $request['position'] ) {
				$position = $request['position'];
			}
			// If for some reason a request sets a widget to below first position set it back to first position.
			if ( $position < 1 ) {
				$position = 1;
			}
		} else {
			$position = 1;
		}

		// Move position to match array index.
		$position = $position - 1;

		// If there is already a widget located in the position of the sidebar make sure to move it after the widget that will be inserted.
		if ( isset( $sidebars_widgets[ $request['sidebar_id'] ][ $position ] ) ) {
			if ( false !== $widget_exists = array_search( $widget_id, $sidebars_widgets[ $request['sidebar_id'] ] ) ) {
				if ( 0 === $widget_exists ) {
					// If widget to be updated is at beginning.
					array_shift( $sidebars_widgets[ $request['sidebar_id'] ] );
				} elseif ( 0 < $widget_exists && $widget_exists < $sidebar_length ) {
					// If widget is in the middle.
					$array_chunk = array_splice( $sidebars_widgets[ $request['sidebar_id'] ], $widget_exists );
					array_shift( $array_chunk );
					$sidebars_widgets[ $request['sidebar_id'] ] = array_merge( $sidebars_widgets[ $request['sidebar_id'] ], $array_chunk );
				} elseif ( $widget_exists + 1 === $sidebar_length && 0 !== $widget_exists ) {
					// If widget is at the end.
					array_pop( $sidebars_widgets[ $request['sidebar_id'] ] );
				}
			}
			if ( 0 < $position && ( $sidebar_length > $position ) ) {
				// If the position is somewhere in the middle of the sidebar.
				// Moves the widget into the proper place in the sidebar.
				$array_chunk = array_splice( $sidebars_widgets[ $request['sidebar_id'] ], $position );
				array_unshift( $array_chunk, $widget_id );
				$sidebars_widgets[ $request['sidebar_id'] ] = array_merge( $sidebars_widgets[ $request['sidebar_id'] ], $array_chunk );
			} elseif ( 0 === $position ) {
				// If the position is at the beginning of the sidebar.
				// Set new widget into the beginning of the sidebar.
				array_unshift( $sidebars_widgets[ $request['sidebar_id'] ], $widget_id );
			} elseif ( $sidebar_length === $position && 0 !== $position ) {
				// If the position is at the end of the sidebar.
				// Equivalent of array_push without function overhead.
				$sidebars_widgets[ $request['sidebar_id'] ][] = $widget_id;
			}
		} else {
			// If the sidebar is empty or if the position is not set, just add the new value.
			$sidebars_widgets[ $request['sidebar_id'] ][ $position ] = $widget_id;
		}

		// Set sidebars widgets.
		wp_set_sidebars_widgets( $sidebars_widgets );
	}

	/**
	 * Creates a hollow widget instance and saves it as a template if no other widgets have been created.
	 *
	 * @access public
	 *
	 * @param  array           $widget        Array that exists at $wp_registered_widgets[ $widget_id ].
	 * @param  int             $widget_number Widget instance number.
	 * @param  WP_REST_Request $request       Request body.
	 * @return array $all_instances Returns the instance data for the given widget type.
	 */
	public function create_widget_instance( $widget, $widget_number, $request ) {

		$all_instances = $widget->get_settings();

		$existing_instance = array();
		// If a core widget.
		if ( $this->is_core_widget( $widget->id_base ) ) {
			$type_schema_properties = $this->get_type_schema( $widget->id_base )['properties'];
			foreach ( $type_schema_properties as $property => $attributes ) {
				if ( 'id' === $property || 'type' === $property ) {
					// Skip id and type.
					continue;
				}
				// Set up a default instance.
				$existing_instance[ $property ] = '';

				if ( isset( $attributes['default'] ) ) {
					$existing_instance[ $property ] = $attributes['default'];
				}
			}
		} else {
			// Tells how many and what instances of the widget exist.
			$instance_numbers = array_keys( $all_instances );

			// Cut the array to just one key and store the key's value.
			if ( 0 < count( $instance_numbers ) ) {
				$existing_instance_number = array_slice( $instance_numbers, 0, 1 )[0];
			}

			if ( isset( $existing_instance_number ) ) {
				// Store a copy of existing instance.  If there are no instances this will store the value to _multiwidget which is 1.
				$existing_instance = $all_instances[ $existing_instance_number ];
			}

			if ( empty( $existing_instance ) ) {
				$the_widget_form = '';
				// @TODO Move to a function.
				// If there were no active instances set instance to empty array.
				// Start output buffering to capture WP_Widget::form() output.
				ob_start();
				// By placing -1 as param, it ouputs an stateless version of the widget form.
				$widget->form_callback( -1 );
				$the_widget_form = ob_get_contents();
				ob_end_clean();

				if ( false !== strpos( $the_widget_form, '<p class="no-options-widget">' ) ) {
					// Create a DOM Document for the widget form.
					$dom = new DOMDocument();
					$dom->loadHTML( $the_widget_form );

					// Check for inputs.
					$inputs = $dom->getElementsByTagName( 'input' );

					foreach ( $inputs as $input ) {
						if ( $input->hasAttribute( 'id' ) ) {
							// Get ID attributes of form fields so widget instances can be set.
							$id_value = $input->getAttribute( 'id' );

							// Find last occurence of '__i__-'. The field value follows.
							$chop = strrpos( $id_value, '__i__-' );

							// Move the chop position to the end of '__i__-'
							$chop = $chop + 6;

							// Instance option name.
							$instance_option = substr( $id_value, $chop );

							// Store instance options into array.
							$existing_instance[ $instance_option ] = '';
						}
					}

					// Check for textareas.
					$textareas = $dom->getElementsByTagName( 'textarea' );

					foreach ( $textareas as $textarea ) {
						if ( $textarea->hasAttribute( 'id' ) ) {
							// Get ID attributes of form fields so widget instances can be set.
							$id_value = $textarea->getAttribute( 'id' );

							// Find last occurence of '__i__-'. The field value follows.
							$chop = strrpos( $id_value, '__i__-' );

							// Move the chop position to the end of '__i__-'.
							$chop = $chop + 6;

							// Instance option name.
							$instance_option = substr( $id_value, $chop );

							// Store instance options into array.
							$existing_instance[ $instance_option ] = '';
						}
					}

					// Check for select boxes.
					$select_boxes = $dom->getElementsByTagName( 'select' );

					foreach ( $select_boxes as $select_box ) {
						if ( $select_box->hasAttribute( 'id' ) ) {
							// Get ID attributes of form fields so widget instances can be set.
							$id_value = $select_box->getAttribute( 'id' );

							// Find last occurence of '__i__-'. The field value follows.
							$chop = strrpos( $id_value, '__i__-' );

							// Move the chop position to the end of '__i__-'.
							$chop = $chop + 6;

							// Instance option name.
							$instance_option = substr( $id_value, $chop );

							// Store instance options into array.
							$existing_instance[ $instance_option ] = '';
						}
					}
				}
			}
		}

		// Set instance for new widget.
		$all_instances[ $widget_number ] = $existing_instance;

		// Save new instances of the widget.
		$widget->save_settings( $all_instances );

		return $all_instances;
	}

	/**
	 * Determines if the widget is a core widget.
	 *
	 * @param  string $id_base Widget ID base. Same as widget type.
	 * @return boolean
	 */
	public function is_core_widget( $id_base ) {
		return in_array( $id_base, array( 'pages', 'calendar', 'archives', 'meta', 'search', 'text', 'categories', 'recent-posts', 'recent-comments', 'rss', 'tag_cloud', 'nav_menu', 'next_recent_posts' ), true );
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

	public function delete_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * Delete a single widget.
	 *
	 * @param  WP_REST_Request $request Request body.
	 * @return WP_REST_Response|WP_Error $data
	 */
	public function delete_item( $request ) {
		if ( isset( $request['id_base'] ) && isset( $request['number'] ) ) {
			$widget_id = $request['id_base'] . '-' . $request['number'];

			// Attempt to find matching widget.
			foreach ( $this->widgets as $widget ) {
				if ( $request['id_base'] === $widget->id_base ) {
					$the_widget = $widget;
					break;
				}
			}

			// If widget match is found. Remove widget instance from settings and sidebar.
			if ( isset( $the_widget ) ) {
				$sidebars_widgets = wp_get_sidebars_widgets();

				foreach ( $sidebars_widgets as $sidebar_id => $sidebar ) {
					if ( is_array( $sidebar ) && in_array( $widget_id, $sidebar ) ) {
						$current_sidebar = $sidebar_id;
						$position        = array_search( $widget_id, $sidebar );
						break;
					}
				}

				// If widget is in a sidebar remove it from the array and preserve array indices.
				if ( isset( $current_sidebar ) && false !== $position ) {
					$sidebar_length = count( $sidebars_widgets[ $current_sidebar ] ) - 1;
					if ( 0 < $position && $sidebar_length > $position ) {
						// If the position of the widget is not first or last.
						$array_chunk = array_splice( $sidebars_widgets[ $current_sidebar ], $position );
						array_shift( $array_chunk );
						$sidebars_widgets[ $current_sidebar ] = array_merge( $sidebars_widgets[ $current_sidebar ], $array_chunk );
					} elseif ( $sidebar_length === $position ) {
						// If the widget is last in the array.
						array_pop( $sidebars_widgets[ $current_sidebar ] );
					} elseif ( 0 === $position ) {
						// If widget is first in the array.
						if ( is_array( $sidebars_widgets[ $current_sidebar ] ) ) {
							// If there are multiple widgets in the array remove the first element.
							array_shift( $sidebars_widgets[ $current_sidebar ] );
						} else {
							// If there is only one widget in the sidebar then set it back to an empty array.
							$sidebars_widgets[ $current_sidebar ] = array();
						}
					}

					// Save modified sidebars widgets.
					wp_set_sidebars_widgets( $sidebars_widgets );
				}

				// Get widget settings.
				$settings = $the_widget->get_settings();
				// If widget instance is set we need to remove it.
				if ( array_key_exists( $request['number'], $settings ) ) {
					unset( $settings[ $request['number'] ] );
					$the_widget->save_settings( $settings );
					return new WP_REST_Response( $settings, 200 );
				}
			} else {
				return new WP_Error( 'cant-delete-widget', __( 'The widget ID provided does not exist.', 'non-existant-text-domain' ), array( 'status' => 404 ) );
			}
		} else {
			return new WP_Error( 'cant-delete-widget', __( 'Please specify a widget base and ID in your request.', 'non-existant-text-domain' ), array( 'status' => 400 ) );
		}
		return new WP_Error( 'cant-delete-widget', __( 'Something went wrong with your request. Please try again.', 'non-existant-text-domain' ), array( 'status' => 500 ) );
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
