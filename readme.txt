=== Plugin Name ===
Contributors: DrewAPicture
Donate link: http://www.werdswords.com
Tags: dashboard, access, users, administration
Requires at least: 3.0
Tested up to: 3.4.2
Stable tag: 0.4

This plugin completely removes non-admin user access to the Dashboard. Non-admins are automatically redirected the site's homepage.

== Description ==

This plugin uses the 'manage_options' capability to check whether users are administrators or not. Users lacking this capability will be automatically redirected to the site's homepage.

A full list of capabilities and their associated roles can be found here: http://codex.wordpress.org/Roles_and_Capabilities

TODO: Provide options to choose your own capability type.

== Installation ==

1. Upload `remove-wp-dashboard-access.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What happens to non-admin users who try to login to the Dashboard? =

Non-admin users will be automatically redirected to the site's homepage

== Changelog ==

= 0.4 = Refined DOING_AJAX check for logged-out users, props @nacin and @BoiteAWeb

= 0.3 = Changed cap to manage_options, replaced PHP_SELF with DOING_AJAX

= 0.2 = Replaced preg_match with admin-ajax test. Added compatibility with rewritten dashboard URLs.

= 0.1 = Submitted to repository

== Upgrade Notice ==

= 0.4 = Refined DOING_AJAX check for logged-out users

= 0.3 = Improved function.

= 0.2 = No additional files were added.

= 0.1 = Initial submission

== Screenshots ==

1. No screenshots.
