<?php

/**
 * Core plugin functionality
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core plugin class
 */
class PExpress_Core
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize core functionality
     */
    private function init()
    {
        // Initialize order statuses
        PExpress_Order_Statuses::init();
    }

    /**
     * Get order meta value
     *
     * @param int    $order_id Order ID.
     * @param string $key      Meta key.
     * @param bool   $single   Whether to return single value.
     * @return mixed
     */
    public static function get_order_meta($order_id, $key, $single = true)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        return $order->get_meta($key, $single);
    }

    /**
     * Update order meta value
     *
     * @param int    $order_id Order ID.
     * @param string $key       Meta key.
     * @param mixed  $value     Meta value.
     * @return int|bool Meta ID on success, false on failure.
     */
    public static function update_order_meta($order_id, $key, $value)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        $order->update_meta_data($key, $value);
        return $order->save();
    }

    /**
     * Get assigned delivery user ID for an order
     *
     * @param int $order_id Order ID.
     * @return int|false
     */
    public static function get_delivery_user_id($order_id)
    {
        return (int) self::get_order_meta($order_id, '_polar_delivery_user_id');
    }

    /**
     * Get assigned fridge user ID for an order
     *
     * @param int $order_id Order ID.
     * @return int|false
     */
    public static function get_fridge_user_id($order_id)
    {
        return (int) self::get_order_meta($order_id, '_polar_fridge_user_id');
    }

    /**
     * Get assigned distributor user ID for an order
     *
     * @param int $order_id Order ID.
     * @return int|false
     */
    public static function get_distributor_user_id($order_id)
    {
        return (int) self::get_order_meta($order_id, '_polar_distributor_user_id');
    }

    /**
     * Get meeting type (meet_point or delivery_location)
     *
     * @param int $order_id Order ID.
     * @return string
     */
    public static function get_meeting_type($order_id)
    {
        $meeting_type = self::get_order_meta($order_id, '_polar_meeting_type');
        return $meeting_type ?: 'meet_point';
    }

    /**
     * Get meeting location text
     *
     * @param int $order_id Order ID.
     * @return string
     */
    public static function get_meeting_location($order_id)
    {
        return (string) self::get_order_meta($order_id, '_polar_meeting_location');
    }

    /**
     * Get scheduled meeting datetime
     *
     * @param int $order_id Order ID.
     * @return string
     */
    public static function get_meeting_datetime($order_id)
    {
        return (string) self::get_order_meta($order_id, '_polar_meeting_datetime');
    }

    /**
     * Get fridge asset identifier
     *
     * @param int $order_id Order ID.
     * @return string
     */
    public static function get_fridge_asset_id($order_id)
    {
        return (string) self::get_order_meta($order_id, '_polar_fridge_asset_id');
    }

    /**
     * Get instructions saved for a role
     *
     * @param int    $order_id Order ID.
     * @param string $role_key Role key (delivery|fridge|distributor).
     * @return string
     */
    public static function get_role_instructions($order_id, $role_key)
    {
        $meta_key = sprintf('_polar_instructions_%s', sanitize_key($role_key));
        return (string) self::get_order_meta($order_id, $meta_key);
    }

    /**
     * Get per-role status for an order
     *
     * @param int    $order_id Order ID.
     * @param string $role_key Role key (agency|delivery|fridge|distributor).
     * @return string Status value, defaults to 'pending' if not set.
     */
    public static function get_role_status($order_id, $role_key)
    {
        $meta_key = sprintf('_polar_status_%s', sanitize_key($role_key));
        $status = self::get_order_meta($order_id, $meta_key);
        return $status ?: 'pending';
    }

    /**
     * Update per-role status for an order
     *
     * @param int    $order_id Order ID.
     * @param string $role_key Role key (agency|delivery|fridge|distributor).
     * @param string $status   Status value.
     * @return bool|int Meta ID on success, false on failure.
     */
    public static function update_role_status($order_id, $role_key, $status)
    {
        $meta_key = sprintf('_polar_status_%s', sanitize_key($role_key));
        return self::update_order_meta($order_id, $meta_key, sanitize_text_field($status));
    }

    /**
     * Get all role statuses for an order
     *
     * @param int $order_id Order ID.
     * @return array Associative array of role_key => status.
     */
    public static function get_all_role_statuses($order_id)
    {
        $roles = array('agency', 'delivery', 'fridge', 'distributor');
        $statuses = array();
        foreach ($roles as $role) {
            $statuses[$role] = self::get_role_status($order_id, $role);
        }
        return $statuses;
    }

    /**
     * Get role status history for an order
     *
     * @param int    $order_id Order ID.
     * @param string $role_key Role key (agency|delivery|fridge|distributor).
     * @return array Array of status history entries.
     */
    public static function get_role_status_history($order_id, $role_key)
    {
        $meta_key = sprintf('_polar_status_history_%s', sanitize_key($role_key));
        $history = self::get_order_meta($order_id, $meta_key, false);
        if (!is_array($history)) {
            $history = array();
        }
        return $history;
    }

    /**
     * Add entry to role status history
     *
     * @param int    $order_id Order ID.
     * @param string $role_key Role key (agency|delivery|fridge|distributor).
     * @param string $status   Status value.
     * @param string $note     Optional note.
     * @param int    $user_id  Optional user ID (defaults to current user).
     * @return bool|int Meta ID on success, false on failure.
     */
    public static function add_role_status_history($order_id, $role_key, $status, $note = '', $user_id = 0)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $user = get_userdata($user_id);
        $user_name = $user ? $user->display_name : __('System', 'pexpress');

        $history = self::get_role_status_history($order_id, $role_key);
        $history[] = array(
            'status' => sanitize_text_field($status),
            'note' => sanitize_textarea_field($note),
            'user_id' => absint($user_id),
            'user_name' => sanitize_text_field($user_name),
            'timestamp' => current_time('mysql'),
        );

        $meta_key = sprintf('_polar_status_history_%s', sanitize_key($role_key));
        return self::update_order_meta($order_id, $meta_key, $history);
    }

    /**
     * Get orders assigned to a user
     *
     * @param int    $user_id User ID.
     * @param string $role    Role type (delivery, fridge, distributor).
     * @return array
     */
    public static function get_assigned_orders($user_id, $role = 'delivery')
    {
        $meta_key = '_polar_' . sanitize_key($role) . '_user_id';

        $args = array(
            'status' => 'any',
            'limit'  => -1,
            'meta_key' => $meta_key,
            'meta_value' => $user_id,
        );

        return wc_get_orders($args);
    }

    /**
     * Check if order needs assignment
     *
     * @param int $order_id Order ID.
     * @return bool
     */
    public static function order_needs_assignment($order_id)
    {
        return 'yes' === self::get_order_meta($order_id, '_polar_needs_assignment');
    }

    /**
     * Get billing name for an order
     *
     * @param WC_Order|int $order Order object or order ID.
     * @return string
     */
    public static function get_billing_name($order)
    {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }

        if (!$order || !is_a($order, 'WC_Order')) {
            return '';
        }

        $first_name = $order->get_billing_first_name() ?: '';
        $last_name = $order->get_billing_last_name() ?: '';

        $name = trim($first_name . ' ' . $last_name);

        // Fallback to customer name if billing name is empty
        if (empty($name)) {
            $customer_id = $order->get_customer_id();
            if ($customer_id) {
                $customer = new WC_Customer($customer_id);
                $display_name = $customer->get_display_name();
                $name = $display_name ?: '';
            }
        }

        // Final fallback
        if (empty($name)) {
            $company = $order->get_billing_company();
            $name = $company ?: __('Guest', 'pexpress');
        }

        return $name ?: __('Guest', 'pexpress');
    }
}
