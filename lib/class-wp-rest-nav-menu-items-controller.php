<?php
/**
 * REST API: WP_REST_Nav_Menu_Items_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 5.x
 */

/**
 * Core controller used to access menu items via the REST API.
 *
 * @since 5.x
 *
 * @see WP_REST_Posts_Controller
 */
class WP_REST_Nav_Menu_Items_Controller extends WP_REST_Posts_Controller {

	/**
	 * Constructor
	 *
	 * @since 5.x
	 *
	 * @param string $post_type Post type name
	 */
	public function __construct( $post_type ) {

		parent::__construct( $post_type );

		add_filter( "rest_pre_insert_{$post_type}", array( $this, 'pre_insert_item' ), 0, 2 );
		add_action( "rest_insert_{$post_type}", array( $this, 'post_insert_item' ), 0, 2 );
		add_action( "rest_after_insert_{$post_type}", array( $this, 'post_insert_item' ), 0, 2 );
		add_filter( "rest_prepare_{$post_type}", array( $this, 'prepare_item_response' ), 0, 3 );

	}

	/**
	 * Sets the menu item properties before storing the post in the database.
	 *
	 * @since 5.x
	 *
	 * @param stdClass        $prepared_post Post
	 * @param WP_REST_Request $request Request
	 *
	 * @return stdClass|WP_Error
	 */
	public function pre_insert_item( $prepared_post, $request ) {

		$schema = $this->get_item_schema();

		// Set the menu item type (stored as meta)
		if ( ! empty( $schema['properties']['item_type'] ) && isset( $request['item_type'] ) ) {
			$prepared_post->meta_input['_menu_item_type'] = $request['item_type'];
		}

		// Set the menu item attr_title (stored as post excerpt)
		if ( ! empty( $schema['properties']['attr_title'] ) && isset( $request['attr_title'] ) ) {
			$prepared_post->post_excerpt = $request['attr_title'];
		}

		// Set the menu item classes (stored as meta)
		if ( ! empty( $schema['properties']['classes'] ) && isset( $request['classes'] ) ) {
			$prepared_post->meta_input['_menu_item_classes'] = $request['classes'];
		}

		// Set the menu item description (stored as post content)
		if ( ! empty( $schema['properties']['description'] ) && isset( $request['description'] ) ) {
			$prepared_post->post_content = $request['description'];
		}

		if ( ! empty( $schema['properties']['menus'] ) ) {
			if ( empty( $request['menus'] ) ) {
				// If no menu is set, go ahead and mark as orphaned.
				$prepared_post->meta_input['_menu_item_orphaned'] = (string) time();
			}
		}

		// Set the menu item object type (stored as meta)
		if ( ! empty( $schema['properties']['object'] ) && isset( $request['object'] ) ) {
			$prepared_post->meta_input['_menu_item_object'] = $request['object'];
		}

		// Set the menu item object id (stored as meta)
		if ( ! empty( $schema['properties']['object_id'] ) && isset( $request['object_id'] ) ) {
			$prepared_post->meta_input['_menu_item_object_id'] = $request['object_id'];
		}

		// Set the menu item parent (stored as meta)
		if ( ! empty( $schema['properties']['parent'] ) && isset( $request['parent'] ) ) {
			$prepared_post->meta_input['_menu_item_menu_item_parent'] = $request['parent'];
		}

		// Set the menu item parent (stored as meta)
		if ( ! empty( $schema['properties']['target'] ) && isset( $request['target'] ) ) {
			$prepared_post->meta_input['_menu_item_target'] = $request['target'];
		}

		// Set the menu item URL (stored as meta)
		if ( ! empty( $schema['properties']['url'] ) && isset( $request['url'] ) ) {
			$prepared_post->meta_input['_menu_item_url'] = $request['url'];
		}

		// Set the menu item xfn (stored as meta)
		if ( ! empty( $schema['properties']['xfn'] ) && isset( $request['xfn'] ) ) {
			$prepared_post->meta_input['_menu_item_xfn'] = $request['xfn'];
		}

		return $prepared_post;
	}

	/**
	 * Handles special cases after a post has been updated.
	 *
	 * @since 5.x
	 *
	 * @param WP_Post         $post Post object.
	 * @param WP_REST_Request $request Request object.
	 */
	public function post_insert_item( $post, $request ) {
		// If a menu is set, make sure we remove the orphaned marker.
		if ( ! empty( $request['menus'] ) ) {
			delete_post_meta( $post->ID, '_menu_item_orphaned' );
		}
	}

