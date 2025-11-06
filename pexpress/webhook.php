<?php

/**
 * WooCommerce Webhook Handler
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle WooCommerce webhook callbacks
 */
class PExpress_Webhook
{

    /**
     * Initialize webhook handlers
     */
    public static function init()
    {
        // Handle webhook via AJAX (for nopriv access)
        add_action('wp_ajax_nopriv_polar_webhook', array(__CLASS__, 'handle_webhook'));
        add_action('wp_ajax_polar_webhook', array(__CLASS__, 'handle_webhook'));
    }

    /**
     * Handle incoming webhook
     */
    public static function handle_webhook()
    {
        // Get webhook payload
        $payload = file_get_contents('php://input');
        $data    = json_decode($payload, true);

        // Validate payload
        if (empty($data) || !isset($data['id'])) {
            wp_send_json_error(array('message' => 'Invalid webhook payload'));
            wp_die();
        }

        $order_id = absint($data['id']);
        $status   = isset($data['status']) ? sanitize_text_field($data['status']) : '';

        // Handle order creation
        if (isset($data['created_at']) && !empty($data['created_at'])) {
            self::handle_order_created($order_id, $data);
        }

        // Handle order status change
        if (!empty($status)) {
            self::handle_status_change($order_id, $status, $data);
        }

        // Return success
        wp_send_json_success(array('message' => 'Webhook processed'));
        wp_die();
    }

    /**
     * Handle order creation
     *
     * @param int   $order_id Order ID.
     * @param array $data     Webhook data.
     */
    private static function handle_order_created($order_id, $data)
    {
        // Mark order as needing assignment when status is processing
        if (isset($data['status']) && 'processing' === $data['status']) {
            PExpress_Core::update_order_meta($order_id, '_polar_needs_assignment', 'yes');
        }
    }

    /**
     * Handle order status change
     *
     * @param int    $order_id Order ID.
     * @param string $status   New status.
     * @param array  $data     Webhook data.
     */
    private static function handle_status_change($order_id, $status, $data)
    {
        // When status changes to processing, mark as needing assignment
        if ('processing' === $status) {
            PExpress_Core::update_order_meta($order_id, '_polar_needs_assignment', 'yes');
        }

        // When assigned, remove needs assignment flag
        if ('polar-assigned' === $status) {
            PExpress_Core::update_order_meta($order_id, '_polar_needs_assignment', 'no');
        }
    }
}
