<?php
/**
 * PHPUnit bootstrap for Remove Dashboard Access.
 *
 * Designed to run inside @wordpress/env's `tests-cli` container, where the WP
 * test library is pre-mounted at /wordpress-phpunit. WP_TESTS_DIR can override
 * the path if the suite is run elsewhere (CI, devcontainer, etc.).
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = '/wordpress-phpunit';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	fwrite(
		STDERR,
		"Could not find WP test library at {$_tests_dir}/includes/functions.php\n" .
		"Set WP_TESTS_DIR to the path of your WP test install, or run via " .
		"`npm run test:unit:php` (which uses wp-env's tests-cli container).\n"
	);
	exit( 1 );
}

// Yoast polyfills required for WP test framework on PHPUnit 9+.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin before WP boots so its hooks register inside the
 * test environment.
 */
function _rda_manually_load_plugin() {
	require dirname( __DIR__ ) . '/remove-dashboard-access.php';
}
tests_add_filter( 'muplugins_loaded', '_rda_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

require_once __DIR__ . '/phpunit/class-rda-testcase.php';
