<?php
/*
Plugin Name: Remove Dashboard Access
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
		add_action( 'admin_head', array( $this, 'hide_menus' ) );
		add_action( 'admin_bar_menu' array( $this, 'hide_toolbar_items' ), 999 );
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
		global $pagenow;
		if ( 'profile.php' != $pagenow && ! current_user_can( $this->user_cap() ) && ! defined( 'DOING_AJAX' ) ) {
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

	/**
	 * Hide Admin Menus
	 *
	 * @since 0.5
	 *
	 * @return null
	 */
	function hide_menus() {
		?>
		<style type="text/css">
		#adminmenuback, #adminmenuwrap {
			display: none;
		}
		.wrap {
			margin-top: 1.5%;
		}
		#wpcontent {
			margin-left: 2%;
		}
		<?php
	}

	/**
	 * Hide Toolbar Items
	 *
	 * @since 1.0
	 *
	 * @param $wp_admin_bar global
	 * @return null
	 */
	function hide_toolbar_items( $wp_admin_bar ) {
		// global $wp_admin_bar;
		$args = array( 'about', 'comments', 'new-content' );
		$nodes = apply_filters( 'rda_toolbar_nodes', $args );
		foreach ( $nodes as $id ) {
			$wp_admin_bar->remove_node( $id );
		}
		if ( ! is_admin() )
			$wp_admin_bar->remove_node( 'dashboard' );
	}
}

new Remove_Dashboard_Access;