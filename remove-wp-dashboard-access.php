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

// Load options instance
if ( class_exists( 'RDA_Options' ) )
	$load = new RDA_Options;

// RDA_Remove_Access Class
require_once( dirname( __FILE__ ) . '/inc/class-rda-remove-access.php' );

// Run it
if ( class_exists( 'RDA_Remove_Access' ) )
	new RDA_Remove_Access( $load->capability(), $load->settings );


class RDA_Options {
	
	/**
	 * @var $settings rda-settings options array
	 *
	 * @since 1.0
	 */
	var $settings = array();

	/**
	 * Init
	 *
	 * @since 1.0
	 */
	function __construct() {
		load_plugin_textdomain( 'remove_dashboard_access', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		$this->settings = (array) get_option( 'rda-settings' );
		$this->hooks();
	}
	
	/**
	 * Action Hooks and Filters
	 *
	 * activate - Setup options array on activation.
	 * options_page - Adds the options page.
	 * options_setup - Register options pages settings sections and fields.
	 * access_switch_js - Prints jQuery script via admin_head-$suffix for the options page.
	 * settings_link - Adds a 'Settings' link to the plugin row links via plugin_action_links_$plugin.
	 *
	 * @since 1.0
	 */
	function hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		add_action( 'admin_menu', array( $this, 'options_page' ) );
		add_action( 'admin_init', array( $this, 'options_setup' ) );
		add_action( 'admin_head-settings_page_dashboard-access', array( $this, 'access_switch_js' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ) );
	}

	/**
	 * Activation Hook
	 *
	 * Setup options array on activation.
	 *
	 * @since 1.0
	 */
	function activate() {
		$options = array(
			'access_switch' => 'manage_options',
			'access_cap' => 'manage_options',
			'enable_profile' => 1,
			'redirect_url' => home_url()
		);
		update_option( 'rda-settings', $options );	
	}

	/**
	 * Options page: Remove Access
	 *
	 * @since 1.0
	 *
	 * @uses add_options_page() to add a submenu under 'Settings'
	 */
	function options_page() {		
		add_options_page( 
			__( 'Dashboard Access Settings', 'remove_dashboard_access' ),
			__( 'Dashboard Access', 'remove_dashboard_access' ),
			'manage_options',
			'dashboard-access',
			array( $this, 'options_page_cb' )
		);
	}

