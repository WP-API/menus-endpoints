<?php
/**
 * REST API: WP_REST_Nav_Menus_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 5.x
 */

/**
 * Core controller used to access menus via the REST API.
 *
 * @since 5.x
 *
 * @see WP_REST_Posts_Controller
 */
class WP_REST_Nav_Menus_Controller extends WP_REST_Terms_Controller {

	/**
	 * Prepares links for the request.
	 *
	 * @since 5.x
	 *
	 * @param object $term Term object.
	 * @return array Links for the given term.
	 */
	protected function prepare_links( $term ) {

		$links = parent::prepare_links( $term );

		// Let's make sure that menu items are embeddable for a menu collection.
		if ( array_key_exists( 'https://api.w.org/post_type', $links ) ) {
			$post_type_links = $links['https://api.w.org/post_type'];

			foreach ( $post_type_links as $index => $post_type_link ) {
				if ( ! array_key_exists( 'href', $post_type_link ) || strpos( $post_type_link['href'], '/menu-items?' ) === false ) {
					continue;
				}

				$post_type_links[ $index ]['embeddable'] = true;
			}

			$links['https://api.w.org/post_type'] = $post_type_links;
		}

		return $links;
	}

}
