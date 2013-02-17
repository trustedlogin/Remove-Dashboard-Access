=== Plugin Name ===
Contributors: DrewAPicture
Donate link: http://www.werdswords.com
Tags: dashboard, access, users, administration
Requires at least: 3.1
Tested up to: 3.5.1
Stable tag: 1.0

This plugin limits user access to the dashboard based on whether users have a chosen capability. Disallowed users are redirected to a chosen URL.

== Description ==

Remove Dashboard Access was completely rewritten for version 1.0!

New features include:

* Limit Dashboard access to Administrators only, or limit by specific capability.
* Allow/disallow user profile access
* Choose your own redirect URL
* (<a href="http://wordpress.org/extend/plugins/remove-dashboard-access-for-non-admins/other_notes/">more info</a>)

A full list of capabilities and their associated roles can be found here: http://codex.wordpress.org/Roles_and_Capabilities

<strong>Contribute to RDA</strong>

This plugin is in active development <a href="https://github.com/DrewAPicture/remove-dashboard-access" target="_new">on GitHub</a>. If you'd like to contribute, pull requests are welcome!

== Installation ==

1. Upload `remove-wp-dashboard-access.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What happens to disallowed users who try to login to the Dashboard? =

Users lacking the chosen capability or role will be redirected to the URL set in Settings > Dashboard Access.

== Other Notes ==

<strong>Capabilities</strong>

* In v1.0+, you can limit Dashboard access to Admins or by selecting a capability. More information on WordPress' default roles and capabilities can be found here: http://codex.wordpress.org/Roles_and_Capabilities

<strong>User Profile Access</strong>

* In v1.0+, you can allow or disallow the ability for all users to edit their profiles in the Dashboard. Users lacking the chosen capability won't be able to access any other sections of the Dashboard.

<strong>Hiding other plugins/themes' Toolbar menus</strong>

v1.0+ hides some built-in WordPress Toolbar menus by default, but can be extended to hide menus from other plugins or themes via two filters: `rda_toolbar_nodes`, and `rda_frontend_toolbar_nodes`.

How to find the menu (node) id:

* In the HTML page source, look for the `<li>` container for the menu node you're targeting. It should take the form of `<li id="wp-admin-bar-SOMETHING">`
* In `<li id="wp-admin-bar-SOMETHING">`, you want the "SOMETHING" part.
	
How to filter the disallowed Toolbar nodes on the front-end:
`
function hide_some_toolbar_menu( $ids ) {
	$ids[] = 'SOMETHING';
	return $ids;
}
add_filter( 'rda_frontend_toolbar_nodes', 'hide_some_toolbar_menu' );
`

Common plugin Toolbar menus and their ids:

* <a href="http://wordpress.org/extend/plugins/jetpack/">JetPack by WordPress.com</a> (Notifications) - 'notes'
* <a href="http://wordpress.org/extend/plugins/wordpress-seo/">WordPress SEO by Yoast</a> - 'wpseo-menu'
* <a href="http://wordpress.org/extend/plugins/w3-total-cache/">W3 Total Cache</a> - 'w3tc'

== Screenshots ==

1. The new 1.0 accesss options screen.

2. Allow users to access their profile settings (only).

== Changelog ==

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