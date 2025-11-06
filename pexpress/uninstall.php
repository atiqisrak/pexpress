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

// Perform uninstall actions here
// Delete options, custom tables, etc.

// Example: Delete plugin options
// delete_option('pexpress_options');

// Example: Drop custom tables
// global $wpdb;
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pexpress_table");

