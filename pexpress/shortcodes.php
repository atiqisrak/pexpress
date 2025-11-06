<?php

/**
 * Shortcode Definitions
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HR Dashboard Shortcode
 */
add_shortcode('polar_hr', 'polar_hr_dashboard_shortcode');
function polar_hr_dashboard_shortcode($atts)
{
    // Check capability
    if (!current_user_can('polar_hr')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar HR.', 'pexpress') . '</p>';
    }

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/hr-dashboard.php';
    return ob_get_clean();
}

/**
 * Delivery Person Dashboard Shortcode
 */
add_shortcode('polar_delivery', 'polar_delivery_dashboard_shortcode');
function polar_delivery_dashboard_shortcode($atts)
{
    // Check capability
    if (!current_user_can('polar_delivery')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Delivery.', 'pexpress') . '</p>';
    }

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/delivery-dashboard.php';
    return ob_get_clean();
}

/**
 * Fridge Provider Dashboard Shortcode
 */
add_shortcode('polar_fridge', 'polar_fridge_dashboard_shortcode');
function polar_fridge_dashboard_shortcode($atts)
{
    // Check capability
    if (!current_user_can('polar_fridge')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Fridge Provider.', 'pexpress') . '</p>';
    }

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/fridge-dashboard.php';
    return ob_get_clean();
}

/**
 * Distributor Dashboard Shortcode
 */
add_shortcode('polar_distributor', 'polar_distributor_dashboard_shortcode');
function polar_distributor_dashboard_shortcode($atts)
{
    // Check capability
    if (!current_user_can('polar_distributor')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Distributor.', 'pexpress') . '</p>';
    }

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/distributor-dashboard.php';
    return ob_get_clean();
}

/**
 * Support Dashboard Shortcode
 */
add_shortcode('polar_support', 'polar_support_dashboard_shortcode');
function polar_support_dashboard_shortcode($atts)
{
    // Check capability
    if (!current_user_can('polar_support')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Support.', 'pexpress') . '</p>';
    }

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/support-dashboard.php';
    return ob_get_clean();
}
