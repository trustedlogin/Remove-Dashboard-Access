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
	 * @uses $this->is_allowed_page() Checks if the current page is allowed.
	 *
	 * @return null Bail if the current user has the requisite capability.
	 */
	function is_user_allowed() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// The admin checkbox under Settings → Dashboard Access drives the
			// default; the rda_strict_ajax filter can still override it either
			// way (e.g. a site-specific mu-plugin forcing strict mode regardless
			// of the option, or vice versa).
			$default_strict = ! empty( $this->settings['lock_ajax'] );

			/**
			 * Filter whether the dashboard lock applies to AJAX requests.
			 *
			 * By default the plugin exempts `/wp-admin/admin-ajax.php` so individual
			 * AJAX handlers can do their own cap checks (this matches WP's broader
			 * convention). The "Also block AJAX" setting on the Dashboard Access
			 * settings page seeds the default for this filter; the filter still
			 * has the final word so code can override either way.
			 *
			 * @since 1.3.0
			 *
			 * @param bool   $strict     Whether to enforce the cap check on AJAX. Seeded by the
			 *                           "rda_lock_ajax" option (default false).
			 * @param string $capability The configured access capability.
			 */
			$strict_ajax = apply_filters( 'rda_strict_ajax', $default_strict, $this->capability );

			if ( ! $strict_ajax ) {
				return;
			}
		}

		if ( $this->is_allowed_page() ) {
			return;
		}

		if ( ! $this->capability ) {
			return;
		}

		if ( current_user_can( $this->capability ) ) {
			return;
		}

		$this->lock_it_up();
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
			$redirect_url = $this->settings['redirect_url'];

			// Add the configured redirect host to the safe-redirect allowlist so
			// `wp_safe_redirect()` honors admin-configured external destinations
			// (the plugin's documented behavior since 1.0). Hosts other than this
			// one still fall back to home_url() per WP core.
			$redirect_host = wp_parse_url( $redirect_url, PHP_URL_HOST );
			if ( $redirect_host ) {
				$filter = static function ( $hosts ) use ( $redirect_host ) {
					$hosts[] = $redirect_host;
					return $hosts;
				};
				add_filter( 'allowed_redirect_hosts', $filter );
				wp_safe_redirect( $redirect_url );
				remove_filter( 'allowed_redirect_hosts', $filter );
			} else {
				wp_safe_redirect( $redirect_url );
			}
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
			'admin-post.php' => array(),
		);

		/**
		 * Filter the allowlist of admin pages.
		 *
		 * Structure: associative array keyed by `$pagenow`. The value is a list of
		 * allowed `$_GET` param sets. A request matches when its `$_GET` is a
		 * superset of any one set in the list (so additional params like 2FA OTP
		 * codes do not break the allowlist). An empty array means "this page is
		 * allowed regardless of GET params."
		 *
		 * Example — allow Wordfence 2FA (`admin.php?page=WFLS`, plus any extra
		 * params like `&wfls-action=otp&otp=…`):
		 *
		 *  array(
		 *     'admin.php' => array(
		 *         array( 'page' => 'WFLS' ),
		 *     ),
		 *  );
		 *
		 * Example — allow `admin-post.php` with no constraints on GET params:
		 *
		 *  array(
		 *     'admin-post.php' => array(),
		 *  );
		 *
		 * @since 1.2
		 * @since 1.3.0 Matcher changed from exact-count to subset; empty page entry
		 *              now means "no GET constraint" instead of "never matches."
		 *
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

		// Empty page entry → page allowed with no GET param constraints.
		// (Previously this returned false because the inner foreach never ran.)
		if ( empty( $allowlist[ $pagenow ] ) ) {
			return $this->is_target_page_registered( $pagenow );
		}

		// Iterate over each set of allowed GET parameters for the current page.
		foreach ( $allowlist[ $pagenow ] as $allowed_params_set ) {
			if ( ! $this->is_params_set_allowed( $allowed_params_set ) ) {
				continue;
			}

			// Belt-and-suspenders: for `admin.php?page=<slug>` allowlist entries,
			// confirm the target submenu page is actually registered in WP. Without
			// this, the admin chrome renders for any allowlist key even when the
			// plugin that supplies that page (e.g. Wordfence) isn't active.
			if ( ! $this->is_target_page_registered( $pagenow ) ) {
				continue;
			}

			return true;
		}

		return false;
	}

	/**
	 * Verify that the requested admin page is actually registered with WordPress.
	 *
	 * For `admin.php?page=<slug>` requests, this calls `get_plugin_page_hook()` to
	 * confirm `<slug>` has been registered via `add_menu_page()` / `add_submenu_page()`.
	 * For other pagenows (admin-post.php, profile.php, etc.) we can't easily verify
	 * registration — those are WP-core scripts and we trust them.
	 *
	 * @since 1.3.0
	 *
	 * @param string $pagenow Current admin page.
	 * @return bool True if the page is a registered admin surface; false otherwise.
	 */
	private function is_target_page_registered( $pagenow ) {
		if ( 'admin.php' !== $pagenow ) {
			return true;
		}

		if ( empty( $_GET['page'] ) ) {
			return false;
		}

		$page_slug = wp_unslash( $_GET['page'] );
		if ( ! is_string( $page_slug ) ) {
			return false;
		}

		// `get_plugin_page_hook()` lives in wp-admin/includes/plugin.php, which
		// is NOT auto-loaded on the `init` hook (admin scaffolding only loads
		// once a wp-admin request actually reaches admin.php). Lazy-load so
		// the check works whichever hook calls it.
		if ( ! function_exists( 'get_plugin_page_hook' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$hook = get_plugin_page_hook( $page_slug, $pagenow );

		return null !== $hook;
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

		// Subset match: every key/value in the allowed set must appear in $_GET.
		// Additional params are permitted so legitimate sub-flows of an allowed
		// page (e.g. WFLS 2FA's `&wfls-action=otp&otp=…`) still match.
		// The is_target_page_registered() check in is_allowed_page() is what
		// prevents accidentally exposing the admin shell on a `?page=` slug
		// that no plugin actually registered.
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
