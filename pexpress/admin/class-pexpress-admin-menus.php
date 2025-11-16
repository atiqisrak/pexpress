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

        // Always register the order edit page (hidden menu) so it's accessible
        // even if capabilities are granted via filters
        add_submenu_page(
            null, // Hidden from menu
            __('Edit Order', 'pexpress'),
            __('Edit Order', 'pexpress'),
            'read', // Use basic read capability, actual permissions checked in render method
            'polar-express-order-edit',
            array($this, 'render_order_edit_page')
        );

        if (!$has_role) {
            return;
        }

        // Main menu - visible to all Polar Express roles
        $main_capability = 'read'; // Basic read capability

        // Determine which dashboard to show based on role
        $main_page_callback = 'render_agency_dashboard';
        if (in_array('polar_delivery', $current_user->roles)) {
            $main_page_callback = 'render_hr_dashboard';
        } elseif (in_array('polar_fridge', $current_user->roles)) {
            $main_page_callback = 'render_fridge_dashboard';
        } elseif (in_array('polar_distributor', $current_user->roles)) {
            $main_page_callback = 'render_distributor_dashboard';
        } elseif (in_array('polar_support', $current_user->roles)) {
            $main_page_callback = 'render_support_dashboard';
        }

        // Set the main menu title and label based on role
        $main_menu_title = __('Polar Express', 'pexpress');
        $main_menu_label = __('Polar Express', 'pexpress');

        if (in_array('polar_hr', $current_user->roles) || current_user_can('manage_woocommerce')) {
            $main_menu_title = __('Agency Dashboard', 'pexpress');
            $main_menu_label = __('Agency Dashboard', 'pexpress');
        } elseif (in_array('polar_delivery', $current_user->roles)) {
            $main_menu_title = __('HR Dashboard', 'pexpress');
            $main_menu_label = __('HR Dashboard', 'pexpress');
        } elseif (in_array('polar_support', $current_user->roles)) {
            $main_menu_title = __('Support Portal', 'pexpress');
            $main_menu_label = __('Support Portal', 'pexpress');
        }

        add_menu_page(
            $main_menu_title,
            __('Polar Express', 'pexpress'),
            $main_capability,
            'polar-express',
            array($this, $main_page_callback),
            'dashicons-clipboard',
            56
        );

        // Add explicit submenu for Agency Dashboard so it appears in the submenu list
        if (in_array('polar_hr', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Agency Dashboard', 'pexpress'),
                __('Agency Dashboard', 'pexpress'),
                'read',
                'polar-express',
                array($this, 'render_agency_dashboard')
            );
        }

        // HR Dashboard (formerly Delivery) - Only show for HR users, not for Agency users
        if (in_array('polar_delivery', $current_user->roles) || (current_user_can('manage_woocommerce') && !in_array('polar_hr', $current_user->roles))) {
            add_submenu_page(
                'polar-express',
                __('HR Dashboard', 'pexpress'),
                __('HR Dashboard', 'pexpress'),
                'read',
                'polar-express-delivery',
                array($this, 'render_hr_dashboard')
            );
        }

        // Fridge Dashboard
        if (in_array('polar_fridge', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Fridge Dashboard', 'pexpress'),
                __('Fridge Dashboard', 'pexpress'),
                'read',
                'polar-express-fridge',
                array($this, 'render_fridge_dashboard')
            );
        }

        // Distributor Dashboard
        if (in_array('polar_distributor', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Distributor Fulfills', 'pexpress'),
                __('Distributor Fulfills', 'pexpress'),
                'read',
                'polar-express-distributor',
                array($this, 'render_distributor_dashboard')
            );
        }

        // Support Dashboard
        if (in_array('polar_support', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Support Portal', 'pexpress'),
                __('Support Portal', 'pexpress'),
                'read',
                'polar-express-support',
                array($this, 'render_support_dashboard')
            );
        }


        // Settings (HR and Shop Managers only)
        if (in_array('polar_hr', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Configuration', 'pexpress'),
                __('Configuration', 'pexpress'),
                'manage_woocommerce',
                'polar-express-settings',
                array($this, 'render_settings_page')
            );
        }

        // Setup Wizard (HR and Shop Managers only) - Show if setup not completed
        if ((in_array('polar_hr', $current_user->roles) || current_user_can('manage_woocommerce')) && !PExpress_Admin_Setup_Wizard::is_setup_completed()) {
            add_submenu_page(
                'polar-express',
                __('Setup Wizard', 'pexpress'),
                __('Setup Wizard', 'pexpress'),
                'manage_woocommerce',
                'polar-express-setup-wizard',
                array($this, 'render_setup_wizard_page')
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

        // Role Capabilities (HR and Shop Managers only)
        if (in_array('polar_hr', $current_user->roles) || current_user_can('manage_woocommerce')) {
            add_submenu_page(
                'polar-express',
                __('Role Capabilities', 'pexpress'),
                __('Role Capabilities', 'pexpress'),
                'manage_woocommerce',
                'polar-express-role-capabilities',
                array($this, 'render_role_capabilities_page')
            );
        }
    }

    /**
     * Render Agency Dashboard page
     */
    public function render_agency_dashboard()
    {
        $dashboards = new PExpress_Admin_Dashboards();
        $dashboards->render_agency_dashboard();
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

    /**
     * Render Setup Wizard page
     */
    public function render_setup_wizard_page()
    {
        $wizard = new PExpress_Admin_Setup_Wizard();
        $wizard->render_setup_wizard();
    }

    /**
     * Render Role Capabilities page
     */
    public function render_role_capabilities_page()
    {
        $capabilities = new PExpress_Role_Capabilities();
        $capabilities->render_role_capabilities_page();
    }
}
