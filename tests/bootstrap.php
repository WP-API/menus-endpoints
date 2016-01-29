<?php
/**
 * Bootstrap the plugin unit testing environment.
 *
 * @package WordPress
 * @subpackage JSON API
 */

/**
 * Determine where the WP test suite lives.
 *
 * Support for:
 * 1. `WP_DEVELOP_DIR` environment variable, which points to a checkout
 *   of the develop.svn.wordpress.org repository (this is recommended)
 * 2. `WP_TESTS_DIR` environment variable, which points to a checkout
 * 3. `WP_ROOT_DIR` environment variable, which points to a checkout
 * 4. Plugin installed inside of WordPress.org developer checkout
 * 5. Tests checked out to /tmp
 */
if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$test_root = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} else if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	$test_root = getenv( 'WP_TESTS_DIR' );
} else if ( false !== getenv( 'WP_ROOT_DIR' ) ) {
	$test_root = getenv( 'WP_ROOT_DIR' ) . '/tests/phpunit';
} else if ( file_exists( '../../../../tests/phpunit/includes/bootstrap.php' ) ) {
	$test_root = '../../../../tests/phpunit';
} else if ( file_exists( '/tmp/wordpress-tests-lib/includes/bootstrap.php' ) ) {
	$test_root = '/tmp/wordpress-tests-lib';
}

if ( file_exists( dirname( dirname( __FILE__ ) ) . '/wp-api/plugin.php' ) ) {
	define( 'WP_API_ROOT', dirname( dirname( __FILE__ ) ) . '/wp-api' );
} else if ( dirname( dirname( dirname( __FILE__ ) ) ) . '/wp-api/plugin.php' ) {
	define( 'WP_API_ROOT', dirname( dirname( dirname( __FILE__ ) ) ) . '/wp-api' );
}

require $test_root . '/includes/functions.php';

function _manually_load_plugin() {
	require WP_API_ROOT . '/plugin.php';
	require dirname( __FILE__ ) . '/../plugin.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $test_root . '/includes/bootstrap.php';

// Helper classes
if ( ! class_exists( 'WP_Test_REST_TestCase' ) ) {
	require_once WP_API_ROOT . '/tests/class-wp-test-rest-testcase.php';
}

require_once WP_API_ROOT . '/tests/class-wp-test-rest-controller-testcase.php';
require_once WP_API_ROOT . '/tests/class-wp-test-rest-post-type-controller-testcase.php';
require_once WP_API_ROOT . '/tests/class-wp-test-spy-rest-server.php';
require_once WP_API_ROOT . '/tests/class-wp-rest-test-controller.php';
