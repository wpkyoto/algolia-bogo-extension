<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Algolia_Bogo
 */

// Get the tests directory.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

// If we're running inside wp-env, use the default path.
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/algolia-bogo.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

