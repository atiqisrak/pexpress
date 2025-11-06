<?php

/**
 * Admin menu registration
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin menus handler
 */
class PExpress_Admin_Menus
{

    /**
     * Register admin menus
     */
    public function register_menus()
    {
        // Check if user has any Polar Express role or manage_woocommerce
        $current_user = wp_get_current_user();
        $has_role = in_array('polar_hr', $current_user->roles) ||
            in_array('polar_delivery', $current_user->roles) ||
            in_array('polar_fridge', $current_user->roles) ||
            in_array('polar_distributor', $current_user->roles) ||
            in_array('polar_support', $current_user->roles) ||
            current_user_can('manage_woocommerce');

        if (!$has_role) {
            return;
        }

        // Main menu - visible to all Polar Express roles
        $main_capability = 'read'; // Basic read capability

        // Determine which dashboard to show based on role
        $main_page_callback = 'render_hr_dashboard';
        if (in_array('polar_delivery', $current_user->roles)) {
            $main_page_callback = 'render_delivery_dashboard';
        } elseif (in_array('polar_fridge', $current_user->roles)) {
            $main_page_callback = 'render_fridge_dashboard';
        } elseif (in_array('polar_distributor', $current_user->roles)) {
            $main_page_callback = 'render_distributor_dashboard';
        } elseif (in_array('polar_support', $current_user->roles)) {
            $main_page_callback = 'render_support_dashboard';
        }

        add_menu_page(
            __('Polar Express', 'pexpress'),
            __('Polar Express', 'pexpress'),
            $main_capability,
            'polar-express',
            array($this, $main_page_callback),
            'dashicons-clipboard',
            56
        );

        // HR Dashboard (only for HR and managers)
        if (in_array('polar_hr', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('HR Dashboard', 'pexpress'),
                __('HR Dashboard', 'pexpress'),
                'polar_hr',
                'polar-express',
                array($this, 'render_hr_dashboard')
            );
        }

        // Delivery Dashboard
        if (in_array('polar_delivery', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Delivery Dashboard', 'pexpress'),
                __('Delivery', 'pexpress'),
                'read',
                'polar-express-delivery',
                array($this, 'render_delivery_dashboard')
            );
        }

        // Fridge Dashboard
        if (in_array('polar_fridge', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Fridge Dashboard', 'pexpress'),
                __('Fridge', 'pexpress'),
                'read',
                'polar-express-fridge',
                array($this, 'render_fridge_dashboard')
            );
        }

        // Distributor Dashboard
        if (in_array('polar_distributor', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Distributor Dashboard', 'pexpress'),
                __('Distributor', 'pexpress'),
                'read',
                'polar-express-distributor',
                array($this, 'render_distributor_dashboard')
            );
        }

        // Support Dashboard
        if (in_array('polar_support', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Support Dashboard', 'pexpress'),
                __('Support', 'pexpress'),
                'read',
                'polar-express-support',
                array($this, 'render_support_dashboard')
            );
        }

        // Order Edit (for Support and HR)
        if (in_array('polar_support', $current_user->roles) || in_array('polar_hr', $current_user->roles) || current_user_can('edit_shop_orders')) {
            add_submenu_page(
                null, // Hidden from menu
                __('Edit Order', 'pexpress'),
                __('Edit Order', 'pexpress'),
                'edit_shop_orders',
                'polar-express-order-edit',
                array($this, 'render_order_edit_page')
            );
        }

        // Settings (HR and Shop Managers only)
        if (in_array('polar_hr', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Settings', 'pexpress'),
                __('Settings', 'pexpress'),
                'manage_woocommerce',
                'polar-express-settings',
                array($this, 'render_settings_page')
            );
        }

        // Setup Guideline (HR and Shop Managers only)
        if (in_array('polar_hr', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Setup Guideline', 'pexpress'),
                __('Setup Guideline', 'pexpress'),
                'manage_woocommerce',
                'polar-express-setup',
                array($this, 'render_setup_guideline_page')
            );
        }

        // Changelog (HR and Shop Managers only)
        if (in_array('polar_hr', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Changelog', 'pexpress'),
                __('Changelog', 'pexpress'),
                'manage_woocommerce',
                'polar-express-changelog',
                array($this, 'render_changelog_page')
            );
        }
    }

    /**
     * Render HR Dashboard page
     */
    public function render_hr_dashboard()
    {
        $dashboards = new PExpress_Admin_Dashboards();
        $dashboards->render_hr_dashboard();
    }

    /**
     * Render Delivery Dashboard page
     */
    public function render_delivery_dashboard()
    {
        $dashboards = new PExpress_Admin_Dashboards();
        $dashboards->render_delivery_dashboard();
    }

    /**
     * Render Fridge Dashboard page
     */
    public function render_fridge_dashboard()
    {
        $dashboards = new PExpress_Admin_Dashboards();
        $dashboards->render_fridge_dashboard();
    }

    /**
     * Render Distributor Dashboard page
     */
    public function render_distributor_dashboard()
    {
        $dashboards = new PExpress_Admin_Dashboards();
        $dashboards->render_distributor_dashboard();
    }

    /**
     * Render Support Dashboard page
     */
    public function render_support_dashboard()
    {
        $dashboards = new PExpress_Admin_Dashboards();
        $dashboards->render_support_dashboard();
    }

    /**
     * Render Settings page
     */
    public function render_settings_page()
    {
        $settings = new PExpress_Admin_Settings();
        $settings->render_settings_page();
    }

    /**
     * Render Setup Guideline page
     */
    public function render_setup_guideline_page()
    {
        $pages = new PExpress_Admin_Pages();
        $pages->render_setup_guideline_page();
    }

    /**
     * Render Changelog page
     */
    public function render_changelog_page()
    {
        $pages = new PExpress_Admin_Pages();
        $pages->render_changelog_page();
    }

    /**
     * Render Order Edit page
     */
    public function render_order_edit_page()
    {
        $order_manipulation = new PExpress_Admin_Order_Manipulation();
        $order_manipulation->render_order_edit_page();
    }
}
