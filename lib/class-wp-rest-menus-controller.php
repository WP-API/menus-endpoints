<?php
/**
 * REST API: WP_REST_Menus_Controller class
 *
 * @package    WordPress
 * @subpackage REST_API
 */

/**
 * Core class used to managed menu terms associated via the REST API.
 *
 * @see WP_REST_Controller
 */
class WP_REST_Menus_Controller extends WP_REST_Terms_Controller {

	/**
	 * Get the term, if the ID is valid.
	 *
	 * @param int $id Supplied ID.
	 *
	 * @return WP_Term|WP_Error Term object if ID is valid, WP_Error otherwise.
	 */
	protected function get_term( $id ) {
		$term = parent::get_term( $id );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$nav_term = wp_get_nav_menu_object( $term );

		return $nav_term;
	}

	/**
	 * Checks if a request has access to create a term.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True if the request has access to create items, false or WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		$check = $this->check_assign_locations_permission( $request );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		return parent::create_item_permissions_check( $request );
	}

	/**
	 * Checks if a request has access to update the specified term.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True if the request has access to update the item, false or WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		$check = $this->check_assign_locations_permission( $request );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		return parent::update_item_permissions_check( $request );
	}

	/**
	 * Checks whether current user can assign all locations sent with the current request.
	 *
	 * @param WP_REST_Request $request The request object with post and locations data.
	 *
	 * @return bool Whether the current user can assign the provided terms.
	 */
	protected function check_assign_locations_permission( $request ) {
		if ( ! isset( $request['locations'] ) ) {
			return true;
		}

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new WP_Error( 'rest_cannot_assign_location', __( 'Sorry, you are not allowed to assign the provided locations.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		foreach ( $request['locations'] as $location ) {
			if ( ! array_key_exists( $location, get_registered_nav_menus() ) ) {
				return new WP_Error(
					'rest_menu_location_invalid',
					__( 'Invalid menu location.' ),
					array(
						'status' => 404,
						'data'   => $location,
					)
				);
			}
		}

		return true;
	}

	/**
	 * Prepares a single term output for response.
	 *
	 * @param obj             $term    Term object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response $response Response object.
	 */
	public function prepare_item_for_response( $term, $request ) {
		$nav_menu = wp_get_nav_menu_object( $term );

		return parent::prepare_item_for_response( $nav_menu, $request );
	}

	/**
	 * Prepares a single term for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array $prepared_term Term object.
	 */
	public function prepare_item_for_database( $request ) {
		$prepared_term = parent::prepare_item_for_database( $request );

		$prepared_term = (array) $prepared_term;
		$schema        = $this->get_item_schema();
		if ( isset( $request['name'] ) && ! empty( $schema['properties']['name'] ) ) {
			$prepared_term['menu-name'] = $request['name'];
		}

		return $prepared_term;
	}

	/**
	 * Creates a single term in a taxonomy.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		if ( isset( $request['parent'] ) ) {
			if ( ! is_taxonomy_hierarchical( $this->taxonomy ) ) {
				return new WP_Error( 'rest_taxonomy_not_hierarchical', __( 'Cannot set parent term, taxonomy is not hierarchical.' ), array( 'status' => 400 ) );
			}

			$parent = wp_get_nav_menu_object( (int) $request['parent'] );

			if ( ! $parent ) {
				return new WP_Error( 'rest_term_invalid', __( 'Parent term does not exist.' ), array( 'status' => 400 ) );
			}
		}

		$prepared_term = $this->prepare_item_for_database( $request );

		$term = wp_update_nav_menu_object( 0, wp_slash( (array) $prepared_term ) );

		if ( is_wp_error( $term ) ) {
			/*
			 * If we're going to inform the client that the term already exists,
			 * give them the identifier for future use.
			 */
			$term_id = $term->get_error_data( 'term_exists' );
			if ( $term_id ) {
				$existing_term = get_term( $term_id, $this->taxonomy );
				$term->add_data( $existing_term->term_id, 'term_exists' );
				$term->add_data(
					array(
						'status'  => 400,
						'term_id' => $term_id,
					)
				);
			}

			return $term;
		}

		$term = $this->get_term( $term );

		/**
		 * Fires after a single term is created or updated via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->taxonomy`, refers to the taxonomy slug.
		 *
		 * @param WP_Term         $term     Inserted or updated term object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a term, false when updating.
		 */
		do_action( "rest_insert_{$this->taxonomy}", $term, $request, true );

		$schema = $this->get_item_schema();
		if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
			$meta_update = $this->meta->update_value( $request['meta'], $term->term_id );

