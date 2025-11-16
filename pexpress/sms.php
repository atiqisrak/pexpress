<?php

/**
 * SSLCommerz SMS Integration
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Send SMS via SSL Care API
 *
 * @param string $phone   Phone number.
 * @param string $message Message text.
 * @return bool|WP_Error
 */
function polar_send_sms($phone, $message)
{
    // Check if SMS is enabled
    $options = get_option('pexpress_options', array());
    $settings = isset($options['sms_config']) ? $options['sms_config'] : array();

    if (empty($settings['enable_plugin'])) {
        return new WP_Error('sms_disabled', __('SMS notifications are disabled.', 'pexpress'));
    }

    // Sanitize phone number (remove non-numeric characters except +)
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    // Ensure phone number starts with country code if not present
    if (!empty($phone) && !preg_match('/^\+?88/', $phone) && preg_match('/^01/', $phone)) {
        $phone = '88' . $phone;
    }

    // Validate phone number
    if (empty($phone)) {
        return new WP_Error('invalid_phone', __('Invalid phone number.', 'pexpress'));
    }

    // Use SMS API class
    $params = PExpress_Sms_Api::set_get_parameter($phone, $message);

    if ($params === false) {
        return new WP_Error('sms_config_error', __('SMS API configuration error. Please check your settings.', 'pexpress'));
    }

    $response = PExpress_Sms_Api::call_to_get_api($params);

    if (is_wp_error($response)) {
        return $response;
    }

    // Log response for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Polar Express SMS Response: ' . print_r($response, true));
    }

    // Check response
    if (isset($response[0]) && isset($response[0]['code'])) {
        $response_code = $response[0]['code'];
        if ($response_code == 200) {
            return true;
        }
    }

    // For ISMS, check if response contains success indicator
    if (isset($response[1]) && (false !== strpos($response[1], '1701') || false !== strpos($response[1], 'SUCCESS'))) {
        return true;
    }

    return new WP_Error('sms_send_failed', __('Failed to send SMS.', 'pexpress'), $response);
}

/**
 * Send SMS notification for order assignment
 *
 * @param int    $order_id Order ID.
 * @param string $role     Role type (delivery, fridge, distributor).
 * @param int    $user_id  Assigned user ID.
 * @return bool|WP_Error
 */
function polar_send_assignment_sms($order_id, $role, $user_id)
{
    if (!function_exists('wc_get_order')) {
        return new WP_Error('woocommerce_not_available', __('WooCommerce is not available.', 'pexpress'));
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_Error('order_not_found', __('Order not found.', 'pexpress'));
    }

    // Get customer phone
    $phone = $order->get_billing_phone();
    if (empty($phone)) {
        return new WP_Error('phone_not_available', __('Customer phone is not available.', 'pexpress'));
    }

    // Get assigned user
    $assigned_user = get_userdata($user_id);
    if (!$assigned_user) {
        return new WP_Error('user_not_found', __('Assigned user not found.', 'pexpress'));
    }

    // Build message based on role
    $role_names = array(
        'delivery'   => __('delivery person', 'pexpress'),
        'fridge'     => __('fridge provider', 'pexpress'),
        'distributor' => __('distributor', 'pexpress'),
    );

    $role_name = isset($role_names[$role]) ? $role_names[$role] : $role;
    $message = sprintf(
        __('Your Polar ice-cream order #%d has been assigned to %s. You will receive delivery updates shortly.', 'pexpress'),
        $order_id,
        $assigned_user->display_name
    );

    return polar_send_sms($phone, $message);
}

/**
 * Process SMS template with placeholders
 *
 * @param string $template_key Template key.
 * @param array  $data         Order data.
 * @return string Processed template
 */
function polar_process_sms_template($template_key, $data)
{
    $options = get_option('pexpress_options', array());
    $sms_templates = isset($options['sms_templates']) ? $options['sms_templates'] : array();

    // Default templates
    $default_templates = array(
        'order_confirmed' => __('Your order #{{order_id}} has been confirmed. Thank you for your order!', 'pexpress'),
        'order_proceeded' => __('Your order #{{order_id}} is now being processed. We will update you soon.', 'pexpress'),
        'out_for_delivery' => __('Your order #{{order_id}} is out for delivery. You will receive it shortly.', 'pexpress'),
        'order_completed' => __('Your order #{{order_id}} has been completed. Thank you for choosing us!', 'pexpress'),
    );

    if (isset($sms_templates[$template_key]['template'])) {
        $template = $sms_templates[$template_key]['template'];
    } elseif (isset($default_templates[$template_key])) {
        $template = $default_templates[$template_key];
    } else {
        return '';
    }

    $placeholders = array(
        '{{order_id}}' => isset($data['order_id']) ? $data['order_id'] : '',
        '{{customer_name}}' => isset($data['customer_name']) ? $data['customer_name'] : '',
        '{{order_total}}' => isset($data['order_total']) ? $data['order_total'] : '',
        '{{order_date}}' => isset($data['order_date']) ? $data['order_date'] : '',
    );

    $message = $template;
    foreach ($placeholders as $placeholder => $value) {
        $message = str_replace($placeholder, $value, $message);
    }

    return $message;
}

/**
 * Check if SMS template is enabled
 *
 * @param string $template_key Template key.
 * @return bool
 */
function polar_is_sms_template_enabled($template_key)
{
    $options = get_option('pexpress_options', array());
    $sms_templates = isset($options['sms_templates']) ? $options['sms_templates'] : array();

    return !empty($sms_templates[$template_key]['enabled']);
}

/**
 * Send order notification (SMS and Email)
 *
 * @param int    $order_id     Order ID.
 * @param string $template_key Template key.
 * @return array Results array with 'sms' and 'email' keys
 */
function polar_send_order_notification($order_id, $template_key)
{
    if (!function_exists('wc_get_order')) {
        return array('sms' => false, 'email' => false);
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return array('sms' => false, 'email' => false);
    }

    // Prepare data
    $customer_name = PExpress_Core::get_billing_name($order);
    $order_total = $order->get_formatted_order_total();
    $order_date = $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format')) : '';

    $data = array(
        'order_id' => $order_id,
        'customer_name' => $customer_name,
        'order_total' => $order_total,
        'order_date' => $order_date,
    );

    $results = array('sms' => false, 'email' => false);

    // Send SMS
    if (polar_is_sms_template_enabled($template_key)) {
        $phone = $order->get_billing_phone();
        if (!empty($phone)) {
            $message = polar_process_sms_template($template_key, $data);
            if (!empty($message)) {
                $results['sms'] = polar_send_sms($phone, $message);
            }
        }
    }

    // Send Email
    if (class_exists('PExpress_Email') && PExpress_Email::is_template_enabled($template_key)) {
        $results['email'] = PExpress_Email::send_notification($template_key, $data);
    }

    return $results;
}
