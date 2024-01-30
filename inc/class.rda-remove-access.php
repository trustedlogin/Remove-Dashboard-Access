<?php
/**
 * Remove Dashboard Access Class
 *
 * @since 1.0
 */

// Bail if called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

		if ( ! $menu || ! is_array( $menu ) ) {
			return;
		}

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

		if ( $this->is_allowed_page() ) {
			return;
		}

		if ( ( $pagenow && 'profile.php' !== $pagenow ) || ( defined( 'IS_PROFILE_PAGE' ) && ! IS_PROFILE_PAGE ) || ! $this->settings['enable_profile'] ) {
			wp_redirect( $this->settings['redirect_url'] );
			exit;
		}
	}

	/**
	 * Returns an array of admin pages that are allowed.
	 *
	 * @since 1.2
	 *
	 * @return array Allowlist of admin pages.
	 */
	private function get_allowlist() {

		$allowlist = array(
			'admin.php' => array(
				array(
					'page' => 'WFLS', // Wordfence Login Security 2FA
				),
			),
		);

		/**
		 * Filter the allowlist of admin pages.
		 * The function returns an associative array with $pagenow as the key and a nested array of key => value pairs
		 * where the key is the $_GET variable and the value is the allowed value.
		 *
		 * Example: To allow the Wordfence Login Security 2FA page, with a URL of admin.php?page=WFLS, the array would be:
		 *
		 *  array(
		 *     'admin.php' => array(
		 *         array(
		 *           'page' => 'WFLS',
		 *         ),
		 *     ),
		 *  );
		 * @param array $allowlist The allowlist of admin pages.
		 */
		$allowlist = apply_filters( 'rda_allowlist', $allowlist );

		return $allowlist;
	}

	/**
	 * Checks if the current page is allowed.
	 *
	 * @since 1.2
	 *
	 * @return bool True if the current page is in the allowlist, false otherwise.
	 */
	private function is_allowed_page() {
		global $pagenow;

		if ( empty( $pagenow ) ) {
			return false;
		}

		$allowlist = $this->get_allowlist();

		if ( ! array_key_exists( $pagenow, $allowlist ) ) {
			return false;
		}

		// Iterate over each set of allowed GET parameters for the current page.
		foreach ( $allowlist[ $pagenow ] as $allowed_params_set ) {
			if ( $this->is_params_set_allowed( $allowed_params_set ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if a set of parameters matches the current $_GET parameters.
	 *
	 * @since 1.2
	 *
	 * @param array $allowed_params_set A set of allowed GET parameters.
	 * @return bool True if the current $_GET parameters match the allowed set, false otherwise.
	 */
	private function is_params_set_allowed( $allowed_params_set ) {

		if ( ! is_array( $_GET ) || ! is_array( $allowed_params_set ) ) {
			return false;
		}

		// Check if the number of parameters in both arrays is the same. This prevents sub-pages from being allowed,
		// e.g. admin.php?page=example&subpage=secure-thing.
		if ( count( $_GET ) !== count( $allowed_params_set ) ) {
			return false;
		}

		foreach ( $allowed_params_set as $param_key => $param_value ) {
			if ( ! isset( $_GET[ $param_key ] ) || $_GET[ $param_key ] !== $param_value ) {
				return false;
			}
		}

		return true;
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
