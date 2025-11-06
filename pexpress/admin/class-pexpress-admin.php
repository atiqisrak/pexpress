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
    }

    /**
     * Register admin menus
     */
    public function register_menus()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $capability = 'manage_woocommerce';

        add_menu_page(
            __('Polar Express', 'pexpress'),
            __('Polar Express', 'pexpress'),
            $capability,
            'polar-express',
            array($this, 'render_dashboard_page'),
            'dashicons-clipboard',
            56
        );

        add_submenu_page(
            'polar-express',
            __('Dashboard', 'pexpress'),
            __('Dashboard', 'pexpress'),
            $capability,
            'polar-express',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'polar-express',
            __('Settings', 'pexpress'),
            __('Settings', 'pexpress'),
            $capability,
            'polar-express-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('pexpress_settings_group', 'pexpress_options');

        add_settings_section(
            'pexpress_general_section',
            __('General Settings', 'pexpress'),
            '__return_false',
            'polar-express-settings'
        );

        add_settings_field(
            'pexpress_enable_sms',
            __('Enable SMS Notifications', 'pexpress'),
            array($this, 'render_checkbox_field'),
            'polar-express-settings',
            'pexpress_general_section',
            array(
                'label_for' => 'pexpress_enable_sms',
                'option_key' => 'enable_sms'
            )
        );
    }

    /**
     * Checkbox field renderer
     */
    public function render_checkbox_field($args)
    {
        $options = get_option('pexpress_options', array());
        $checked = !empty($options[$args['option_key']]) ? 'checked' : '';
        echo '<input type="checkbox" id="' . esc_attr($args['label_for']) . '" name="pexpress_options[' . esc_attr($args['option_key']) . ']" value="1" ' . $checked . ' />';
    }

    /**
     * Render Dashboard page
     */
    public function render_dashboard_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Polar Express Dashboard', 'pexpress') . '</h1>';
        echo '<p>' . esc_html__('Manage delivery assignments, fridge collections, and distributor operations.', 'pexpress') . '</p>';
        echo '</div>';
    }

    /**
     * Render Settings page
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Polar Express Settings', 'pexpress') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('pexpress_settings_group');
        do_settings_sections('polar-express-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}
