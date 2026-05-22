<?php
/**
 * Remove Dashboard Access Uninstall
 *
 * @since 1.0
 */

// Bail if not called from WordPress's uninstall flow.
// `return` (rather than `exit`) is used so the file is unit-testable via
// `require` while still being safe under direct web invocation — at script
// top-level, `return` ends execution the same way `exit` would.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

$settings = array(
	'rda_access_switch',
	'rda_access_cap',
	'rda_redirect_url',
	'rda_enable_profile',
	'rda_login_message',
	'rda_lock_ajax',
	'rda_url_allowlist',
);

foreach ( $settings as $setting ) {
	delete_option( $setting );
}
