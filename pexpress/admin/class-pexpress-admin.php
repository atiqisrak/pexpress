<?php

/**
 * Admin functionality
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class PExpress_Admin
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize admin functionality
     */
    private function init()
    {
        add_action('admin_menu', array($this, 'register_menus'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_post_pexpress_assign_role', array($this, 'handle_role_assignment'));
        add_action('admin_post_pexpress_add_user_to_role', array($this, 'handle_add_user_to_role'));
        add_action('admin_post_pexpress_remove_user_from_role', array($this, 'handle_remove_user_from_role'));
        add_action('admin_notices', array($this, 'show_role_assignment_notices'));
        add_action('wp_ajax_pexpress_get_users_for_role', array($this, 'ajax_get_users_for_role'));
    }

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
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('pexpress_settings_group', 'pexpress_options');

        // SMS Settings Section
        add_settings_section(
            'pexpress_sms_section',
            __('SMS Configuration', 'pexpress'),
            array($this, 'sms_section_callback'),
            'polar-express-settings'
        );

        add_settings_field(
            'pexpress_enable_sms',
            __('Enable SMS Notifications', 'pexpress'),
            array($this, 'render_checkbox_field'),
            'polar-express-settings',
            'pexpress_sms_section',
            array(
                'label_for' => 'pexpress_enable_sms',
                'option_key' => 'enable_sms',
                'description' => __('Enable SMS notifications for order assignments and status updates', 'pexpress')
            )
        );

        add_settings_field(
            'pexpress_sms_user',
            __('SMS API Username', 'pexpress'),
            array($this, 'render_text_field'),
            'polar-express-settings',
            'pexpress_sms_section',
            array(
                'label_for' => 'pexpress_sms_user',
                'option_key' => 'sms_user',
                'description' => __('Your SSLCommerz SMS API username', 'pexpress')
            )
        );

        add_settings_field(
            'pexpress_sms_pass',
            __('SMS API Password', 'pexpress'),
            array($this, 'render_password_field'),
            'polar-express-settings',
            'pexpress_sms_section',
            array(
                'label_for' => 'pexpress_sms_pass',
                'option_key' => 'sms_pass',
                'description' => __('Your SSLCommerz SMS API password', 'pexpress')
            )
        );

        add_settings_field(
            'pexpress_sms_sid',
            __('SMS SID', 'pexpress'),
            array($this, 'render_text_field'),
            'polar-express-settings',
            'pexpress_sms_section',
            array(
                'label_for' => 'pexpress_sms_sid',
                'option_key' => 'sms_sid',
                'description' => __('SMS Sender ID (e.g., POLARICE)', 'pexpress')
            )
        );

        // General Settings Section
        add_settings_section(
            'pexpress_general_section',
            __('General Settings', 'pexpress'),
            array($this, 'general_section_callback'),
            'polar-express-settings'
        );

        add_settings_field(
            'pexpress_heartbeat_interval',
            __('Heartbeat Interval (seconds)', 'pexpress'),
            array($this, 'render_number_field'),
            'polar-express-settings',
            'pexpress_general_section',
            array(
                'label_for' => 'pexpress_heartbeat_interval',
                'option_key' => 'heartbeat_interval',
                'description' => __('How often to check for updates (default: 15 seconds)', 'pexpress'),
                'default' => 15,
                'min' => 5,
                'max' => 60
            )
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on Polar Express pages
        if (strpos($hook, 'polar-express') === false) {
            return;
        }

        wp_enqueue_style(
            'pexpress-admin',
            PEXPRESS_PLUGIN_URL . 'assets/css/polar.css',
            array(),
            PEXPRESS_VERSION
        );

        wp_enqueue_script(
            'pexpress-admin',
            PEXPRESS_PLUGIN_URL . 'assets/js/polar.js',
            array('jquery', 'heartbeat'),
            PEXPRESS_VERSION,
            true
        );

        wp_localize_script(
            'pexpress-admin',
            'polarExpress',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('polar_express_nonce'),
                'heartbeatInterval' => get_option('pexpress_options')['heartbeat_interval'] ?? 15
            )
        );
    }

    /**
     * SMS section callback
     */
    public function sms_section_callback()
    {
        echo '<p>' . esc_html__('Configure SMS notifications for order assignments and status updates.', 'pexpress') . '</p>';
    }

    /**
     * General section callback
     */
    public function general_section_callback()
    {
        echo '<p>' . esc_html__('General plugin settings and configuration.', 'pexpress') . '</p>';
    }

    /**
     * Render text field
     */
    public function render_text_field($args)
    {
        $options = get_option('pexpress_options', array());
        $value = isset($options[$args['option_key']]) ? esc_attr($options[$args['option_key']]) : '';
        echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="pexpress_options[' . esc_attr($args['option_key']) . ']" value="' . $value . '" class="regular-text" />';
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Render password field
     */
    public function render_password_field($args)
    {
        $options = get_option('pexpress_options', array());
        $value = isset($options[$args['option_key']]) ? esc_attr($options[$args['option_key']]) : '';
        echo '<input type="password" id="' . esc_attr($args['label_for']) . '" name="pexpress_options[' . esc_attr($args['option_key']) . ']" value="' . $value . '" class="regular-text" />';
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Render number field
     */
    public function render_number_field($args)
    {
        $options = get_option('pexpress_options', array());
        $value = isset($options[$args['option_key']]) ? intval($options[$args['option_key']]) : ($args['default'] ?? 15);
        $min = isset($args['min']) ? $args['min'] : 1;
        $max = isset($args['max']) ? $args['max'] : 100;
        echo '<input type="number" id="' . esc_attr($args['label_for']) . '" name="pexpress_options[' . esc_attr($args['option_key']) . ']" value="' . $value . '" min="' . $min . '" max="' . $max . '" class="small-text" />';
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Checkbox field renderer
     */
    public function render_checkbox_field($args)
    {
        $options = get_option('pexpress_options', array());
        $checked = !empty($options[$args['option_key']]) ? 'checked' : '';
        echo '<input type="checkbox" id="' . esc_attr($args['label_for']) . '" name="pexpress_options[' . esc_attr($args['option_key']) . ']" value="1" ' . $checked . ' />';
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

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

        include PEXPRESS_PLUGIN_DIR . 'templates/support-dashboard.php';
    }

    /**
     * Render Settings page
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
        }

        // Polar Express roles
        $polar_roles = array(
            'polar_hr' => __('Polar HR', 'pexpress'),
            'polar_delivery' => __('Polar Delivery', 'pexpress'),
            'polar_fridge' => __('Polar Fridge Provider', 'pexpress'),
            'polar_distributor' => __('Polar Distributor', 'pexpress'),
            'polar_support' => __('Polar Support', 'pexpress'),
        );

        // Get users for each role
        $role_users = array();
        foreach ($polar_roles as $role_key => $role_name) {
            $role_users[$role_key] = get_users(array('role' => $role_key));
        }

        // Get all users for the add user form
        $all_users = get_users(array('number' => -1, 'orderby' => 'display_name'));

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Polar Express Settings', 'pexpress') . '</h1>';

        // Role Management Section
        echo '<div class="polar-role-management-section" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
        echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
        echo '<h2 style="margin: 0;">' . esc_html__('Role Management', 'pexpress') . '</h2>';
        echo '<button type="button" id="polar-add-user-btn" class="button button-primary">' . esc_html__('+ Add User to Role', 'pexpress') . '</button>';
        echo '</div>';
        echo '<p>' . esc_html__('Manage users assigned to each Polar Express role. Click "Add User to Role" to assign a user to a role.', 'pexpress') . '</p>';

        echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">';
        echo '<thead>';
        echo '<tr>';
        echo '<th style="width: 200px;">' . esc_html__('Role', 'pexpress') . '</th>';
        echo '<th>' . esc_html__('Description', 'pexpress') . '</th>';
        echo '<th>' . esc_html__('Assigned Users', 'pexpress') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($polar_roles as $role_key => $role_name) {
            $users = $role_users[$role_key];
            echo '<tr>';
            echo '<td><strong>' . esc_html($role_name) . '</strong></td>';
            echo '<td>' . esc_html($this->get_role_description($role_key)) . '</td>';
            echo '<td>';
            if (!empty($users)) {
                echo '<div class="polar-users-list" style="display: flex; flex-direction: column; gap: 8px;">';
                foreach ($users as $user) {
                    // Get all polar roles for this user
                    $user_polar_roles = array_intersect($user->roles, array_keys($polar_roles));
                    $other_roles = array_diff($user_polar_roles, array($role_key));

                    echo '<div class="polar-user-item" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #f6f7f7; border-radius: 4px; border: 1px solid #dcdcde;">';
                    echo '<div style="flex: 1;">';
                    echo '<strong style="display: block; color: #1d2327; font-size: 14px;">' . esc_html($user->display_name) . '</strong>';
                    echo '<span style="color: #646970; font-size: 12px;">' . esc_html($user->user_email) . '</span>';
                    if (!empty($other_roles)) {
                        $other_role_names = array();
                        foreach ($other_roles as $or) {
                            $other_role_names[] = $polar_roles[$or];
                        }
                        echo '<span style="display: block; color: #2271b1; font-size: 11px; margin-top: 4px; font-style: italic;">';
                        echo esc_html__('Also has:', 'pexpress') . ' ' . esc_html(implode(', ', $other_role_names));
                        echo '</span>';
                    }
                    echo '</div>';
                    echo '<button type="button" class="button button-small polar-remove-user-btn" data-role="' . esc_attr($role_key) . '" data-user-id="' . esc_attr($user->ID) . '" data-user-name="' . esc_attr($user->display_name) . '" style="margin-left: 10px; color: #b32d2e; border-color: #b32d2e;">';
                    echo '<span class="dashicons dashicons-dismiss" style="font-size: 16px; width: 16px; height: 16px; line-height: 1.2;"></span> ' . esc_html__('Remove', 'pexpress');
                    echo '</button>';
                    echo '</div>';
                }
                echo '<p style="margin: 10px 0 0 0; font-size: 12px; color: #646970; font-style: italic;">' . sprintf(esc_html__('Total: %d user(s)', 'pexpress'), count($users)) . '</p>';
                echo '</div>';
            } else {
                echo '<div style="padding: 20px; text-align: center; color: #999; background: #f6f7f7; border-radius: 4px; border: 1px dashed #dcdcde;">';
                echo '<span class="dashicons dashicons-groups" style="font-size: 32px; width: 32px; height: 32px; display: block; margin: 0 auto 10px; opacity: 0.5;"></span>';
                echo '<p style="margin: 0; font-size: 14px;">' . esc_html__('No users assigned to this role', 'pexpress') . '</p>';
                echo '<p style="margin: 5px 0 0 0; font-size: 12px;">' . esc_html__('Click "Add User to Role" to assign users', 'pexpress') . '</p>';
                echo '</div>';
            }
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        // Add User Modal Form
        echo '<div id="polar-add-user-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; align-items: center; justify-content: center;">';
        echo '<div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 800px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">';
        echo '<h2 style="margin-top: 0;">' . esc_html__('Add User to Role', 'pexpress') . '</h2>';
        echo '<form id="polar-add-user-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('pexpress_add_user_to_role', 'pexpress_add_user_nonce');
        echo '<input type="hidden" name="action" value="pexpress_add_user_to_role">';

        echo '<div style="margin-bottom: 20px; width: 100%; display: block;">';
        echo '<label for="polar_add_role" style="display: block; margin-bottom: 8px; font-weight: 600;">' . esc_html__('Select Role:', 'pexpress') . '</label>';
        echo '<select name="polar_role" id="polar_add_role" required style="width: 100%; max-width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; box-sizing: border-box; display: block;">';
        echo '<option value="">' . esc_html__('Select a role...', 'pexpress') . '</option>';
        foreach ($polar_roles as $role_key => $role_name) {
            echo '<option value="' . esc_attr($role_key) . '">' . esc_html($role_name) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div style="margin-bottom: 20px; width: 100%; display: block;">';
        echo '<label for="polar_add_user" style="display: block; margin-bottom: 8px; font-weight: 600;">' . esc_html__('Select Users (Multiple Selection):', 'pexpress') . '</label>';
        echo '<select name="user_id[]" id="polar_add_user" multiple required style="width: 100%; max-width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; min-height: 200px; font-size: 14px; box-sizing: border-box; display: block;" disabled>';
        echo '<option value="">' . esc_html__('Please select a role first...', 'pexpress') . '</option>';
        // Store all users with their roles as data attributes
        foreach ($all_users as $user) {
            $user_roles = array_intersect($user->roles, array_keys($polar_roles));
            $user_roles_str = implode(',', $user_roles);
            $current_roles_text = '';
            if (!empty($user_roles)) {
                $role_names = array();
                foreach ($user_roles as $ur) {
                    $role_names[] = $polar_roles[$ur];
                }
                $current_roles_text = ' - ' . esc_html__('Current:', 'pexpress') . ' ' . implode(', ', $role_names);
            }
            echo '<option value="' . esc_attr($user->ID) . '" data-roles="' . esc_attr($user_roles_str) . '" class="polar-user-option">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')' . $current_roles_text . '</option>';
        }
        echo '</select>';
        echo '<p style="margin: 5px 0 0 0; font-size: 12px; color: #646970;" id="polar-user-help-text">';
        echo esc_html__('Please select a role first to see available users. Users already assigned to the selected role will be hidden.', 'pexpress');
        echo '</p>';
        echo '<p style="margin: 5px 0 0 0; font-size: 12px; color: #2271b1; font-weight: 600;" id="polar-selected-count">' . esc_html__('0 users selected', 'pexpress') . '</p>';
        echo '</div>';

        echo '<div style="display: flex; gap: 10px; justify-content: flex-end;">';
        echo '<button type="button" id="polar-cancel-add-user" class="button">' . esc_html__('Cancel', 'pexpress') . '</button>';
        echo '<button type="submit" class="button button-primary">' . esc_html__('Add Users', 'pexpress') . '</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';

        // Settings Form
        echo '<div class="polar-settings-section" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
        echo '<h2>' . esc_html__('Plugin Settings', 'pexpress') . '</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields('pexpress_settings_group');
        do_settings_sections('polar-express-settings');
        submit_button(__('Save Settings', 'pexpress'));
        echo '</form>';
        echo '</div>';

        echo '</div>';

        // Add inline JavaScript for modal and AJAX
?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Open modal
                $('#polar-add-user-btn').on('click', function() {
                    $('#polar-add-user-modal').css('display', 'flex');
                    // Reset form
                    $('#polar-add-user-form')[0].reset();
                    $('#polar-selected-count').text('<?php echo esc_js(__('0 users selected', 'pexpress')); ?>');
                    $('#polar_add_user').prop('disabled', true);
                    $('#polar-user-help-text').text('<?php echo esc_js(__('Please select a role first to see available users. Users already assigned to the selected role will be hidden.', 'pexpress')); ?>');
                });

                // Close modal
                $('#polar-cancel-add-user, #polar-add-user-modal').on('click', function(e) {
                    if (e.target === this) {
                        $('#polar-add-user-modal').hide();
                    }
                });

                // Filter users when role is selected
                $('#polar_add_role').on('change', function() {
                    var selectedRole = $(this).val();
                    var $userSelect = $('#polar_add_user');

                    if (!selectedRole) {
                        $userSelect.prop('disabled', true).val(null);
                        $userSelect.find('option').not(':first').hide();
                        $('#polar-selected-count').text('<?php echo esc_js(__('0 users selected', 'pexpress')); ?>');
                        $('#polar-user-help-text').text('<?php echo esc_js(__('Please select a role first to see available users. Users already assigned to the selected role will be hidden.', 'pexpress')); ?>');
                        return;
                    }

                    // Enable user select
                    $userSelect.prop('disabled', false);

                    // Show/hide users based on selected role
                    var visibleCount = 0;
                    $userSelect.find('.polar-user-option').each(function() {
                        var $option = $(this);
                        var userRoles = $option.data('roles') || '';
                        var userRolesArray = userRoles ? userRoles.split(',') : [];

                        // Hide if user already has this role
                        if (userRolesArray.indexOf(selectedRole) !== -1) {
                            $option.hide().prop('selected', false);
                        } else {
                            $option.show();
                            visibleCount++;
                        }
                    });

                    // Update help text
                    if (visibleCount === 0) {
                        $('#polar-user-help-text').html('<span style="color: #d63638;"><?php echo esc_js(__('All users are already assigned to this role.', 'pexpress')); ?></span>');
                    } else {
                        $('#polar-user-help-text').text('<?php echo esc_js(__('Hold Ctrl (Windows) or Cmd (Mac) to select multiple users. Users already assigned to the selected role are hidden.', 'pexpress')); ?>');
                    }

                    // Reset selected count
                    $('#polar-selected-count').text('<?php echo esc_js(__('0 users selected', 'pexpress')); ?>');
                });

                // Update selected count for multi-select
                $('#polar_add_user').on('change', function() {
                    var selectedCount = $(this).val() ? $(this).val().length : 0;
                    if (selectedCount === 0) {
                        $('#polar-selected-count').text('<?php echo esc_js(__('0 users selected', 'pexpress')); ?>');
                    } else if (selectedCount === 1) {
                        $('#polar-selected-count').text('<?php echo esc_js(__('1 user selected', 'pexpress')); ?>');
                    } else {
                        $('#polar-selected-count').text(selectedCount + ' <?php echo esc_js(__('users selected', 'pexpress')); ?>');
                    }
                });

                // Validate form before submit
                $('#polar-add-user-form').on('submit', function(e) {
                    var role = $('#polar_add_role').val();
                    var users = $('#polar_add_user').val();

                    if (!role) {
                        alert('<?php echo esc_js(__('Please select a role.', 'pexpress')); ?>');
                        e.preventDefault();
                        return false;
                    }

                    if (!users || users.length === 0) {
                        alert('<?php echo esc_js(__('Please select at least one user.', 'pexpress')); ?>');
                        e.preventDefault();
                        return false;
                    }

                    return true;
                });

                // Remove user from role
                $('.polar-remove-user-btn').on('click', function() {
                    var $btn = $(this);
                    var role = $btn.data('role');
                    var userId = $btn.data('user-id');
                    var userName = $btn.data('user-name');

                    if (!userId) {
                        alert('<?php echo esc_js(__('Invalid user selected.', 'pexpress')); ?>');
                        return;
                    }

                    var confirmMessage = '<?php echo esc_js(__('Are you sure you want to remove', 'pexpress')); ?>' + ' "' + userName + '" ' + '<?php echo esc_js(__('from this role?', 'pexpress')); ?>';

                    if (!confirm(confirmMessage)) {
                        return;
                    }

                    // Disable button and show loading state
                    $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> <?php echo esc_js(__('Removing...', 'pexpress')); ?>');

                    var form = $('<form>', {
                        method: 'post',
                        action: '<?php echo esc_url(admin_url('admin-post.php')); ?>'
                    });

                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'action',
                        value: 'pexpress_remove_user_from_role'
                    }));
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'user_id',
                        value: userId
                    }));
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'polar_role',
                        value: role
                    }));
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'pexpress_remove_user_nonce',
                        value: '<?php echo wp_create_nonce('pexpress_remove_user_from_role'); ?>'
                    }));

                    $('body').append(form);
                    form.submit();
                });
            });
        </script>
        <style>
            #polar-add-user-form {
                width: 100%;
                display: block;
            }

            #polar-add-user-form>div {
                width: 100%;
                display: block;
            }

            #polar_add_role,
            #polar_add_user {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                display: block !important;
            }

            #polar_add_user {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right 8px center;
                background-size: 16px 12px;
                padding-right: 30px;
            }

            #polar_add_user option {
                padding: 5px;
            }

            #polar_add_user option:checked {
                background: #2271b1 linear-gradient(0deg, #2271b1 0%, #2271b1 100%);
                color: #fff;
            }

            #polar_add_user:disabled {
                background-color: #f0f0f1;
                cursor: not-allowed;
                opacity: 0.6;
            }

            #polar_add_user option[hidden] {
                display: none;
            }

            .polar-user-item {
                transition: all 0.2s ease;
            }

            .polar-user-item:hover {
                background: #f0f0f1 !important;
                border-color: #c3c4c7 !important;
            }

            .polar-remove-user-btn {
                transition: all 0.2s ease;
            }

            .polar-remove-user-btn:hover {
                background: #b32d2e !important;
                color: #fff !important;
                border-color: #8a2424 !important;
            }

            .polar-remove-user-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
        </style>
    <?php
    }

    /**
     * Get role description
     */
    private function get_role_description($role_key)
    {
        $descriptions = array(
            'polar_hr' => __('Full access to assign orders and manage operations', 'pexpress'),
            'polar_delivery' => __('Can view and update delivery status for assigned orders', 'pexpress'),
            'polar_fridge' => __('Can view and mark fridge collection for assigned orders', 'pexpress'),
            'polar_distributor' => __('Can view and mark fulfillment for assigned orders', 'pexpress'),
            'polar_support' => __('Can view all orders and provide customer support', 'pexpress'),
        );
        return isset($descriptions[$role_key]) ? $descriptions[$role_key] : '';
    }

    /**
     * Handle role assignment
     */
    public function handle_role_assignment()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to perform this action.', 'pexpress'));
        }

        if (!isset($_POST['user_id']) || !isset($_POST['polar_role']) || !isset($_POST['pexpress_role_nonce'])) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=missing_fields'));
            exit;
        }

        $user_id = intval($_POST['user_id']);
        $polar_role = sanitize_text_field($_POST['polar_role']);

        if (!wp_verify_nonce($_POST['pexpress_role_nonce'], 'pexpress_assign_role_' . $user_id)) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=nonce_failed'));
            exit;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=user_not_found'));
            exit;
        }

        // Polar Express roles
        $polar_roles = array('polar_hr', 'polar_delivery', 'polar_fridge', 'polar_distributor', 'polar_support');

        // Remove existing Polar roles
        foreach ($polar_roles as $role) {
            $user->remove_role($role);
        }

        // Add new role if not removing
        if ($polar_role !== 'remove' && in_array($polar_role, $polar_roles)) {
            $user->add_role($polar_role);
        }

        // Preserve admin/shop_manager role if user had it
        $current_roles = $user->roles;
        if (!in_array('administrator', $current_roles) && !in_array('shop_manager', $current_roles)) {
            // If user had admin or shop_manager, keep it
            // (This is handled automatically by WordPress role system)
        }

        wp_redirect(admin_url('admin.php?page=polar-express-settings&role_assigned=1'));
        exit;
    }

    /**
     * Handle adding user to role
     */
    public function handle_add_user_to_role()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to perform this action.', 'pexpress'));
        }

        if (!isset($_POST['user_id']) || !isset($_POST['polar_role']) || !isset($_POST['pexpress_add_user_nonce'])) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=missing_fields'));
            exit;
        }

        if (!wp_verify_nonce($_POST['pexpress_add_user_nonce'], 'pexpress_add_user_to_role')) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=nonce_failed'));
            exit;
        }

        // Handle multiple user IDs
        $user_ids = isset($_POST['user_id']) ? $_POST['user_id'] : array();
        if (!is_array($user_ids)) {
            $user_ids = array($user_ids);
        }
        $user_ids = array_map('intval', $user_ids);
        $user_ids = array_filter($user_ids); // Remove empty values

        if (empty($user_ids)) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=missing_fields'));
            exit;
        }

        $polar_role = sanitize_text_field($_POST['polar_role']);

        $polar_roles = array('polar_hr', 'polar_delivery', 'polar_fridge', 'polar_distributor', 'polar_support');
        if (!in_array($polar_role, $polar_roles)) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=invalid_role'));
            exit;
        }

        $success_count = 0;
        $failed_count = 0;

        // Process each user
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) {
                $failed_count++;
                continue;
            }

            // Check if user already has this role
            if (in_array($polar_role, $user->roles)) {
                // User already has this role, skip
                continue;
            }

            // Add the new role (don't remove existing polar roles - allow multiple roles)
            $user->add_role($polar_role);
            $success_count++;
        }

        // Redirect with success message
        if ($success_count > 0) {
            $message = $success_count === 1
                ? 'user_added=1'
                : 'users_added=' . $success_count;
            if ($failed_count > 0) {
                $message .= '&some_failed=' . $failed_count;
            }
            wp_redirect(admin_url('admin.php?page=polar-express-settings&' . $message));
        } else {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=user_not_found'));
        }
        exit;
    }

    /**
     * Handle removing user from role
     */
    public function handle_remove_user_from_role()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to perform this action.', 'pexpress'));
        }

        if (!isset($_POST['user_id']) || !isset($_POST['polar_role']) || !isset($_POST['pexpress_remove_user_nonce'])) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=missing_fields'));
            exit;
        }

        if (!wp_verify_nonce($_POST['pexpress_remove_user_nonce'], 'pexpress_remove_user_from_role')) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=nonce_failed'));
            exit;
        }

        $user_id = intval($_POST['user_id']);
        $polar_role = sanitize_text_field($_POST['polar_role']);

        $user = get_userdata($user_id);
        if (!$user) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=user_not_found'));
            exit;
        }

        // Remove the role
        $user->remove_role($polar_role);

        wp_redirect(admin_url('admin.php?page=polar-express-settings&user_removed=1'));
        exit;
    }

    /**
     * AJAX handler to get users for a role
     */
    public function ajax_get_users_for_role()
    {
        check_ajax_referer('polar_express_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'pexpress')));
        }

        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $polar_roles = array('polar_hr', 'polar_delivery', 'polar_fridge', 'polar_distributor', 'polar_support');

        if (!in_array($role, $polar_roles)) {
            wp_send_json_error(array('message' => __('Invalid role.', 'pexpress')));
        }

        $users = get_users(array('role' => $role));
        $users_data = array();

        foreach ($users as $user) {
            $users_data[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
            );
        }

        wp_send_json_success(array('users' => $users_data));
    }

    /**
     * Render Setup Guideline page
     */
    public function render_setup_guideline_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
        }

        $shortcodes = array(
            'polar_hr' => array(
                'name' => __('HR Dashboard', 'pexpress'),
                'shortcode' => '[polar_hr]',
                'description' => __('Full access to assign orders and manage operations', 'pexpress'),
                'page_suggestion' => '/hr-dashboard',
            ),
            'polar_delivery' => array(
                'name' => __('Delivery Dashboard', 'pexpress'),
                'shortcode' => '[polar_delivery]',
                'description' => __('Can view and update delivery status for assigned orders', 'pexpress'),
                'page_suggestion' => '/my-deliveries',
            ),
            'polar_fridge' => array(
                'name' => __('Fridge Provider Dashboard', 'pexpress'),
                'shortcode' => '[polar_fridge]',
                'description' => __('Can view and mark fridge collection for assigned orders', 'pexpress'),
                'page_suggestion' => '/fridge-tasks',
            ),
            'polar_distributor' => array(
                'name' => __('Distributor Dashboard', 'pexpress'),
                'shortcode' => '[polar_distributor]',
                'description' => __('Can view and mark fulfillment for assigned orders', 'pexpress'),
                'page_suggestion' => '/distributor-tasks',
            ),
            'polar_support' => array(
                'name' => __('Support Dashboard', 'pexpress'),
                'shortcode' => '[polar_support]',
                'description' => __('Can view all orders and provide customer support', 'pexpress'),
                'page_suggestion' => '/support-dashboard',
            ),
        );

    ?>
        <div class="wrap polar-setup-guideline">
            <style>
                .polar-setup-guideline {
                    max-width: 1200px;
                    margin: 20px auto;
                }

                .polar-setup-header {
                    background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
                    color: #fff;
                    padding: 40px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }

                .polar-setup-header h1 {
                    color: #fff;
                    margin: 0 0 10px 0;
                    font-size: 32px;
                }

                .polar-setup-header p {
                    color: rgba(255, 255, 255, 0.9);
                    font-size: 16px;
                    margin: 0;
                }

                .polar-setup-section {
                    background: #fff;
                    padding: 30px;
                    margin-bottom: 30px;
                    border: 1px solid #ccd0d4;
                    border-radius: 8px;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                }

                .polar-setup-section h2 {
                    margin-top: 0;
                    color: #1d2327;
                    font-size: 24px;
                    border-bottom: 2px solid #2271b1;
                    padding-bottom: 10px;
                }

                .polar-setup-step {
                    background: #f6f7f7;
                    padding: 20px;
                    margin: 20px 0;
                    border-left: 4px solid #2271b1;
                    border-radius: 4px;
                }

                .polar-setup-step h3 {
                    margin-top: 0;
                    color: #2271b1;
                    font-size: 18px;
                }

                .polar-setup-step ol,
                .polar-setup-step ul {
                    margin: 15px 0;
                    padding-left: 25px;
                }

                .polar-setup-step li {
                    margin: 8px 0;
                    line-height: 1.6;
                }

                .polar-shortcode-card {
                    background: #fff;
                    border: 1px solid #dcdcde;
                    border-radius: 6px;
                    padding: 20px;
                    margin: 15px 0;
                    transition: box-shadow 0.2s;
                }

                .polar-shortcode-card:hover {
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                }

                .polar-shortcode-card h4 {
                    margin: 0 0 10px 0;
                    color: #1d2327;
                    font-size: 18px;
                }

                .polar-shortcode-code {
                    background: #1d2327;
                    color: #00a32a;
                    padding: 12px;
                    border-radius: 4px;
                    font-family: 'Courier New', monospace;
                    font-size: 14px;
                    margin: 10px 0;
                    word-break: break-all;
                    position: relative;
                }

                .polar-shortcode-code::before {
                    content: 'Shortcode:';
                    color: #8c8f94;
                    font-size: 12px;
                    display: block;
                    margin-bottom: 5px;
                }

                .polar-copy-btn {
                    background: #2271b1;
                    color: #fff;
                    border: none;
                    padding: 6px 12px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 12px;
                    margin-top: 5px;
                }

                .polar-copy-btn:hover {
                    background: #135e96;
                }

                .polar-copy-btn.copied {
                    background: #00a32a;
                }

                .polar-info-box {
                    background: #e7f5e7;
                    border-left: 4px solid #00a32a;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }

                .polar-warning-box {
                    background: #fff3cd;
                    border-left: 4px solid #ffb900;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }

                .polar-checklist {
                    list-style: none;
                    padding-left: 0;
                }

                .polar-checklist li {
                    padding: 10px 0 10px 35px;
                    position: relative;
                }

                .polar-checklist li::before {
                    content: '☐';
                    position: absolute;
                    left: 0;
                    font-size: 20px;
                    color: #2271b1;
                }

                .polar-checklist li.completed::before {
                    content: '✓';
                    color: #00a32a;
                }

                @media (max-width: 768px) {
                    .polar-setup-header {
                        padding: 25px;
                    }

                    .polar-setup-header h1 {
                        font-size: 24px;
                    }

                    .polar-setup-section {
                        padding: 20px;
                    }
                }
            </style>

            <div class="polar-setup-header">
                <h1><?php esc_html_e('Polar Express Setup Guideline', 'pexpress'); ?></h1>
                <p><?php esc_html_e('Follow these steps to set up and configure Polar Express for your team.', 'pexpress'); ?></p>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('📋 Quick Setup Checklist', 'pexpress'); ?></h2>
                <ul class="polar-checklist">
                    <li><?php esc_html_e('Assign users to Polar Express roles', 'pexpress'); ?></li>
                    <li><?php esc_html_e('Create dashboard pages with shortcodes', 'pexpress'); ?></li>
                    <li><?php esc_html_e('Configure SMS settings (optional)', 'pexpress'); ?></li>
                    <li><?php esc_html_e('Test order assignment workflow', 'pexpress'); ?></li>
                </ul>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('👥 Step 1: Assign Users to Roles', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <h3><?php esc_html_e('Role Management', 'pexpress'); ?></h3>
                    <ol>
                        <li><?php esc_html_e('Go to Polar Express → Settings', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Click "Add User to Role" button', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Select a role from the dropdown', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Select a user to assign to that role', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Click "Add User" to complete the assignment', 'pexpress'); ?></li>
                    </ol>
                    <div class="polar-info-box">
                        <strong><?php esc_html_e('Available Roles:', 'pexpress'); ?></strong>
                        <ul style="margin-top: 10px;">
                            <li><strong><?php esc_html_e('Polar HR:', 'pexpress'); ?></strong> <?php esc_html_e('Full access to assign orders and manage operations', 'pexpress'); ?></li>
                            <li><strong><?php esc_html_e('Polar Delivery:', 'pexpress'); ?></strong> <?php esc_html_e('Can view and update delivery status for assigned orders', 'pexpress'); ?></li>
                            <li><strong><?php esc_html_e('Polar Fridge Provider:', 'pexpress'); ?></strong> <?php esc_html_e('Can view and mark fridge collection for assigned orders', 'pexpress'); ?></li>
                            <li><strong><?php esc_html_e('Polar Distributor:', 'pexpress'); ?></strong> <?php esc_html_e('Can view and mark fulfillment for assigned orders', 'pexpress'); ?></li>
                            <li><strong><?php esc_html_e('Polar Support:', 'pexpress'); ?></strong> <?php esc_html_e('Can view all orders and provide customer support', 'pexpress'); ?></li>
                        </ul>
                    </div>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-settings')); ?>" class="button button-primary">
                            <?php esc_html_e('Go to Settings →', 'pexpress'); ?>
                        </a>
                    </p>
                </div>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('📄 Step 2: Create Dashboard Pages', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <h3><?php esc_html_e('Create Pages for Each Dashboard', 'pexpress'); ?></h3>
                    <ol>
                        <li><?php esc_html_e('Go to Pages → Add New', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Create a new page for each dashboard type', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Paste the corresponding shortcode in the page content', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Publish the page', 'pexpress'); ?></li>
                    </ol>
                    <div class="polar-warning-box">
                        <strong><?php esc_html_e('Important:', 'pexpress'); ?></strong> <?php esc_html_e('Each user will only see their own dashboard based on their assigned role. Make sure users are logged in when accessing these pages.', 'pexpress'); ?>
                    </div>
                </div>

                <h3 style="margin-top: 30px;"><?php esc_html_e('Available Shortcodes', 'pexpress'); ?></h3>
                <?php foreach ($shortcodes as $key => $shortcode) : ?>
                    <div class="polar-shortcode-card">
                        <h4><?php echo esc_html($shortcode['name']); ?></h4>
                        <p><?php echo esc_html($shortcode['description']); ?></p>
                        <div class="polar-shortcode-code">
                            <?php echo esc_html($shortcode['shortcode']); ?>
                        </div>
                        <button class="polar-copy-btn" data-shortcode="<?php echo esc_attr($shortcode['shortcode']); ?>">
                            <?php esc_html_e('Copy Shortcode', 'pexpress'); ?>
                        </button>
                        <p style="margin-top: 10px; color: #646970; font-size: 13px;">
                            <strong><?php esc_html_e('Suggested Page Slug:', 'pexpress'); ?></strong>
                            <code><?php echo esc_html($shortcode['page_suggestion']); ?></code>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('⚙️ Step 3: Configure SMS Settings (Optional)', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <h3><?php esc_html_e('SMS Notifications', 'pexpress'); ?></h3>
                    <p><?php esc_html_e('Configure SMS notifications to automatically notify team members when orders are assigned to them.', 'pexpress'); ?></p>
                    <ol>
                        <li><?php esc_html_e('Go to Polar Express → Settings', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Scroll to "SMS Configuration" section', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Enable SMS notifications', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Enter your SSLCommerz SMS API credentials', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Save settings', 'pexpress'); ?></li>
                    </ol>
                    <div class="polar-info-box">
                        <strong><?php esc_html_e('Note:', 'pexpress'); ?></strong> <?php esc_html_e('SMS notifications are optional. The plugin will work without SMS configuration, but team members won\'t receive automatic notifications.', 'pexpress'); ?>
                    </div>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-settings')); ?>" class="button button-primary">
                            <?php esc_html_e('Go to Settings →', 'pexpress'); ?>
                        </a>
                    </p>
                </div>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('🔄 Step 4: Workflow Overview', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <h3><?php esc_html_e('Order Processing Flow', 'pexpress'); ?></h3>
                    <ol>
                        <li><strong><?php esc_html_e('Order Received:', 'pexpress'); ?></strong> <?php esc_html_e('New orders appear in HR Dashboard for assignment', 'pexpress'); ?></li>
                        <li><strong><?php esc_html_e('HR Assignment:', 'pexpress'); ?></strong> <?php esc_html_e('HR assigns orders to Delivery, Fridge Provider, and Distributor', 'pexpress'); ?></li>
                        <li><strong><?php esc_html_e('Distributor Fulfills:', 'pexpress'); ?></strong> <?php esc_html_e('Distributor marks orders as fulfilled', 'pexpress'); ?></li>
                        <li><strong><?php esc_html_e('Fridge Collection:', 'pexpress'); ?></strong> <?php esc_html_e('Fridge Provider collects and later returns the fridge', 'pexpress'); ?></li>
                        <li><strong><?php esc_html_e('Delivery:', 'pexpress'); ?></strong> <?php esc_html_e('Delivery person marks orders as out for delivery and then delivered', 'pexpress'); ?></li>
                        <li><strong><?php esc_html_e('Support:', 'pexpress'); ?></strong> <?php esc_html_e('Support team can view all orders to assist customers', 'pexpress'); ?></li>
                    </ol>
                </div>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('💡 Tips & Best Practices', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <ul>
                        <li><?php esc_html_e('Create separate pages for each dashboard type for better organization', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Use descriptive page slugs (e.g., /my-deliveries, /fridge-tasks)', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Ensure all team members have WordPress user accounts before assigning roles', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Test the workflow with a test order before going live', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Keep SMS credentials secure and never share them publicly', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Regularly check the HR Dashboard for pending assignments', 'pexpress'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('❓ Need Help?', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <p><?php esc_html_e('If you encounter any issues or need assistance:', 'pexpress'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Check the plugin documentation', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Review the error messages in WordPress admin notices', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Ensure WooCommerce is installed and active', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Verify user roles are correctly assigned', 'pexpress'); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.polar-copy-btn').on('click', function() {
                    var $btn = $(this);
                    var shortcode = $btn.data('shortcode');
                    var $temp = $('<textarea>');
                    $('body').append($temp);
                    $temp.val(shortcode).select();
                    document.execCommand('copy');
                    $temp.remove();

                    var originalText = $btn.text();
                    $btn.text('<?php echo esc_js(__('Copied!', 'pexpress')); ?>').addClass('copied');
                    setTimeout(function() {
                        $btn.text(originalText).removeClass('copied');
                    }, 2000);
                });
            });
        </script>
<?php
    }

    /**
     * Show role assignment notices
     */
    public function show_role_assignment_notices()
    {
        if (isset($_GET['role_assigned']) && $_GET['role_assigned'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('User role assigned successfully!', 'pexpress') . '</p></div>';
        }

        if (isset($_GET['user_added']) && $_GET['user_added'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('User added to role successfully!', 'pexpress') . '</p></div>';
        }

        if (isset($_GET['users_added']) && is_numeric($_GET['users_added'])) {
            $count = intval($_GET['users_added']);
            $message = $count === 1
                ? __('User added to role successfully!', 'pexpress')
                : sprintf(__('%d users added to role successfully!', 'pexpress'), $count);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';

            if (isset($_GET['some_failed']) && intval($_GET['some_failed']) > 0) {
                $failed_count = intval($_GET['some_failed']);
                echo '<div class="notice notice-warning is-dismissible"><p>' .
                    sprintf(esc_html__('Warning: %d user(s) could not be added. Please check if the user IDs are valid.', 'pexpress'), $failed_count) .
                    '</p></div>';
            }
        }

        if (isset($_GET['user_removed']) && $_GET['user_removed'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('User removed from role successfully!', 'pexpress') . '</p></div>';
        }

        if (isset($_GET['error'])) {
            $error_messages = array(
                'missing_fields' => __('Missing required fields.', 'pexpress'),
                'nonce_failed' => __('Security check failed.', 'pexpress'),
                'user_not_found' => __('User not found.', 'pexpress'),
                'invalid_role' => __('Invalid role selected.', 'pexpress'),
            );
            $error = isset($error_messages[$_GET['error']]) ? $error_messages[$_GET['error']] : __('An error occurred.', 'pexpress');
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
        }
    }
}
