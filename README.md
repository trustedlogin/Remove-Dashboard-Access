# Remove Dashboard Access
##### (Remove Dashboard Access for Non-Admins)

* Contributors: DrewAPicture
* Donate link: http://www.werdswords.com
* Tags: dashboard, access, users, administration
* Requires at least: 3.1
* Tested up to: 4.0.0
* Stable tag: 1.1

This WordPress plugin limits user access to the dashboard based on whether users have a chosen capability or role. Disallowed users are redirected to a chosen URL.

#### Features:

* Limit Dashboard access to Administrators only, Admins + Editors, Admins + Editors + Authors, or limit by specific capability.
* Choose your own redirect URL
* Optionally allow user profile access
* Optionally display a message on the login screen
* See **Other Notes** for more info

A full list of capabilities and their associated roles can be found here: http://codex.wordpress.org/Roles_and_Capabilities

#### Contribute to RDA

Pull requests are welcome!

## Installation

1. Search 'Remove Dashboard Access' from the Install Plugins screen.
2. Install plugin, click Activate.

## Frequently Asked Questions

#### What happens to disallowed users who try to login to the Dashboard?

Users lacking the chosen capability or role will be redirected to the URL set in Settings > Dashboard Access.

#### Why haven't you added an option to disable the WordPress Toolbar?

The Toolbar contains certain important links (even for disallowed users) such as for accessing to the profile editor and/or logging out. Plus, there are many plugins out there for disabling the Toolbar if you really want to.

#### Can I disable the redirection/profile-editing controls without disabling the plugin?

No. Disable the plugin if you don't wish to leverage the functionality.

## Other Notes

#### Capabilities

* You can limit Dashboard access to Admins only, Editors or above, Authors or above, or by selecting a capability. More information on WordPress' default roles and capabilities can be found here: http://codex.wordpress.org/Roles_and_Capabilities

#### User Profile Access

* You can optionally allow all users the ability to edit their profiles in the Dashboard. Users lacking the chosen capability won't be able to access any other sections of the Dashboard.

#### Login Message

* Supply a message to display on the login screen. Leaving this blank disables the message.

#### Hiding other plugins/themes' Toolbar menus

This hides some built-in WordPress Toolbar menus by default, but can be extended to hide menus from other plugins or themes via two filters: `rda_toolbar_nodes`, and `rda_frontend_toolbar_nodes`.

##### How to find the menu (node) id:

* In the HTML page source, look for the `<li>` container for the menu node you're targeting. It should take the form of `<li id="wp-admin-bar-SOMETHING">`
* In `<li id="wp-admin-bar-SOMETHING">`, you want the "SOMETHING" part.
	
##### How to filter the disallowed Toolbar nodes on the front-end:

```php
/**
 * Filter hidden Toolbar menus on the front-end.
 *
 * @param array $ids Toolbar menu IDs.
 * @return array (maybe) filtered front-end Toolbar menu IDs.
 */
function hide_some_toolbar_menu( $ids ) {
	$ids[] = 'SOMETHING';
	return $ids;
}
add_filter( 'rda_frontend_toolbar_nodes', 'hide_some_toolbar_menu' );
```

##### Common plugin Toolbar menus and their ids:

| Plugin | Menu ID |
| ------ | ------- |
| [Jetpack by WordPress.com](http://wordpress.org/extend/plugins/jetpack/) (notifications) | 'notes |
| [WordPress SEO by Yoast](http://wordpress.org/extend/plugins/wordpress-seo/) | 'wpseo-menu' |
| [W3 Total Cache](http://wordpress.org/extend/plugins/w3-total-cache/) | 'w3tc' |

## Changelog

#### 1.1

Enhancements:
* Instantiate as a static instance for better modularity
* Move Dashboard Access Controls settings to Settings > Reading
* Add optional login message option
* Add better settings sanitization
* New Filter: `rda_default_caps_for_role` - Filter default roles for Admins, Editors, and Authors

Bug Fixes:
* Remove unnecessarily stringent URL mask on the redirect URL option

#### 1.0

* Complete rewrite!
* New: Limit dashboard access for Admins only or by capability
* New: Allow/disallow edit-profile access
* New: Choose your own redirect URL
* New Filter: `rda_default_access_cap` - Change default access capability
* New Filter: `rda_toolbar_nodes` - Filter which back-end Toolbar nodes are hidden
* New Filter: `rda_frontend_toolbar_nodes` - Filter which front-end Toolbar nodes are hidden

#### 0.4

* Refined DOING_AJAX check for logged-out users, props @nacin and @BoiteAWeb

#### 0.3

* Changed cap to manage_options, replaced PHP_SELF with DOING_AJAX

#### 0.2

* Replaced preg_match with admin-ajax test. Added compatibility with rewritten dashboard URLs.

#### 0.1

* Submitted to repository
