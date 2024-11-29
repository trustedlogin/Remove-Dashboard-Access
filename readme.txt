=== Remove Dashboard Access ===
Contributors: TrustedLogin
Donate link: https://www.trustedlogin.com
Tags: dashboard, access, administration, login, restrict
Requires at least: 3.1.0
Tested up to: 6.7
Stable tag: 1.2.1
Requires PHP: 5.3

Disable Dashboard access for users of a specific role or capability. Disallowed users are redirected to a chosen URL. Get set up in seconds.

== Description ==

The easiest and safest way to restrict access to your WordPress site's Dashboard and administrative menus. Remove Dashboard Access is a lightweight plugin that automatically redirects users who shouldn't have access to the Dashboard to a custom URL of your choosing. Redirects can also be configured on a per-role/per-capability basis, allowing you to keep certain users out of the Dashboard, while retaining access for others.

* Limit Dashboard access to user roles:
    - Admins only
    - Admins + editors
    - Admins, editors, and authors
    - or restrict by specific user capability
* Choose your own redirect URL
* Optionally allow users to edit their profiles
* Display a message on the login screen so users know why they're being redirected

Blocking access to the Dashboard is a great way to prevent clients from breaking their sites, prevent users from seeing things they shouldn't, and to keep your site's backend more secure.

<strong>Allow only users with roles or capabilities:</strong>

You can restrict Dashboard access to Admins only, Editors or above, Authors or above, or by selecting a specific user capability.

<strong>Grant access to user profiles:</strong>

Optionally allow all users the ability to edit their profiles in the Dashboard. Users lacking the chosen capability won't be able to access any other sections of the Dashboard.

<strong>Show a custom login message:</strong>

* Supply a message to display on the login screen. Leaving this blank disables the message.

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

= How do I hide other plugins/themes' Toolbar menus? =

* Remove Dashboard Access removes some built-in WordPress Toolbar menus by default, but can be extended to hide menus from other plugins or themes via two filters: `rda_toolbar_nodes` (viewing from the admin), and `rda_frontend_toolbar_nodes` (viewing from the front-end).

= How do I find the menu (node) id? =

* In the HTML page source, look for the `<li>` container for the menu node you're targeting. It should take the form of `<li id="wp-admin-bar-SOMETHING">`
* In `<li id="wp-admin-bar-SOMETHING">`, you want the "SOMETHING" part.

= How can I allow access to specific pages of the Dashboard? =

The function returns an associative array with `$pagenow` as the key and a nested array of key => value pairs where the key is the `$_GET` parameter and the value is the allowed value.

Example: If you want to allow a URL of `admin.php?page=EXAMPLE`, there are three parts to know:

- The `$pagenow` global value (`tools.php` in this case)
- The `$_GET` key (`page` in this case)
- The `$_GET value (`EXAMPLE in this case)

Here is how we would add that URL to the allowlist:

`
/**
 * Allow users to access a page with a URL of tools.php?page=EXAMPLE
 *
 * @param array $pages Allowed Dashboard pages.
 * @return array Filtered allowed Dashboard pages.
 */
function wpdocs_allow_example_dashboard_page( $pages ) {

    // If the $pages array doesn't contain the 'admin.php' key, add it.
    if ( ! isset( $pages['tools.php'] ) ) {
        $pages['tools.php'] = array();
    }

    // Now add `?page=EXAMPLE` combination to the allowed parameter set for that page.
    $pages['tools.php'][] = array(
        'page' => 'EXAMPLE'
    );

    return $pages;
}

add_filter( 'rda_allowlist', 'wpdocs_allow_example_dashboard_page' );
`

= How can I filter the disallowed Toolbar nodes on the front-end? =

`
/**
 * Filter hidden Toolbar menus on the front-end.
 *
 * @param array $ids Toolbar menu IDs.
 * @return array Filtered front-end Toolbar menu IDs.
 */
function wpdocs_hide_some_toolbar_menu( $ids ) {
	$ids[] = 'SOMETHING';
	return $ids;
}
add_filter( 'rda_frontend_toolbar_nodes', 'wpdocs_hide_some_toolbar_menu' );

<strong>Common plugin Toolbar menus and their ids:</strong>

* <a href="https://wordpress.org/extend/plugins/jetpack/">Jetpack by WordPress.com</a> (notifications) – 'notes'
* <a href="https://wordpress.org/extend/plugins/wordpress-seo/">WordPress SEO by Yoast</a> – 'wpseo-menu'
* <a href="https://wordpress.org/extend/plugins/w3-total-cache/">W3 Total Cache</a> – 'w3tc'

= How do I enable Debug Mode? =

To view debugging information on the Settings > Reading screen, visit:
`
example.com/options-general.php?page=dashboard-access&rda_debug=1
`
= Can I contribute to the plugin? =

Yes! This plugin is in active development <a href="https://github.com/trustedlogin/Remove-Dashboard-Access" target="_new">on GitHub</a>. Pull requests are welcome!

= Is the plugin GDPR compliant? =

Yes. The plugin does not collect any personal data, nor does it set any cookies.

== Screenshots ==

1. The Dashboard Access Controls settings in the Settings > Dashboard Access screen.
2. Allow users to access their profile settings (only).
3. Optional login message.

== Changelog ==

= 1.2.1 on November 29, 2024 =

* Fixed: Compatibility with WordPress 6.7 (there was a warning that translations were being loaded too soon)
* Tweak: Sanitized admin menu URL

= 1.2 on January 29, 2024 =

* Confirmed compatibility with WordPress 6.4.2
* New: Added a new filter, `rda_allowlist`, to configure pages that should be accessible to all users, regardless of their capabilities or roles (see FAQ for usage)
* Improved: Added a description that clarifies that the Login Message is only displayed on the WordPress "Log In" screen
* Improved: The User Profile Access text is now a proper label for the checkbox
* Fixed: Allow access to the Wordfence 2FA configuration page ([#33](https://github.com/trustedlogin/Remove-Dashboard-Access/issues/33))
* Fixed: Text domain not properly set for translations (thanks [@fierevere](https://wordpress.org/support/topic/i18n-problem-textdomain-is-not-sethello/))
* Tweak: Prevent directly accessing PHP files by checking for `ABSPATH` ([#26](https://github.com/trustedlogin/Remove-Dashboard-Access/issues/26))
* Tweak: Prevent browsing directories on poorly-configured servers by adding `index.php` files in plugin directories

= 1.1.4 & 1.1.5 on April 18, 2022 =

Remove Dashboard Access is now being maintained by [TrustedLogin](https://www.trustedlogin.com/2022/02/21/remove-dashboard-access/)! Remove Dashboard Access aligns with what we do at TrustedLogin: simply making WordPress more secure. Email any questions to [support@trustedlogin.com](mailto:support@trustedlogin.com).

* Fixed: Deactivating and activating the plugin will no longer overwrite plugin settings
* Fixed: Deprecated function `screen_icon()` warning
* Fixed: Issue when front-end editing of profiles when the `$pagenow` global is not defined ([#24](https://github.com/trustedlogin/Remove-Dashboard-Access/issues/24))
* Fixed: Potential `Invalid argument supplied for foreach()` PHP warning ([#22](https://github.com/trustedlogin/Remove-Dashboard-Access/pull/22))

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
