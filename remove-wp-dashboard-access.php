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
	/**
	 * Init
	 *
	 * @since 0.5
	 */
	function __construct() {
		add_action( 'admin_init', array( $this, 'dashboard_redirect' ) );
	}
	
	/**
	 * Dashboard Redirect
	 *
	 * @since 0.1
	 *
	 * @uses current_user_can() to determine user capability for accessing the Dashboard
	 * @uses wp_redirect() to redirect unauthorized users
	 * @return null
	 */
	function dashboard_redirect() {
		if ( ! current_user_can( $this->user_cap() ) && ! defined( 'DOING_AJAX' ) ) {
			wp_redirect( $this->redirect_url() );
			exit;
		}
	}

	/**
	 * User Capability
	 *
	 * @since 0.5
	 *
	 * @return string user capability
	 */
	function user_cap() {
		return apply_filters( 'rda_dashboard_cap', 'manage_options' );
	}

	/**
	 * Redirect URL
	 *
	 * @since 0.5
	 *
	 * @return string URL to redirect unauthorized users to.
	 */
	function redirect_url() {
		return apply_filters( 'rda_redirect_url', home_url() );
	}
}

new Remove_Dashboard_Access;