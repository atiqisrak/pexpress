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
    if (!current_user_can('polar_hr') && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar HR.', 'pexpress') . '</p>';
    }

    // Get orders needing assignment
    $pending_orders = wc_get_orders(array(
        'status' => 'processing',
        'limit' => -1,
        'meta_key' => '_polar_needs_assignment',
        'meta_value' => 'yes',
    ));

    // Get all delivery, fridge, and distributor users
    $delivery_users = get_users(array('role' => 'polar_delivery'));
    $fridge_users = get_users(array('role' => 'polar_fridge'));
    $distributor_users = get_users(array('role' => 'polar_distributor'));

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
    $current_user = wp_get_current_user();
    if (!in_array('polar_delivery', $current_user->roles) && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Delivery.', 'pexpress') . '</p>';
    }

    $user_id = get_current_user_id();

    // Get orders assigned to this delivery person
    $assigned_orders = PExpress_Core::get_assigned_orders($user_id, 'delivery');

    // Get orders by status
    $out_orders = array();
    $delivered_orders = array();

    foreach ($assigned_orders as $order) {
        if ($order->get_status() === 'wc-polar-out') {
            $out_orders[] = $order;
        } elseif ($order->get_status() === 'wc-polar-delivered') {
            $delivered_orders[] = $order;
        }
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
    $current_user = wp_get_current_user();
    if (!in_array('polar_fridge', $current_user->roles) && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Fridge Provider.', 'pexpress') . '</p>';
    }

    $user_id = get_current_user_id();

    // Get orders assigned to this fridge provider
    $assigned_orders = PExpress_Core::get_assigned_orders($user_id, 'fridge');

    // Get orders by status
    $collected_orders = array();
    $return_pending = array();

    foreach ($assigned_orders as $order) {
        $return_date = PExpress_Core::get_order_meta($order->get_id(), '_polar_fridge_return_date');
        if ($order->get_status() === 'wc-polar-fridge-back') {
            $collected_orders[] = $order;
        } else {
            $return_pending[] = $order;
        }
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
    $current_user = wp_get_current_user();
    if (!in_array('polar_distributor', $current_user->roles) && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Distributor.', 'pexpress') . '</p>';
    }

    $user_id = get_current_user_id();

    // Get orders assigned to this distributor
    $assigned_orders = PExpress_Core::get_assigned_orders($user_id, 'distributor');

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
    $current_user = wp_get_current_user();
    if (!in_array('polar_support', $current_user->roles) && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Support.', 'pexpress') . '</p>';
    }

    // Get recent orders
    $recent_orders = wc_get_orders(array(
        'status' => 'any',
        'limit' => 50,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/support-dashboard.php';
    return ob_get_clean();
}
