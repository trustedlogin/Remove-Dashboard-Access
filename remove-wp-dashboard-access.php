<?php
/**
 * Plugin Name: Remove Dashboard Access
 * Plugin URI: http://www.werdswords.com
 * Description: Removes Dashboard access for certain users based on capability.
 * Version: 1.0
 * Author: Drew Jaynes (DrewAPicture)
 * Author URI: http://www.drewapicture.com
 * License: GPLv2
*/

require_once( dirname( __FILE__ ) .	'/inc/class-rda-options.php' );       // RDA_Options Class
require_once( dirname( __FILE__ ) . '/inc/class-rda-remove-access.php' ); // RDA_Remove_Access Class

// Load options instance
if ( class_exists( 'RDA_Options' ) )
	$load = new RDA_Options;

// Run it
if ( class_exists( 'RDA_Remove_Access' ) )
	new RDA_Remove_Access( $load->capability(), $load->settings );