			if ( is_wp_error( $meta_update ) ) {
				return $meta_update;
			}
		}

		$locations_update = $this->handle_locations( $term->term_id, $request );

		if ( is_wp_error( $locations_update ) ) {
			return $locations_update;
		}

		$fields_update = $this->update_additional_fields_for_object( $term, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'view' );

		/**
		 * Fires after a single term is completely created or updated via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->taxonomy`, refers to the taxonomy slug.
		 *
		 * @param WP_Term         $term     Inserted or updated term object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a term, false when updating.
		 *
		 * @since 5.0.0
		 */
		do_action( "rest_after_insert_{$this->taxonomy}", $term, $request, true );

		$response = $this->prepare_item_for_response( $term, $request );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );
		$response->header( 'Location', rest_url( $this->namespace . '/' . $this->rest_base . '/' . $term->term_id ) );

		return $response;
	}

	/**
	 * Updates a single term from a taxonomy.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$term = $this->get_term( $request['id'] );
		if ( is_wp_error( $term ) ) {
			return $term;
		}

		if ( isset( $request['parent'] ) ) {
			if ( ! is_taxonomy_hierarchical( $this->taxonomy ) ) {
				return new WP_Error( 'rest_taxonomy_not_hierarchical', __( 'Cannot set parent term, taxonomy is not hierarchical.' ), array( 'status' => 400 ) );
			}

			$parent = get_term( (int) $request['parent'], $this->taxonomy );

			if ( ! $parent ) {
				return new WP_Error( 'rest_term_invalid', __( 'Parent term does not exist.' ), array( 'status' => 400 ) );
			}
		}

		$prepared_term = $this->prepare_item_for_database( $request );

		// Only update the term if we haz something to update.
		if ( ! empty( $prepared_term ) ) {
			$update = wp_update_nav_menu_object( $term->term_id, wp_slash( (array) $prepared_term ) );

			if ( is_wp_error( $update ) ) {
				return $update;
			}
		}

		$term = get_term( $term->term_id, $this->taxonomy );

		/** This action is documented in wp-includes/rest-api/endpoints/class-wp-rest-terms-controller.php */
		do_action( "rest_insert_{$this->taxonomy}", $term, $request, false );

		$schema = $this->get_item_schema();
		if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
			$meta_update = $this->meta->update_value( $request['meta'], $term->term_id );

			if ( is_wp_error( $meta_update ) ) {
				return $meta_update;
			}
		}

		$locations_update = $this->handle_locations( $term->term_id, $request );

		if ( is_wp_error( $locations_update ) ) {
			return $locations_update;
		}

		$fields_update = $this->update_additional_fields_for_object( $term, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'view' );

		/** This action is documented in wp-includes/rest-api/endpoints/class-wp-rest-terms-controller.php */
		do_action( "rest_after_insert_{$this->taxonomy}", $term, $request, false );

		$response = $this->prepare_item_for_response( $term, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Deletes a single term from a taxonomy.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$term = $this->get_term( $request['id'] );
		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for terms.
		if ( ! $force ) {
			/* translators: %s: force=true */
			return new WP_Error( 'rest_trash_not_supported', sprintf( __( "Terms do not support trashing. Set '%s' to delete." ), 'force=true' ), array( 'status' => 501 ) );
		}

		$request->set_param( 'context', 'view' );

		$previous = $this->prepare_item_for_response( $term, $request );

		$retval = wp_delete_nav_menu( $term );

		if ( ! $retval ) {
			return new WP_Error( 'rest_cannot_delete', __( 'The term cannot be deleted.' ), array( 'status' => 500 ) );
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $previous->get_data(),
			)
		);

		/**
		 * Fires after a single term is deleted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->taxonomy`, refers to the taxonomy slug.
		 *
		 * @param WP_Term          $term     The deleted term.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "rest_delete_{$this->taxonomy}", $term, $response, $request );

		return $response;
	}

	/**
	 * Updates the menu's locations from a REST request.
	 *
	 * @param int             $menu_id The menu id to update the location form.
	 * @param WP_REST_Request $request The request object with menu and locations data.
	 *
	 * @return null|WP_Error WP_Error on an error assigning any of the locations, otherwise null.
	 */
	protected function handle_locations( $menu_id, $request ) {
		if ( ! isset( $request['locations'] ) ) {
			return true;
		}

		$menu_locations = get_registered_nav_menus();
		$menu_locations = array_keys( $menu_locations );
		$new_locations  = array();
		foreach ( $request['locations'] as $location ) {
			if ( ! in_array( $location, $menu_locations, true ) ) {
				return new WP_Error( 'none_exist_location', __( 'Menu location does not exist.' ), array( 'status' => 400 ) );
			}
			$new_locations[ $location ] = $menu_id;
		}
		$assigned_menu = get_nav_menu_locations();
		foreach ( $assigned_menu as $location => $term_id ) {
			if ( $term_id === $menu_id ) {
				unset( $assigned_menu[ $location ] );
			}
		}
		$new_assignments = array_merge( $new_locations, $assigned_menu );
		set_theme_mod( 'nav_menu_locations', $new_assignments );

		return true;
	}

	/**
	 * Retrieves the term's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();
		unset( $schema['properties']['count'] );
		unset( $schema['properties']['link'] );
		unset( $schema['properties']['taxonomy'] );

		$schema['properties']['locations'] = array(
			'description' => __( 'The locations assigned to the menu.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
			'context'     => array( 'view', 'edit' ),
		);

		return $schema;
	}
}
