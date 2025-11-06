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
        add_action('admin_notices', array($this, 'show_role_assignment_notices'));
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

        // Get all users
        $all_users = get_users(array('number' => -1));

        // Polar Express roles
        $polar_roles = array(
            'polar_hr' => __('Polar HR', 'pexpress'),
            'polar_delivery' => __('Polar Delivery', 'pexpress'),
            'polar_fridge' => __('Polar Fridge Provider', 'pexpress'),
            'polar_distributor' => __('Polar Distributor', 'pexpress'),
            'polar_support' => __('Polar Support', 'pexpress'),
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Polar Express Settings', 'pexpress') . '</h1>';

        // User Role Assignment Section
        echo '<div class="polar-user-roles-section" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
        echo '<h2>' . esc_html__('User Role Assignment', 'pexpress') . '</h2>';
        echo '<p>' . esc_html__('Assign Polar Express roles to users. Users can be assigned to one of the following roles:', 'pexpress') . '</p>';
        echo '<ul style="list-style: disc; margin-left: 20px;">';
        foreach ($polar_roles as $role_key => $role_name) {
            echo '<li><strong>' . esc_html($role_name) . '</strong> - ' . esc_html($this->get_role_description($role_key)) . '</li>';
        }
        echo '</ul>';

        echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('User', 'pexpress') . '</th>';
        echo '<th>' . esc_html__('Email', 'pexpress') . '</th>';
        echo '<th>' . esc_html__('Current Roles', 'pexpress') . '</th>';
        echo '<th>' . esc_html__('Assign Polar Role', 'pexpress') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($all_users as $user) {
            $user_roles = $user->roles;
            $has_polar_role = false;
            $current_polar_role = '';

            foreach ($polar_roles as $role_key => $role_name) {
                if (in_array($role_key, $user_roles)) {
                    $has_polar_role = true;
                    $current_polar_role = $role_key;
                    break;
                }
            }

            echo '<tr>';
            echo '<td><strong>' . esc_html($user->display_name) . '</strong><br><small>' . esc_html($user->user_login) . '</small></td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>';
            if ($has_polar_role) {
                echo '<span style="color: #46b450; font-weight: bold;">✓ ' . esc_html($polar_roles[$current_polar_role]) . '</span>';
            } else {
                $other_roles = array_diff($user_roles, array_keys($polar_roles));
                if (!empty($other_roles)) {
                    $wp_roles = wp_roles()->get_names();
                    $role_labels = array();
                    foreach ($other_roles as $role) {
                        if (isset($wp_roles[$role])) {
                            $role_labels[] = $wp_roles[$role];
                        }
                    }
                    echo esc_html(implode(', ', $role_labels));
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
            }
            echo '</td>';
            echo '<td>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display: inline;">';
            wp_nonce_field('pexpress_assign_role_' . $user->ID, 'pexpress_role_nonce');
            echo '<input type="hidden" name="action" value="pexpress_assign_role">';
            echo '<input type="hidden" name="user_id" value="' . esc_attr($user->ID) . '">';
            echo '<select name="polar_role" style="margin-right: 5px;">';
            echo '<option value="">' . esc_html__('Select Role...', 'pexpress') . '</option>';
            foreach ($polar_roles as $role_key => $role_name) {
                $selected = ($current_polar_role === $role_key) ? 'selected' : '';
                echo '<option value="' . esc_attr($role_key) . '" ' . $selected . '>' . esc_html($role_name) . '</option>';
            }
            echo '<option value="remove">' . esc_html__('Remove Polar Role', 'pexpress') . '</option>';
            echo '</select>';
            echo '<button type="submit" class="button button-small">' . esc_html__('Assign', 'pexpress') . '</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
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
     * Show role assignment notices
     */
    public function show_role_assignment_notices()
    {
        if (isset($_GET['role_assigned']) && $_GET['role_assigned'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('User role assigned successfully!', 'pexpress') . '</p></div>';
        }

        if (isset($_GET['error'])) {
            $error_messages = array(
                'missing_fields' => __('Missing required fields.', 'pexpress'),
                'nonce_failed' => __('Security check failed.', 'pexpress'),
                'user_not_found' => __('User not found.', 'pexpress'),
            );
            $error = isset($error_messages[$_GET['error']]) ? $error_messages[$_GET['error']] : __('An error occurred.', 'pexpress');
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
        }
    }
}
