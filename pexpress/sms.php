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
 * Send SMS via SSLCommerz
 *
 * @param string $phone   Phone number.
 * @param string $message Message text.
 * @return bool|WP_Error
 */
function polar_send_sms($phone, $message)
{
    // Get SMS credentials from options
    $sms_user = get_option('polar_sms_user', '');
    $sms_pass = get_option('polar_sms_pass', '');
    $sms_sid  = get_option('polar_sms_sid', 'POLARICE');

    // Validate credentials
    if (empty($sms_user) || empty($sms_pass)) {
        return new WP_Error('sms_credentials_missing', __('SMS credentials are not configured.', 'pexpress'));
    }

    // Sanitize phone number (remove non-numeric characters except +)
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    // Validate phone number
    if (empty($phone)) {
        return new WP_Error('invalid_phone', __('Invalid phone number.', 'pexpress'));
    }

    // Prepare SMS data
    $data = array(
        'user'   => sanitize_text_field($sms_user),
        'pass'   => sanitize_text_field($sms_pass),
        'sid'    => sanitize_text_field($sms_sid),
        'msisdn' => sanitize_text_field($phone),
        'sms'    => sanitize_text_field($message),
        'csmsid' => time(),
    );

    // Send SMS request
    $url = 'https://sms.sslwireless.com/pushapi/dynamic/server.php';

    $response = wp_remote_post(
        $url,
        array(
            'body'    => $data,
            'timeout' => 30,
        )
    );

    // Check for errors
    if (is_wp_error($response)) {
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    // Log response for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Polar Express SMS Response: ' . $response_body);
    }

    // SSLCommerz returns "1701" on success
    if (200 === $response_code && false !== strpos($response_body, '1701')) {
        return true;
    }

    return new WP_Error('sms_send_failed', __('Failed to send SMS.', 'pexpress'), $response_body);
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
