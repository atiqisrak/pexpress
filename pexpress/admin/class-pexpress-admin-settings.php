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
            'pexpress_enable_plugin',
            __('Enable Plugin', 'pexpress'),
            array($this, 'render_enable_plugin_field'),
            'polar-express-settings',
            'pexpress_sms_section'
        );

        add_settings_field(
            'pexpress_api_version',
            __('Select SSL Care Platform', 'pexpress'),
            array($this, 'render_api_version_field'),
            'polar-express-settings',
            'pexpress_sms_section'
        );

        add_settings_field(
            'pexpress_api_hash_token',
            __('API Hash Token', 'pexpress'),
            array($this, 'render_api_hash_token_field'),
            'polar-express-settings',
            'pexpress_sms_section'
        );

        add_settings_field(
            'pexpress_api_url',
            __('API URL', 'pexpress'),
            array($this, 'render_api_url_field'),
            'polar-express-settings',
            'pexpress_sms_section'
        );

        add_settings_field(
            'pexpress_api_username',
            __('API User', 'pexpress'),
            array($this, 'render_api_username_field'),
            'polar-express-settings',
            'pexpress_sms_section'
        );

        add_settings_field(
            'pexpress_api_password',
            __('API Password', 'pexpress'),
            array($this, 'render_api_password_field'),
            'polar-express-settings',
            'pexpress_sms_section'
        );

        add_settings_field(
            'pexpress_api_sid',
            __('SID/Stakeholder', 'pexpress'),
            array($this, 'render_api_sid_field'),
            'polar-express-settings',
            'pexpress_sms_section'
        );

        add_settings_field(
            'pexpress_enable_unicode',
            __('Unicode/Bangla SMS', 'pexpress'),
            array($this, 'render_enable_unicode_field'),
            'polar-express-settings',
            'pexpress_sms_section'
        );

        // Email Settings Section
        add_settings_section(
            'pexpress_email_section',
            __('Email Configuration', 'pexpress'),
            array($this, 'email_section_callback'),
            'polar-express-settings'
        );

        add_settings_field(
            'pexpress_enable_email',
            __('Enable Email Notifications', 'pexpress'),
            array($this, 'render_checkbox_field'),
            'polar-express-settings',
            'pexpress_email_section',
            array(
                'label_for' => 'pexpress_enable_email',
                'option_key' => 'email_config.enable_email',
                'description' => __('Enable email notifications for order status updates', 'pexpress')
            )
        );

        add_settings_field(
            'pexpress_email_from_name',
            __('From Name', 'pexpress'),
            array($this, 'render_text_field'),
            'polar-express-settings',
            'pexpress_email_section',
            array(
                'label_for' => 'pexpress_email_from_name',
                'option_key' => 'email_config.from_name',
                'description' => __('Name to use as sender', 'pexpress')
            )
        );

        add_settings_field(
            'pexpress_email_from_email',
            __('From Email', 'pexpress'),
            array($this, 'render_text_field'),
            'polar-express-settings',
            'pexpress_email_section',
            array(
                'label_for' => 'pexpress_email_from_email',
                'option_key' => 'email_config.from_email',
                'description' => __('Email address to use as sender', 'pexpress')
            )
        );

        // Email Templates Section
        add_settings_section(
            'pexpress_email_templates_section',
            __('Email Template Configuration', 'pexpress'),
            array($this, 'email_templates_section_callback'),
            'polar-express-settings'
        );

        // Order Confirmed Email Template
        add_settings_field(
            'pexpress_email_order_confirmed_enable',
            __('Order Confirmed Email Alert', 'pexpress'),
            array($this, 'render_email_template_enable_field'),
            'polar-express-settings',
            'pexpress_email_templates_section',
            array('template_key' => 'order_confirmed')
        );

        add_settings_field(
            'pexpress_email_order_confirmed_template',
            __('Order Confirmed Email Template', 'pexpress'),
            array($this, 'render_email_template_field'),
            'polar-express-settings',
            'pexpress_email_templates_section',
            array('template_key' => 'order_confirmed')
        );

        // Order Proceeded Email Template
        add_settings_field(
            'pexpress_email_order_proceeded_enable',
            __('Order Proceeded Email Alert', 'pexpress'),
            array($this, 'render_email_template_enable_field'),
            'polar-express-settings',
            'pexpress_email_templates_section',
            array('template_key' => 'order_proceeded')
        );

        add_settings_field(
            'pexpress_email_order_proceeded_template',
            __('Order Proceeded Email Template', 'pexpress'),
            array($this, 'render_email_template_field'),
            'polar-express-settings',
            'pexpress_email_templates_section',
            array('template_key' => 'order_proceeded')
        );

        // Out for Delivery Email Template
        add_settings_field(
            'pexpress_email_out_for_delivery_enable',
            __('Out for Delivery Email Alert', 'pexpress'),
            array($this, 'render_email_template_enable_field'),
            'polar-express-settings',
            'pexpress_email_templates_section',
            array('template_key' => 'out_for_delivery')
        );

        add_settings_field(
            'pexpress_email_out_for_delivery_template',
            __('Out for Delivery Email Template', 'pexpress'),
            array($this, 'render_email_template_field'),
            'polar-express-settings',
            'pexpress_email_templates_section',
            array('template_key' => 'out_for_delivery')
        );

        // Order Completed Email Template
        add_settings_field(
            'pexpress_email_order_completed_enable',
            __('Order Completed Email Alert', 'pexpress'),
            array($this, 'render_email_template_enable_field'),
            'polar-express-settings',
            'pexpress_email_templates_section',
            array('template_key' => 'order_completed')
        );

        add_settings_field(
            'pexpress_email_order_completed_template',
            __('Order Completed Email Template', 'pexpress'),
            array($this, 'render_email_template_field'),
            'polar-express-settings',
            'pexpress_email_templates_section',
            array('template_key' => 'order_completed')
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

        // SMS Templates Section
        add_settings_section(
            'pexpress_sms_templates_section',
            __('SMS Template Configuration', 'pexpress'),
            array($this, 'sms_templates_section_callback'),
            'polar-express-settings'
        );

        // Order Confirmed Template
        add_settings_field(
            'pexpress_order_confirmed_enable',
            __('Order Confirmed Alert', 'pexpress'),
            array($this, 'render_template_enable_field'),
            'polar-express-settings',
            'pexpress_sms_templates_section',
            array('template_key' => 'order_confirmed')
        );

        add_settings_field(
            'pexpress_order_confirmed_template',
            __('Order Confirmed Template', 'pexpress'),
            array($this, 'render_template_field'),
            'polar-express-settings',
            'pexpress_sms_templates_section',
            array('template_key' => 'order_confirmed')
        );

        // Order Proceeded Template
        add_settings_field(
            'pexpress_order_proceeded_enable',
            __('Order Proceeded Alert', 'pexpress'),
            array($this, 'render_template_enable_field'),
            'polar-express-settings',
            'pexpress_sms_templates_section',
            array('template_key' => 'order_proceeded')
        );

        add_settings_field(
            'pexpress_order_proceeded_template',
            __('Order Proceeded Template', 'pexpress'),
            array($this, 'render_template_field'),
            'polar-express-settings',
            'pexpress_sms_templates_section',
            array('template_key' => 'order_proceeded')
        );

        // Out for Delivery Template
        add_settings_field(
            'pexpress_out_for_delivery_enable',
            __('Out for Delivery Alert', 'pexpress'),
            array($this, 'render_template_enable_field'),
            'polar-express-settings',
            'pexpress_sms_templates_section',
            array('template_key' => 'out_for_delivery')
        );

        add_settings_field(
            'pexpress_out_for_delivery_template',
            __('Out for Delivery Template', 'pexpress'),
            array($this, 'render_template_field'),
            'polar-express-settings',
            'pexpress_sms_templates_section',
            array('template_key' => 'out_for_delivery')
        );

        // Order Completed Template
        add_settings_field(
            'pexpress_order_completed_enable',
            __('Order Completed Alert', 'pexpress'),
            array($this, 'render_template_enable_field'),
            'polar-express-settings',
            'pexpress_sms_templates_section',
            array('template_key' => 'order_completed')
        );

        add_settings_field(
            'pexpress_order_completed_template',
            __('Order Completed Template', 'pexpress'),
            array($this, 'render_template_field'),
            'polar-express-settings',
            'pexpress_sms_templates_section',
            array('template_key' => 'order_completed')
        );
    }

    /**
     * SMS section callback
     */
    public function sms_section_callback()
    {
        echo '<hr>';
    }

    /**
     * Render enable plugin field
     */
    public function render_enable_plugin_field()
    {
        $options = get_option('pexpress_options', array());
        $enable_plugin = isset($options['sms_config']['enable_plugin']) ? $options['sms_config']['enable_plugin'] : '';

        $html = '<input type="checkbox" id="pexpress_enable_plugin" name="pexpress_options[sms_config][enable_plugin]" value="1"' . checked(1, $enable_plugin, false) . '/>';
        $html .= '<label for="pexpress_enable_plugin">' . esc_html__('Check to enable the plugin.', 'pexpress') . '</label>';

        echo $html;
    }

    /**
     * Render API version field
     */
    public function render_api_version_field()
    {
        $options = get_option('pexpress_options', array());
        $api_version = isset($options['sms_config']['api_version']) ? $options['sms_config']['api_version'] : 'isms';

        $html = '<select id="pexpress_api_version" name="pexpress_options[sms_config][api_version]">';
        $html .= '<option value="isms"' . selected('isms', $api_version, false) . '>ISMS</option>';
        $html .= '<option value="ismsplus"' . selected('ismsplus', $api_version, false) . '>ISMS Plus</option>';
        $html .= '</select>';
        $html .= '<p class="description">' . esc_html__('Select SSL Care Platform', 'pexpress') . '</p>';

        echo $html;
    }

    /**
     * Render API hash token field
     */
    public function render_api_hash_token_field()
    {
        $options = get_option('pexpress_options', array());
        $api_hash_token = isset($options['sms_config']['api_hash_token']) ? esc_attr($options['sms_config']['api_hash_token']) : '';

        $html = '<input type="text" id="pexpress_api_hash_token" name="pexpress_options[sms_config][api_hash_token]" value="' . $api_hash_token . '" size="65" placeholder="' . esc_attr__('Only use for ISMS Plus Platform', 'pexpress') . '" />';
        $html .= '<p class="description">' . esc_html__('Only use for ISMS Plus (Get it from Panel Profile).', 'pexpress') . '</p>';

        echo $html;
    }

    /**
     * Render API URL field
     */
    public function render_api_url_field()
    {
        $options = get_option('pexpress_options', array());
        $allowed_api_urls = array(
            'http://sms.sslwireless.com/pushapi/dynamic/server.php',
            'https://smsplus.sslwireless.com/api/v3/send-sms'
        );

        $api_url = isset($options['sms_config']['api_url']) ? esc_url($options['sms_config']['api_url']) : '';

        // If the stored URL is not in the allowed list, set default
        if (!in_array($api_url, $allowed_api_urls) && !empty($api_url)) {
            $api_url = 'https://smsplus.sslwireless.com/api/v3/send-sms';
        } elseif (empty($api_url)) {
            $api_url = 'https://smsplus.sslwireless.com/api/v3/send-sms';
        }

        $html = '<input type="text" id="pexpress_api_url" name="pexpress_options[sms_config][api_url]" value="' . esc_attr($api_url) . '" size="65" />';
        $html .= '<p class="description">' . esc_html__('Must input this field.', 'pexpress') . '</p>';

        echo $html;
    }

    /**
     * Render API username field
     */
    public function render_api_username_field()
    {
        $options = get_option('pexpress_options', array());
        $api_username = isset($options['sms_config']['api_username']) ? esc_attr($options['sms_config']['api_username']) : '';

        $html = '<input type="text" id="pexpress_api_username" name="pexpress_options[sms_config][api_username]" value="' . $api_username . '" size="45" placeholder="' . esc_attr__('Only use for ISMS Platform', 'pexpress') . '" />';
        $html .= '<p class="description">' . esc_html__('API User (Only for ISMS Platform).', 'pexpress') . '</p>';

        echo $html;
    }

    /**
     * Render API password field
     */
    public function render_api_password_field()
    {
        $options = get_option('pexpress_options', array());
        $api_password = isset($options['sms_config']['api_password']) ? esc_attr($options['sms_config']['api_password']) : '';

        $html = '<input type="password" id="pexpress_api_password" name="pexpress_options[sms_config][api_password]" value="' . $api_password . '" size="45" placeholder="' . esc_attr__('Only use for ISMS Platform', 'pexpress') . '" />';
        $html .= '<p class="description">' . esc_html__('API Password (Only for ISMS Platform).', 'pexpress') . '</p>';

        echo $html;
    }

    /**
     * Render API SID field
     */
    public function render_api_sid_field()
    {
        $options = get_option('pexpress_options', array());
        $api_sid = isset($options['sms_config']['api_sid']) ? esc_attr($options['sms_config']['api_sid']) : 'POLAROTP';

        $html = '<input type="text" id="pexpress_api_sid" name="pexpress_options[sms_config][api_sid]" value="' . $api_sid . '" size="45" placeholder="' . esc_attr__('Only use for ISMS & ISMS Plus Platform', 'pexpress') . '" />';
        $html .= '<p class="description">' . esc_html__('SID/Stakeholder (Provided from Ethertech WOOTP).', 'pexpress') . '</p>';

        echo $html;
    }

    /**
     * Render enable unicode field
     */
    public function render_enable_unicode_field()
    {
        $options = get_option('pexpress_options', array());
        $enable_unicode = isset($options['sms_config']['enable_unicode']) ? $options['sms_config']['enable_unicode'] : '';

        $html = '<input type="checkbox" id="pexpress_enable_unicode" name="pexpress_options[sms_config][enable_unicode]" value="1"' . checked(1, $enable_unicode, false) . '/>';
        $html .= '<label for="pexpress_enable_unicode">' . esc_html__('Check to enable Unicode/Bangla SMS (Only for ISMS Platform).', 'pexpress') . '</label>';

        echo $html;
    }

    /**
     * SMS templates section callback
     */
    public function sms_templates_section_callback()
    {
        echo '<p>' . esc_html__('Configure SMS templates for order notifications. Use placeholders: {{order_id}}, {{customer_name}}, {{order_total}}, {{order_date}}', 'pexpress') . '</p>';
    }

    /**
     * Render template enable field
     */
    public function render_template_enable_field($args)
    {
        $options = get_option('pexpress_options', array());
        $template_key = $args['template_key'];
        $enabled = isset($options['sms_templates'][$template_key]['enabled']) ? $options['sms_templates'][$template_key]['enabled'] : '';

        $html = '<input type="checkbox" id="pexpress_' . esc_attr($template_key) . '_enable" name="pexpress_options[sms_templates][' . esc_attr($template_key) . '][enabled]" value="1"' . checked(1, $enabled, false) . '/>';
        $html .= '<label for="pexpress_' . esc_attr($template_key) . '_enable">' . esc_html__('Enable this notification', 'pexpress') . '</label>';

        echo $html;
    }

    /**
     * Render template field
     */
    public function render_template_field($args)
    {
        $options = get_option('pexpress_options', array());
        $template_key = $args['template_key'];

        // Default templates
        $default_templates = array(
            'order_confirmed' => __('Your order #{{order_id}} has been confirmed. Thank you for your order!', 'pexpress'),
            'order_proceeded' => __('Your order #{{order_id}} is now being processed. We will update you soon.', 'pexpress'),
            'out_for_delivery' => __('Your order #{{order_id}} is out for delivery. You will receive it shortly.', 'pexpress'),
            'order_completed' => __('Your order #{{order_id}} has been completed. Thank you for choosing us!', 'pexpress'),
        );

        $template = isset($options['sms_templates'][$template_key]['template']) ? $options['sms_templates'][$template_key]['template'] : (isset($default_templates[$template_key]) ? $default_templates[$template_key] : '');

        $html = '<textarea id="pexpress_' . esc_attr($template_key) . '_template" name="pexpress_options[sms_templates][' . esc_attr($template_key) . '][template]" rows="3" cols="80" class="large-text">' . esc_textarea($template) . '</textarea>';
        $html .= '<p class="description">' . esc_html__('Available placeholders: {{order_id}}, {{customer_name}}, {{order_total}}, {{order_date}}', 'pexpress') . '</p>';

        echo $html;
    }

    /**
     * Email section callback
     */
    public function email_section_callback()
    {
        echo '<hr>';
        echo '<p>' . esc_html__('Configure email notification settings.', 'pexpress') . '</p>';
    }

    /**
     * Email templates section callback
     */
    public function email_templates_section_callback()
    {
        echo '<p>' . esc_html__('Configure email templates for order notifications. Use placeholders: {{order_id}}, {{customer_name}}, {{order_total}}, {{order_date}}', 'pexpress') . '</p>';
    }

    /**
     * Render email template enable field
     */
    public function render_email_template_enable_field($args)
    {
        $options = get_option('pexpress_options', array());
        $template_key = $args['template_key'];
        $enabled = isset($options['email_templates'][$template_key]['enabled']) ? $options['email_templates'][$template_key]['enabled'] : '';

        $html = '<input type="checkbox" id="pexpress_email_' . esc_attr($template_key) . '_enable" name="pexpress_options[email_templates][' . esc_attr($template_key) . '][enabled]" value="1"' . checked(1, $enabled, false) . '/>';
        $html .= '<label for="pexpress_email_' . esc_attr($template_key) . '_enable">' . esc_html__('Enable this notification', 'pexpress') . '</label>';

        echo $html;
    }

    /**
     * Render email template field
     */
    public function render_email_template_field($args)
    {
        $options = get_option('pexpress_options', array());
        $template_key = $args['template_key'];

        // Default templates
        $default_templates = array(
            'order_confirmed' => __('Your order #{{order_id}} has been confirmed. Thank you for your order!', 'pexpress'),
            'order_proceeded' => __('Your order #{{order_id}} is now being processed. We will update you soon.', 'pexpress'),
            'out_for_delivery' => __('Your order #{{order_id}} is out for delivery. You will receive it shortly.', 'pexpress'),
            'order_completed' => __('Your order #{{order_id}} has been completed. Thank you for choosing us!', 'pexpress'),
        );

        $template = isset($options['email_templates'][$template_key]['template']) ? $options['email_templates'][$template_key]['template'] : (isset($default_templates[$template_key]) ? $default_templates[$template_key] : '');

        $html = '<textarea id="pexpress_email_' . esc_attr($template_key) . '_template" name="pexpress_options[email_templates][' . esc_attr($template_key) . '][template]" rows="3" cols="80" class="large-text">' . esc_textarea($template) . '</textarea>';
        $html .= '<p class="description">' . esc_html__('Available placeholders: {{order_id}}, {{customer_name}}, {{order_total}}, {{order_date}}', 'pexpress') . '</p>';

        echo $html;
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

        // Handle nested option keys (e.g., 'email_config.from_name')
        if (strpos($args['option_key'], '.') !== false) {
            $keys = explode('.', $args['option_key']);
            $value = $options;
            foreach ($keys as $key) {
                $value = isset($value[$key]) ? $value[$key] : '';
            }
            $value = esc_attr($value);
            $name = 'pexpress_options[' . implode('][', $keys) . ']';
        } else {
            $value = isset($options[$args['option_key']]) ? esc_attr($options[$args['option_key']]) : '';
            $name = 'pexpress_options[' . esc_attr($args['option_key']) . ']';
        }

        echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="' . $name . '" value="' . $value . '" class="regular-text" />';
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

        // Handle nested option keys (e.g., 'email_config.enable_email')
        if (strpos($args['option_key'], '.') !== false) {
            $keys = explode('.', $args['option_key']);
            $value = $options;
            foreach ($keys as $key) {
                $value = isset($value[$key]) ? $value[$key] : '';
            }
            $checked = !empty($value) ? 'checked' : '';
            $name = 'pexpress_options[' . implode('][', $keys) . ']';
        } else {
            $checked = !empty($options[$args['option_key']]) ? 'checked' : '';
            $name = 'pexpress_options[' . esc_attr($args['option_key']) . ']';
        }

        echo '<input type="checkbox" id="' . esc_attr($args['label_for']) . '" name="' . $name . '" value="1" ' . $checked . ' />';
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
            'polar_delivery' => __('Can view and update delivery status for assigned orders (SR)', 'pexpress'),
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
            'polar_delivery' => __('Polar SR', 'pexpress'),
            'polar_fridge' => __('Polar Fridge Provider', 'pexpress'),
            'polar_distributor' => __('Polar Product Provider', 'pexpress'),
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