	/**
	 * Prepares a single item response.
	 *
	 * @since 5.x
	 *
	 * @param WP_REST_Response $response Response object.
	 * @param WP_Post          $post Post object.
	 * @param WP_REST_Request  $request Request object.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_response( $response, $post, $request ) {

		$menu_item = wp_setup_nav_menu_item( $post );

		$response->data['attr_title']        = $menu_item->attr_title; // Same as post_excerpt
		$response->data['classes']           = $menu_item->classes;
		$response->data['description']       = $menu_item->description; // Same as post_content
		$response->data['item_type']         = $menu_item->type; // Using 'item_type' since 'type' already exists.
		$response->data['item_type_label']   = $menu_item->type_label; // Using 'item_type_label' to match up with 'item_type' - IS READ ONLY!
		$response->data['object']            = $menu_item->object;
		$response->data['object_id']         = absint( $menu_item->object_id ); // Usually is a string, but lets expose as an integer.
		$response->data['parent']            = absint( $menu_item->menu_item_parent ); // Same as post_parent, expose as integer
		$response->data['target']            = $menu_item->target;
		$response->data['title']['rendered'] = $menu_item->title; // Overwrites 'title' (should be same as post_title)
		$response->data['url']               = $menu_item->url;
		$response->data['xfn']               = $menu_item->xfn;

		return $response;
	}

	/**
	 * Retrieves the menu item's schema, conforming to JSON Schema.
	 *
	 * @since 5.x
	 *
	 * @return array Item schema as an array.
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		$schema['properties']['attr_title'] = array(
			'description' => __( 'Text for the title attribute of the link element for this menu item.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit', 'embed' ),
			'arg_options' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

		$schema['properties']['classes'] = array(
			'description' => __( 'Class names for the link element of this menu item.' ),
			'type'        => 'array',
			'context'     => array( 'view', 'edit', 'embed' ),
			'arg_options' => array(
				'sanitize_callback' => function ( $value ) {
					return array_map( 'sanitize_html_class', explode( ' ', $value ) );
				},
			),
		);

		$schema['properties']['description'] = array(
			'description' => __( 'The description of this menu item.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit', 'embed' ),
			'arg_options' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

		$schema['properties']['item_type'] = array(
			'description' => __( 'The family of objects originally represented, such as "post_type" or "taxonomy".' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit', 'embed' ),
			'default'     => 'custom',
			'arg_options' => array(
				'sanitize_callback' => 'sanitize_key',
			),
			'required'    => true,
		);

		$schema['properties']['item_type_label'] = array(
			'description' => __( 'The singular label used to describe this type of menu item.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit', 'embed' ),
			'readonly'    => true,
		);

		$schema['properties']['menu_order'] = array(
			'description' => __( 'The order of the object in relation to other objects of its type.' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit', 'embed' ),
		);

		$schema['properties']['menus'] = array(
			'description' => __( 'The IDs representing the menus to which this menu item should be added.' ),
			'type'        => 'array',
			'context'     => array( 'view', 'edit', 'embed' ),
		);

		$schema['properties']['object'] = array(
			'description' => __( 'The type of object originally represented, such as "category," "post", or "attachment."' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'default'     => 'custom',
			'arg_options' => array(
				'sanitize_callback' => 'sanitize_key',
			),
			'required'    => true,
		);

		$schema['properties']['object_id'] = array(
			'description' => __( 'The DB ID of the original object this menu item represents, e.g. ID for posts and term_id for categories.' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'default'     => 0,
			'required'    => true,
		);

		$schema['properties']['parent'] = array(
			'description' => __( "The DB ID of the nav_menu_item that is this item's menu parent, if any. 0 otherwise." ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['target'] = array(
			'description' => __( 'The target attribute of the link element for this menu item.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit', 'embed' ),
			'enum'        => array(
				'_blank',
				'',
			),
		);

		$schema['properties']['title']['required'] = true;

		$schema['properties']['url'] = array(
			'description' => __( 'The URL to which this menu item points.' ),
			'type'        => 'string',
			'format'      => 'uri',
			'context'     => array( 'view', 'edit', 'embed' ),
			'arg_options' => array(
				'sanitize_callback' => 'esc_url_raw',
			),
		);

		$schema['properties']['xfn'] = array(
			'description' => __( 'The XFN relationship expressed in the link of this menu item.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit', 'embed' ),
			'arg_options' => array(
				'sanitize_callback' => function ( $value ) {
					return implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $value ) ) );
				},
			),
		);

		unset(
			$schema['properties']['link'],
			$schema['properties']['password']
		);

		return $schema;
	}
}
