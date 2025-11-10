<?php

/**
 * Admin settings management
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin settings handler
 */
class PExpress_Admin_Settings
{

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
     * Get role description
     */
    public function get_role_description($role_key)
    {
        $descriptions = array(
            'polar_hr' => __('Full access to assign orders and manage operations (Agency)', 'pexpress'),
            'polar_delivery' => __('Can view and update delivery status for assigned orders (HR)', 'pexpress'),
            'polar_fridge' => __('Can view and mark fridge collection for assigned orders', 'pexpress'),
            'polar_distributor' => __('Can view and mark fulfillment for assigned orders', 'pexpress'),
            'polar_support' => __('Can view all orders and provide customer support', 'pexpress'),
        );
        return isset($descriptions[$role_key]) ? $descriptions[$role_key] : '';
    }

    /**
     * Render Settings page
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
        }

        $roles = new PExpress_Admin_Roles();

        // Polar Express roles
        $polar_roles = array(
            'polar_hr' => __('Polar Agency', 'pexpress'),
            'polar_delivery' => __('Polar HR', 'pexpress'),
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
        $roles->render_add_user_modal($polar_roles, $all_users);

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
        $roles->render_settings_scripts();
    }
}
