<?php
/**
 * Remove Dashboard Access Uninstall
 *
 * @since 1.0
 */
$settings = array(
	'rda_access_switch',
	'rda_access_cap',
	'rda_redirect_url',
	'rda_enable_profile',
	'rda_login_message'
);

foreach ( $settings as $setting ) {
	delete_option( $setting );
}
