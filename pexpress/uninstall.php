<?php
/**
 * Fired when the plugin is uninstalled
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load main plugin file to access functions
require_once plugin_dir_path(__FILE__) . 'roles.php';

// Remove custom roles
polar_remove_roles();

// Clean up options if needed
// delete_option('polar_sms_user');
// delete_option('polar_sms_pass');
// delete_option('polar_sms_sid');

