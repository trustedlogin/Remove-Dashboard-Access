<?php
/**
 * Remove Dashboard Access Class
 *
 * @since 1.0
 */

if ( ! class_exists( 'RDA_Remove_Access' ) ) {
class RDA_Remove_Access {

	/**
	 * @var $capability
	 *
	 * String with capability passed from RDA_Options{}
	 *
	 * @since 1.0
	 */
	var $capability;

	/**
	 * @var $settings 
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
	 *
	 * @param string $capability Capability passed from RDA_Options instance.
	 * @param array $settings Settings array passed from RDA_Options instance.
	 */
	function __construct( $capability, $settings ) {
		if ( ! $capability )
			return; // Bail
		else
			$this->capability = $capability;

		$this->settings = $settings;

		add_action( 'plugins_loaded', array( $this, 'is_user_allowed' ) );
	}

	/**
	 * Determine if user is allowed to access the Dashboard
	 *
	 * @since 1.0
	 *
	 * @uses current_user_can() Checks whether the current user has the specified capability.
	 * @return null Bail if the current user has the requisite capability.
	 */
	function is_user_allowed() {
		if ( $this->capability && ! current_user_can( $this->capability ) && ! defined( 'DOING_AJAX' ) )
			$this->bdth_hooks();
		else
			return; // Bail
	}

	/**
	 * "Batten down the hatches" Hooks
	 *
	 * dashboard_redirect - Handles redirecting disallowed users.
	 * hide_menus - Hides the admin menus with CSS (not ideal but will suffice).
	 * hide_toolbar_items - Hides various Toolbar items on front and back-end.
	 *
	 * @since 1.0
	 */
	function bdth_hooks() {
		add_action( 'admin_init', array( $this, 'dashboard_redirect' ) );
		add_action( 'admin_head', array( $this, 'hide_menus' ) );
		add_action( 'admin_bar_menu', array( $this, 'hide_toolbar_items' ), 999 );			
	}

	/**
	 * Dashboard Redirect
	 *
	 * @since 0.1
	 *
	 * @uses global $pagenow Used to determine the current page.
	 * @uses wp_redirect() Used to redirect disallowed users to chosen URL.
	 */
	function dashboard_redirect() {
		global $pagenow;
		if ( 'profile.php' != $pagenow || $this->settings['enable_profile'] != 1 ) {
			wp_redirect( $this->settings['redirect_url'] );
			exit;
		}
	}

	/**
	 * Hide Admin Menus
	 *
	 * @since 1.0
	 *
	 * @todo Determine why 'Tools' menu can't be easily unset from admin menu
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
	 * @uses apply_filters() to make front-end and back-end Toolbar node arrays filterable.
	 * @param global $wp_admin_bar For remove_node() method access.
	 */
	function hide_toolbar_items( $wp_admin_bar ) {
		$edit_profile = $this->settings['enable_profile'] == 0 ? 'edit-profile' : '';
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