	/**
	 * Options page: callback
	 *
	 * Outputs the form for the 'Remove Access' submenu 
	 *
	 * @since 1.0
	 */
	function options_page_cb() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e( 'Dashboard Access Settings', 'remove_dashboard_access' ); ?></h2>
			<form action="options.php" method="POST" id="rda-options-form">
				<?php 
				settings_fields( 'rda-options' );
				do_settings_sections( 'dashboard-access' );
				submit_button();
				?>
			</form>
		</div><!-- .wrap -->
		<?php
	}

	/**
	 * Register settings and settings sections.
	 *
	 * @since 1.0
	 *
	 * @uses register_setting() Registers the 'rda-options' settings group
	 * @uses add_settings_section() Adds the settings sections
	 * @uses add_settings_filed() Adds the settings fields
	 */
	function options_setup() {
		register_setting( 'rda-options', 'rda-settings', array( $this, 'sanitize_options' ) );

		// Permissions
		add_settings_section( 'rda-permissions', __( 'Access Controls', 'remove_dashboard_access' ), array( $this, 'access_desc_cb' ), 'dashboard-access' );
		add_settings_field( 'access-switch', __( 'User Access:', 'remove_dashboard_access' ), array( $this, 'access_switch_cb' ), 'dashboard-access', 'rda-permissions' );
		add_settings_field( 'profile-enable', __( 'User Profile Access:', 'remove_dashboard_access' ), array( $this, 'profile_enable_cb' ), 'dashboard-access', 'rda-permissions' );

		// Redirect
		add_settings_section( 'rda-redirect', __( 'Redirection Settings', 'remove_dashboard_access' ), array( $this, 'redirect_desc_cb' ), 'dashboard-access' );
		add_settings_field( 'redirect-url', __( 'Redirect URL:', 'remove_dashboard_access' ), array( $this, 'url_redirect_cb' ), 'dashboard-access', 'rda-redirect' );
	}

	/**
	 * Access Controls Description
	 *
	 * @since 1.0
	 */
	function access_desc_cb() {
		_e( 'Dashboard access can be restricted to Administrators only (default) or users with a specific capability.', 'remove_dashboard_access' );
	}
	
	/**
	 * Capability-type radio switch display callback
	 *
	 * Displays the radio button switch for choosing which
	 * capability users need to access the Dashboard. Mimics
	 * 'Page on front' UI in options-reading.php for a more
	 * integrated feel.
	 *
	 * @since 1.0
	 *
	 * @uses checked() Activates the checked attribute on the selected option.
	 * @uses $this->caps_dropdown() Displays the capabilities dropdown paired with the second access switch radio option.
	 */
	function access_switch_cb() {
		$switch = esc_attr( $this->settings['access_switch'] );		
		?>
		<p><label>
			<input name="rda-settings[access_switch]" type="radio" value="manage_options" class="tag" <?php checked( 'manage_options', $switch ); ?> />
			<?php _e( 'Administrators only', 'remove_dashboard_access' ); ?>
		</label></p>
		<p><label>
			<input name="rda-settings[access_switch]" type="radio" value="capability" class="tag" <?php checked( 'capability', $switch ); ?> />
			<?php _e( 'Limit by capability:', 'remove_dashboard_access' ); ?>
		</label>
			<?php $this->output_caps_dropdown(); ?>
		</p>
		<p>
		<?php printf( __( 'You can find out more about specific %s in the Codex.', 'remove_dashboard_access' ),
			sprintf( '<a href="%1$s" target="_new">%2$s</a>',
				esc_url( 'http://codex.wordpress.org/Roles_and_Capabilities' ),
				esc_html( __( 'Roles and Capabilities', 'remove_dashboard_access' ) )
			)
		); ?>
		</p>
		<?php
	}

	/**
	 * Capability-type radio switch jQuery script
	 *
	 * When the 'Limit by capability' radio option is selected the script
	 * enables the capabilities drop-down. Default state is disabled.
	 *
	 * @since 1.0
	 */
	function access_switch_js() {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			var section = $('#rda-options-form'),
				capType = section.find('input:radio[value="capability"]'),
				selects = section.find('select'),
				check_disabled = function(){
					selects.prop( 'disabled', ! capType.prop('checked') );
				};
			check_disabled();
			section.find('input:radio').change(check_disabled);
		});
		</script>
		<?php
	}

	/**
	 * Capability-type switch drop-down
	 *
	 * @since 1.0
	 *
	 * @uses global $wp_roles to derive an array of capabilities.
	 */
	function output_caps_dropdown() {
		global $wp_roles;

 		$capabilities = array();
		foreach ( $wp_roles->role_objects as $key => $role ) {
			if ( is_array( $role->capabilities ) ) {
				foreach ( $role->capabilities as $cap => $grant )
					$capabilities[$cap] = $cap;
			}
		}

		// Gather legacy user levels
		$levels = array( 
			'level_0','level_1', 'level_2', 'level_3',
			'level_4', 'level_5', 'level_6', 'level_7', 
			'level_8', 'level_9', 'level_10'
		);

		// Remove levels from caps array
		$capabilities = array_diff( $capabilities, $levels );

		// Alphabetize for nicer display
		ksort( $capabilities );

		// Start <select> element, plus default first option
		print( '<select name="rda-settings[access_cap]"><option selected="selected" value="manage_options">--- Select One ---</option>' );

		// Build capabilities dropdown
		foreach ( $capabilities as $capability => $value ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), selected( $this->settings['access_cap'], $value ), esc_html( $capability ) );
		}
		print( '</select>' );
	}

	/**
	 * Enable profile access checkbox display callback
	 *
	 * @since 1.0
	 * 
	 * @uses checked() Outputs the checked attribute when the option is enabled.
	 */
	function profile_enable_cb() {
		printf( '<input name="rda-settings[enable_profile]" type="checkbox" value="1" class="code" %1$s/>%2$s',
			checked( esc_attr( $this->settings['enable_profile'] ), true, false ),
			/* Translators: The leading space is intentional to space the text away from the checkbox */
			__( ' Allow users to edit their profiles in the dashboard.', 'remove_dashboard_access' )
		);
	}

	/**
	 * Redirect Settings Title & Description
	 *
	 * @since 1.0
	 */
	function redirect_desc_cb() {
		printf( __( 'Users who lack the selected role or capability will be redirected to a URL you specify. Left blank, default is: <strong>%s</strong>', 'remove_dashboard_access' ), home_url() );
	}
	
	/**
	 * Redirect URL display callback
	 *
	 * Default value is home_url(). $this->sanitize_options() handles validation and escaping.
	 *
	 * @since 1.0
	 */
	function url_redirect_cb() {
		?>
		<p><label>
			<?php _e( 'Redirect users to:', 'remove_dashboard_access' ); ?>
			<input name="rda-settings[redirect_url]" class="regular-text" type="text" value="<?php echo esc_attr( $this->settings['redirect_url'] ); ?>" />
		</label></p>
		<?php
	}

	/**
	 * Sanitize options values for 'rda-settings'
	 *
	 * @since 1.0
	 *
	 * @param array $options Options array to sanitize and validate.
	 * @return array $options The sanitized options array values.
	 */
	function sanitize_options( $options ) {
		$options['access_switch'] = esc_attr( $options['access_switch'] );
		$options['enable_profile'] = ( isset( $options['enable_profile'] ) && true == $options['enable_profile'] ) ? true : false;
		if ( empty( $options['redirect_url'] ) )
			// If Redirect URL is empty, use the home_url()
			$options['redirect_url'] = home_url();
		elseif ( ! preg_match( '|^\S+://\S+\.\S+.+$|', $options['redirect_url'] ) )
			// Malformed URL, throw a validation error
			add_settings_error( 'rda-settings[redirect_url]', 'invalid-url', sprintf( __( 'Please enter a properly-formed URL. For example: %s', 'remove_dashboard_access' ), esc_url( home_url() ) ) );
		else
			$options['redirect_url'] = esc_url_raw( $options['redirect_url'] );
		return $options;
	}

	/**
	 * Required capability for Dashboard access
	 *
	 * @since 1.0
	 *
	 * @return string $this->settings['access_cap'] if isset, otherwise, 'manage_options' (filterable)
	 */
	function capability() {
		if ( isset( $this->settings['access_cap'] ) )
			return $this->settings['access_cap'];
		else
			return apply_filters( 'rda_default_access_cap', 'manage_options' );
	}

	/**
	 * Plugins list 'Settings' row link
	 *
	 * @since 1.0
	 *
	 * @param array $links Row links array to filter.
	 * @return array $links Filtered links array.
	 */
	function settings_link( $links ) {
		return array_merge( 
			array( 'settings' => sprintf( 
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( 'options-general.php?page=dashboard-access' ) ),
				esc_attr( __( 'Settings', 'remove_dashboard_access' ) )
			) ), $links
		);
	}

} // RDA_Options
