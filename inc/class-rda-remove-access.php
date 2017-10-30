<?php
/**
 * RDA_Remove_Access class file
 *
 * @since 1.2.0
 *
 * @package Remove_Dashboard_Access\Core
 */

/**
 * Core RDA class to handle managing admin access.
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
	public $capability;

	/**
	 * RDA Settings.
	 *
	 * @since 1.0
	 * @var   array $settings
	 */
	public $settings = array();

	/**
	 * Sets up the mechanism by which dashboard access is determined.
	 *
	 * @since 1.0
	 * @since 1.1.3 Moved `is_user_allowed()` to the {@see 'init'} hook.
	 *
	 * @param string $capability Capability needed to gain dashboard access.
	 * @param array  $settings RDA settings array.
	 */
	public function __construct( $capability, $settings ) {
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
	 * @return bool False if the current user lacks the requisite capbility. True otherwise.
	 */
	public function is_user_allowed() {
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
	public function lock_it_up() {
		add_action( 'admin_init', array( $this, 'dashboard_redirect' ) );
		add_action( 'admin_head', array( $this, 'hide_menus' ) );
		add_action( 'admin_bar_menu', array( $this, 'hide_toolbar_items' ), 999 );
	}

	/**
	 * Hides menus other than allowed admin pages.
	 *
	 * Note: It is up to third-party developers to handle capability checking for any allowed pages.
	 *
	 * @since 1.1
	 */
	public function hide_menus() {
		/** @global array $menu */
		global $menu;

		/** @global array $submenu */
		global $submenu;

		// Drop menu pages.
		if ( ! empty( $menu ) && is_array( $menu ) ) {
			// Gather menu IDs (minus allowed pages).
			foreach ( $menu as $index => $values ) {

				if ( isset( $values[2] ) && in_array( $values[2], $this->get_allowed_pages(), true ) ) {
					continue;
				}

				// Remove menu pages.
				remove_menu_page( $values[2] );
			}
		}

		// Drop submenu pages.
		if ( ! empty( $submenu ) && is_array( $submenu ) ) {
			// Gather submenu IDs (minus allowed pages).
			foreach ( $submenu as $parent => $positions ) {
				foreach ( $positions as $position => $entry ) {
					if ( isset( $entry[2] ) && in_array( $entry[2], $this->get_allowed_pages(), true ) ) {
						continue;
					}

					remove_submenu_page( $parent, $entry[2] );
				}

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

		/**
		 * Filters the URL to redirect disallowed users to.
		 *
		 * If the redirect URL passed to this hook is empty, the redirect will be skipped.
		 *
		 * Example to disable the redirect:
		 *
		 *     add_filter( 'rda_redirect_url', '__return_empty_string' );
		 *
		 * @since 1.2.0
		 *
		 * @param string             $url   URL to redirect disallowed users to.
		 * @param \RDA_Remove_Access $this  RDA_Remove_Access instance.
		 */
		$redirect_url = apply_filters( 'rda_redirect_url', $this->settings['redirect_url'], $this );

		if ( ( ! in_array( (string) $pagenow, $this->get_allowed_pages(), true ) || ! $this->settings['enable_profile'] )
		     && ! empty( $redirect_url )
		) {
			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Retrieves the list of pages restricted users can access in the admin.
	 *
	 * Note: It is up to third-party developers to handle capability checking for any allowed pages.
	 *
	 * @since 1.2
	 *
	 * @return array List of allowed pages.
	 */
	public function get_allowed_pages() {

		/**
		 * Filters the list of pages restricted users can access in the admin.
		 *
		 * Note: It is up to third-party developers to handle capability checking for any allowed pages.
		 *
		 * @since 1.2
		 *
		 * @param array $pages List of allowed pages.
		 */
		$allowed_pages = apply_filters( 'rda_allowed_pages', array( 'profile.php' ) );

		return array_merge( $allowed_pages, array( 'profile.php' ) );
	}

	/**
	 * Hides Toolbar items for disallowed users.
	 *
	 * @since 1.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Toolbar instance.
	 */
	public function hide_toolbar_items( $wp_admin_bar ) {
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

}
