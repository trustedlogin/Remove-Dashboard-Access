<?php
/*
Plugin Name: Remove Dashboard Access for Non-Admins
Plugin URI: http://www.werdswords.com
Description: Removes Dashboard access for non-admin users
Version: 0.4
Author: DrewAPicture
Author URI: http://www.werdswords.com
License: GPLv2
*/

add_action('admin_init', 'no_mo_dashboard');
function no_mo_dashboard() {
  if (!current_user_can('manage_options') && !defined('DOING_AJAX') ) {
    wp_redirect(site_url()); exit;
  }
}

?>