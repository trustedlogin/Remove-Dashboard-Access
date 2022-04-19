<?php
/**
 * Plugin Name: Remove Dashboard Access
 * Plugin URI: https://www.trustedlogin.com/remove-dashboard-access/
 * Description: Removes Dashboard access for certain users based on capability.
 * Version: 1.1.5
 * Author: TrustedLogin
 * Author URI: https://www.trustedlogin.com
 * License: GPLv2
*/

// Bail if called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// RDA_Options Class
require_once( dirname( __FILE__ ) . '/inc/class.rda-options.php' );

// RDA_Remove_Access Class
require_once( dirname( __FILE__ ) . '/inc/class.rda-remove-access.php' );

// Load options instance
if ( class_exists( 'RDA_Options' ) ) {
	$load = new RDA_Options;

	// Set up options array on activation.
	register_activation_hook( __FILE__, array( $load, 'activate' ) );

	// Run it
	if ( class_exists( 'RDA_Remove_Access' ) ) {
		$access = new RDA_Remove_Access( $load->capability(), $load->settings );
	}
}
