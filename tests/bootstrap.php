<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Woocommerce_Restricted_Address
 */

$wrs_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $wrs_tests_dir ) {
	$wrs_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $wrs_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $wrs_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $wrs_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function wrs_manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/woocommerce-restricted-states.php';
}
tests_add_filter( 'muplugins_loaded', 'wrs_manually_load_plugin' );

// Start up the WP testing environment.
require $wrs_tests_dir . '/includes/bootstrap.php';
