<?php
/**
 * Remove Dashboard Access Class
 *
 * @since 1.0
 */

if ( ! class_exists( 'RDA_Remove_Access' ) ) {
/**
 * Class used to remove access to the admin back-end.
 *
 * @since 1.0
 */
class RDA_Remove_Access {

	/**
	 * Capability needed to access the dashboard.
	 *
	 * @since 1.0
	 * @var   string $capability
	 */
	var $capability;

	/**
	 * RDA Settings.
	 *
	 * @since 1.0
	 * @var   array $settings
	 */
	var $settings = array();

	/**
	 * Sets up the mechanism by which dashboard access is determined.
	 *
	 * @since 1.0
	 * @since 1.1.3 Moved `is_user_allowed()` to the {@see 'init'} hook.
	 *
	 * @param string $capability Capability needed to gain dashboard access.
	 * @param array  $settings   RDA settings array.
	 */
	function __construct( $capability, $settings ) {
		// Bail if the capability is empty.
		if ( empty( $capability ) ) {
			return;
		}

		$this->capability = $capability;
		$this->settings   = $settings;

		add_action( 'init', array( $this, 'is_user_allowed' ) );
	}

	/**
	 * Determines if the current user is allowed to access the admin back end.
	 *
	 * @since 1.0
	 *
	 * @uses current_user_can() Checks whether the current user has the specified capability.
	 * @return bool False if the current user lacks the requisite capbility. True otherwise.
	 */
	function is_user_allowed() {
		if ( $this->capability && ! current_user_can( $this->capability ) && ! defined( 'DOING_AJAX' ) ) {
			// Remove access.
			$this->lock_it_up();

			return false;
		}
		return true;
	}

	/**
	 * Registers callbacks for "locking up" the dashboard.
	 *
	 * @since 1.0
	 */
	function lock_it_up() {
		add_action( 'admin_init',     array( $this, 'dashboard_redirect' ) );
		add_action( 'admin_head',     array( $this, 'hide_menus' ) );
		add_action( 'admin_bar_menu', array( $this, 'hide_toolbar_items' ), 999 );
	}

	/**
	 * Hides menus other than profile.php.
	 *
	 * @since 1.1
	 */
	public function hide_menus() {
		/** @global array $menu */
		global $menu;

		$menu_ids = array();

		if ( ! empty( $menu ) && is_array( $menu ) ) {
			// Gather menu IDs (minus profile.php).
			foreach ( $menu as $index => $values ) {

				if ( isset( $values[2] ) && 'profile.php' === $values[2] ) {
					continue;
				}

				// Remove menu pages.
				remove_menu_page( $values[2] );
			}
		}
	}

	/**
	 * Handles the redirect for disallowed users.
	 *
	 * @since 0.1
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
	 * Hides Toolbar items for disallowed users.
	 *
	 * @since 1.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Toolbar instance.
	 */
	function hide_toolbar_items( $wp_admin_bar ) {
		$edit_profile = ! $this->settings['enable_profile'] ? 'edit-profile' : '';

		if ( is_admin() ) {
			$ids = array( 'about', 'comments', 'new-content', $edit_profile );

			/**
			 * Filters Toolbar menus to remove within the admin.
			 *
			 * @since 1.0
			 *
			 * @param array $ids Toolbar menu IDs to remove.
			 */
			$nodes = apply_filters( 'rda_toolbar_nodes', $ids );
		} else {
			$ids = array( 'about', 'dashboard', 'comments', 'new-content', 'edit', $edit_profile );

			/**
			 * Filters Toolbar menus to remove on the front end.
			 *
			 * @since 1.0
			 *
			 * @param array $ids Toolbar menu IDs to remove.
			 */
			$nodes = apply_filters( 'rda_frontend_toolbar_nodes', $ids );
		}

		foreach ( $nodes as $id ) {
			$wp_admin_bar->remove_menu( $id );
		}
	}	

} // RDA_Remove_Access

} // class_exists
