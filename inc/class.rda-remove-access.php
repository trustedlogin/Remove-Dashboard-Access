<?php
/**
 * Remove Dashboard Access Class
 *
 * @since 1.0
 */

if ( ! class_exists( 'RDA_Remove_Access' ) ) {
class RDA_Remove_Access {

	/**
	 * @var string $capability
	 *
	 * String with capability passed from RDA_Options{}
	 *
	 * @since 1.0
	 */
	var $capability;

	/**
	 * @var array $settings
	 *
	 * Array of settings passed from RDA_Options{}
	 *
	 * @since 1.0
	 */
	var $settings = array();

	/**
	 * RDA Remove Access Init
	 *
	 * @since 1.0
	 * @since 1.1.3 Moved `is_user_allowed()` to the {@see 'init'} hook.
	 *
	 * @param string $capability Capability passed from RDA_Options instance.
	 * @param array $settings Settings array passed from RDA_Options instance.
	 */
	function __construct( $capability, $settings ) {
		if ( empty( $capability ) ) {
			return; // Bail
		} else {
			$this->capability = $capability;
		}

		$this->settings = $settings;

		add_action( 'init', array( $this, 'is_user_allowed' ) );
	}

	/**
	 * Determine if user is allowed to access the Dashboard.
	 *
	 * @since 1.0
	 *
	 * @uses current_user_can() Checks whether the current user has the specified capability.
	 * @return null Bail if the current user has the requisite capability.
	 */
	function is_user_allowed() {
		if ( $this->capability && ! current_user_can( $this->capability ) && ! defined( 'DOING_AJAX' ) ) {
			$this->lock_it_up();
		} else {
			return; // Bail
		}
	}

	/**
	 * "Lock it up" Hooks.
	 *
	 * dashboard_redirect - Handles redirecting disallowed users.
	 * hide_menus         - Hides the admin menus.
	 * hide_toolbar_items - Hides various Toolbar items on front and back-end.
	 *
	 * @since 1.0
	 */
	function lock_it_up() {
		add_action( 'admin_init',     array( $this, 'dashboard_redirect' ) );
		add_action( 'admin_head',     array( $this, 'hide_menus' ) );
		add_action( 'admin_bar_menu', array( $this, 'hide_toolbar_items' ), 999 );
	}

	/**
	 * Hide menus other than profile.php.
	 *
	 * @since 1.1
	 */
	public function hide_menus() {
		/** @global array $menu */
		global $menu;

		$menu_ids = array();

		// Gather menu IDs (minus profile.php).
		foreach ( $menu as $index => $values ) {
			if ( isset( $values[2] ) ) {
				if ( 'profile.php' == $values[2] ) {
					continue;
				}

				// Remove menu pages.
				remove_menu_page( $values[2] );
			}
		}
	}

	/**
	 * Dashboard Redirect.
	 *
	 * @since 0.1
	 *
	 * @see wp_redirect() Used to redirect disallowed users to chosen URL.
	 */
	function dashboard_redirect() {
		/** @global string $pagenow */
		global $pagenow;

		if ( 'profile.php' != $pagenow || ! $this->settings['enable_profile'] ) {
			wp_redirect( $this->settings['redirect_url'] );
			exit;
		}
	}

	/**
	 * Hide Toolbar Items.
	 *
	 * @since 1.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar For remove_node() method access.
	 */
	function hide_toolbar_items( $wp_admin_bar ) {
		$edit_profile = ! $this->settings['enable_profile'] ? 'edit-profile' : '';
		if ( is_admin() ) {
			$ids = array( 'about', 'comments', 'new-content', $edit_profile );
			$nodes = apply_filters( 'rda_toolbar_nodes', $ids );
		} else {
			$ids = array( 'about', 'dashboard', 'comments', 'new-content', 'edit', $edit_profile );
			$nodes = apply_filters( 'rda_frontend_toolbar_nodes', $ids );
		}
		foreach ( $nodes as $id ) {
			$wp_admin_bar->remove_menu( $id );
		}
	}	

} // RDA_Remove_Access

} // class_exists
