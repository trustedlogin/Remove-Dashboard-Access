<?php
/*
Plugin Name: Remove Dashboard Access for Non-Admins
Plugin URI: http://www.werdswords.com
Description: Removes Dashboard access for non-admin users
Version: 0.4
Author: DrewAPicture
Author URI: http://www.werdswords.com
License: GPLv2
*/

class Remove_Dashboard_Access {
	function __construct() {
		add_action( 'admin_init', array( $this, 'rda_redirect' ) );
	}
	
	function rda_direct() {
		if ( ! current_user_can( 'manage_options' ) && ! defined( 'DOING_AJAX' ) ) {
			wp_redirect( home_url() );
			exit;
		}
	}
}

new Remove_Dashboard_Access;