=== Plugin Name ===
Contributors: DrewAPicture
Donate link: http://www.werdswords.com
Tags: dashboard, access, users, administration
Requires at least: 3.1.0
Tested up to: 4.1.1
Stable tag: 1.1.3

Allows you to disable Dashboard access for users of a specific role or capability. Disallowed users are redirected to a chosen URL.

== Description ==

* Limit Dashboard access to admins only, admins + editors, admins + editors + authors, or limit by specific capability.
* Choose your own redirect URL
* Optionally allow user profile access
* Optionally display a message on the login screen
* (<a href="http://wordpress.org/extend/plugins/remove-dashboard-access-for-non-admins/other_notes/">more info</a>)

<strong>Contribute to RDA</strong>

This plugin is in active development <a href="https://github.com/DrewAPicture/remove-dashboard-access" target="_new">on GitHub</a>. Pull requests are welcome!

== Installation ==

1. Search 'Remove Dashboard Access' from the Install Plugins screen.
2. Install plugin, click Activate.

== Frequently Asked Questions ==

= What happens to disallowed users who try to access to the Dashboard? =

Users lacking the chosen capability or role(s) will be redirected to the URL set in Settings > Dashboard Access.

= Why haven't you added an option to disable the WordPress Toolbar? =

The Toolbar contains certain important links (even for disallowed users) such as for accessing to the profile editor and/or logging out. Plus, there are many plugins out there for disabling the Toolbar if you really want to.

= Can I disable the redirection/profile-editing controls without disabling the plugin? =

No. Disable the plugin if you don't wish to leverage the functionality.

== Other Notes ==

<strong>Capabilities:</strong>

* You can limit Dashboard access to Admins only, Editors or above, Authors or above, or by selecting a capability. More information on WordPress' default roles and capabilities can be found here: http://codex.wordpress.org/Roles_and_Capabilities

<strong>User Profile Access:</strong>

* You can optionally allow all users the ability to edit their profiles in the Dashboard. Users lacking the chosen capability won't be able to access any other sections of the Dashboard.

<strong>Login Message:</strong>

* Supply a message to display on the login screen. Leaving this blank disables the message.

<strong>Hiding other plugins/themes' Toolbar menus:</strong>

* Remove Dashboard Access removes some built-in WordPress Toolbar menus by default, but can be extended to hide menus from other plugins or themes via two filters: `rda_toolbar_nodes` (viewing from the admin), and `rda_frontend_toolbar_nodes` (viewing from the front-end).

<strong>How to find the menu (node) id:</strong>

* In the HTML page source, look for the `<li>` container for the menu node you're targeting. It should take the form of `<li id="wp-admin-bar-SOMETHING">`
* In `<li id="wp-admin-bar-SOMETHING">`, you want the "SOMETHING" part.
	
<strong>How to filter the disallowed Toolbar nodes on the front-end:</strong>

`
/**
 * Filter hidden Toolbar menus on the front-end.
 *
 * @param array $ids Toolbar menu IDs.
 * @return array (maybe) filtered front-end Toolbar menu IDs.
 */
function wpdocs_hide_some_toolbar_menu( $ids ) {
	$ids[] = 'SOMETHING';
	return $ids;
}
add_filter( 'rda_frontend_toolbar_nodes', 'wpdocs_hide_some_toolbar_menu' );
`

<strong>Common plugin Toolbar menus and their ids:</strong>

* <a href="http://wordpress.org/extend/plugins/jetpack/">Jetpack by WordPress.com</a> (notifications) – 'notes'
* <a href="http://wordpress.org/extend/plugins/wordpress-seo/">WordPress SEO by Yoast</a> – 'wpseo-menu'
* <a href="http://wordpress.org/extend/plugins/w3-total-cache/">W3 Total Cache</a> – 'w3tc'

<strong>Debug Mode</strong>

To view debugging information on the Settings > Reading screen, visit:
`
example.com/options-general.php?page=dashboard-access&rda_debug=1
`

== Screenshots ==

1. The Dashboard Access Controls settings in the Settings > Dashboard Access screen.
2. Allow users to access their profile settings (only).
3. Optional login message.

== Changelog ==

= 1.1.3 =

* Fixed a compatibility issue with bbPress and the media grid view.

= 1.1.2 =

* Bump tested-up-to to 4.1.0
* Miscellaneous readme changes.

= 1.1.1 =

Bug Fix:

* Move options back to Settings > Dashboard Access screen to resolve conflict with page_on_front UI.

= 1.1 =

Enhancements:

* Instantiate as a static instance for better modularity
* Move Dashboard Access Controls settings to Settings > Dashboard Access
* Add optional login message option
* Add better settings sanitization
* New Filter: `rda_default_caps_for_role` - Filter default roles for Admins, Editors, and Authors
* New Debug Mode

Bug Fixes:

* Remove unnecessarily stringent URL mask on the redirect URL option

= 1.0 =

* Complete rewrite!
* New: Limit dashboard access for Admins only or by capability
* New: Allow/disallow edit-profile access
* New: Choose your own redirect URL
* New Filter: `rda_default_access_cap` - Change default access capability
* New Filter: `rda_toolbar_nodes` - Filter which back-end Toolbar nodes are hidden
* New Filter: `rda_frontend_toolbar_nodes` - Filter which front-end Toolbar nodes are hidden

= 0.4 = 

* Refined DOING_AJAX check for logged-out users, props @nacin and @BoiteAWeb

= 0.3 =

* Changed cap to manage_options, replaced PHP_SELF with DOING_AJAX

= 0.2 =

* Replaced preg_match with admin-ajax test. Added compatibility with rewritten dashboard URLs.

= 0.1 =

* Submitted to repository

== Upgrade Notice ==

= 0.4 = 

* Refined DOING_AJAX check for logged-out users

= 0.3 =

* Improved function.

= 0.2 =

* No additional files were added.

= 0.1 =

* Initial submission
