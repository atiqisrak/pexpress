<?php

/**
 * Admin dashboard rendering
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin dashboards handler
 */
class PExpress_Admin_Dashboards
{

    /**
     * Render HR Dashboard page
     */
    public function render_hr_dashboard()
    {
        if (!current_user_can('polar_hr') && !current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
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

        include PEXPRESS_PLUGIN_DIR . 'templates/hr-dashboard.php';
    }

    /**
     * Render Delivery Dashboard page
     */
    public function render_delivery_dashboard()
    {
        $current_user = wp_get_current_user();
        if (!in_array('polar_delivery', $current_user->roles) && !current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
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

        include PEXPRESS_PLUGIN_DIR . 'templates/delivery-dashboard.php';
    }

    /**
     * Render Fridge Dashboard page
     */
    public function render_fridge_dashboard()
    {
        $current_user = wp_get_current_user();
        if (!in_array('polar_fridge', $current_user->roles) && !current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
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

        include PEXPRESS_PLUGIN_DIR . 'templates/fridge-dashboard.php';
    }

    /**
     * Render Distributor Dashboard page
     */
    public function render_distributor_dashboard()
    {
        $current_user = wp_get_current_user();
        if (!in_array('polar_distributor', $current_user->roles) && !current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
        }

        $user_id = get_current_user_id();

        // Get orders assigned to this distributor
        $assigned_orders = PExpress_Core::get_assigned_orders($user_id, 'distributor');

        include PEXPRESS_PLUGIN_DIR . 'templates/distributor-dashboard.php';
    }

    /**
     * Render Support Dashboard page
     */
    public function render_support_dashboard()
    {
        $current_user = wp_get_current_user();
        if (!in_array('polar_support', $current_user->roles) && !current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
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

        include PEXPRESS_PLUGIN_DIR . 'templates/support-dashboard.php';
    }
}
