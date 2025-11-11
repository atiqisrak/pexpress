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
 * Agency Dashboard Shortcode (formerly HR)
 */
add_shortcode('polar_hr', 'polar_agency_dashboard_shortcode');
function polar_agency_dashboard_shortcode($atts)
{
    // Check role
    $current_user = wp_get_current_user();
    if (!in_array('polar_hr', $current_user->roles) && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Agency.', 'pexpress') . '</p>';
    }

    // Get orders needing assignment
    $pending_orders = wc_get_orders(array(
        'status' => 'processing',
        'limit' => -1,
        'meta_key' => '_polar_needs_assignment',
        'meta_value' => 'yes',
    ));

    // Get all HR (formerly delivery), fridge, and distributor users
    $hr_users = get_users(array('role' => 'polar_delivery'));
    $fridge_users = get_users(array('role' => 'polar_fridge'));
    $distributor_users = get_users(array('role' => 'polar_distributor'));

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/hr-dashboard.php';
    return ob_get_clean();
}

/**
 * HR Dashboard Shortcode (formerly Delivery)
 */
add_shortcode('polar_delivery', 'polar_hr_dashboard_shortcode');
function polar_hr_dashboard_shortcode($atts)
{
    $current_user = wp_get_current_user();
    if (!in_array('polar_delivery', $current_user->roles) && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar HR.', 'pexpress') . '</p>';
    }

    $user_id = get_current_user_id();

    // Get orders assigned to this HR person
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

    // Ensure $recent_orders is always an array
    if (!is_array($recent_orders)) {
        $recent_orders = array();
    }

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/support-dashboard.php';
    return ob_get_clean();
}

/**
 * Order Tracking Shortcode for Customers
 */
add_shortcode('polar_order_tracking', 'polar_order_tracking_shortcode');
function polar_order_tracking_shortcode($atts)
{
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'order_id' => 0,
    ), $atts, 'polar_order_tracking');

    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p>' . esc_html__('Please log in to view your order tracking.', 'pexpress') . '</p>';
    }

    $current_user = wp_get_current_user();
    $order_id = 0;

    // Get order ID from attribute, URL parameter, or current user's orders
    if (!empty($atts['order_id'])) {
        $order_id = absint($atts['order_id']);
    } elseif (isset($_GET['order_id'])) {
        $order_id = absint($_GET['order_id']);
    } else {
        // Get the most recent order for the current user
        $customer_orders = wc_get_orders(array(
            'customer_id' => $current_user->ID,
            'status' => 'any',
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        if (!empty($customer_orders)) {
            $order_id = $customer_orders[0]->get_id();
        }
    }

    if (!$order_id) {
        return '<p>' . esc_html__('No order found. Please provide an order ID.', 'pexpress') . '</p>';
    }

    // Get order
    $order = wc_get_order($order_id);
    if (!$order) {
        return '<p>' . esc_html__('Order not found.', 'pexpress') . '</p>';
    }

    // Check if user owns this order (unless admin)
    if (!current_user_can('manage_woocommerce')) {
        $customer_id = $order->get_customer_id();
        if ($customer_id != $current_user->ID) {
            return '<p>' . esc_html__('Access denied. This order does not belong to you.', 'pexpress') . '</p>';
        }
    }

    // Get role statuses
    $hr_status = PExpress_Core::get_role_status($order_id, 'agency');
    $delivery_status = PExpress_Core::get_role_status($order_id, 'delivery');
    $fridge_status = PExpress_Core::get_role_status($order_id, 'fridge');
    $distributor_status = PExpress_Core::get_role_status($order_id, 'distributor');

    // Get assigned users
    $delivery_user_id = PExpress_Core::get_delivery_user_id($order_id);
    $fridge_user_id = PExpress_Core::get_fridge_user_id($order_id);
    $distributor_user_id = PExpress_Core::get_distributor_user_id($order_id);

    // Get user names
    $delivery_user_name = $delivery_user_id ? get_userdata($delivery_user_id)->display_name : '';
    $fridge_user_name = $fridge_user_id ? get_userdata($fridge_user_id)->display_name : '';
    $distributor_user_name = $distributor_user_id ? get_userdata($distributor_user_id)->display_name : '';

    // Enqueue assets
    wp_enqueue_style(
        'polar-order-tracking',
        PEXPRESS_PLUGIN_URL . 'assets/css/polar-order-tracking.css',
        array(),
        PEXPRESS_VERSION
    );

    wp_enqueue_script(
        'polar-order-tracking',
        PEXPRESS_PLUGIN_URL . 'assets/js/polar-order-tracking.js',
        array('jquery', 'heartbeat'),
        PEXPRESS_VERSION,
        true
    );

    wp_localize_script(
        'polar-order-tracking',
        'polarOrderTracking',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('polar_order_tracking_nonce'),
            'orderId' => $order_id,
        )
    );

    // Make variables available to template
    $order_id = $order_id;
    $order = $order;
    $hr_status = $hr_status;
    $delivery_status = $delivery_status;
    $fridge_status = $fridge_status;
    $distributor_status = $distributor_status;
    $delivery_user_name = $delivery_user_name;
    $fridge_user_name = $fridge_user_name;
    $distributor_user_name = $distributor_user_name;

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/order-tracking.php';
    return ob_get_clean();
}
