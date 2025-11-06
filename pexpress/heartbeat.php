<?php

/**
 * WordPress Heartbeat API Integration
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Heartbeat API Handler
 */
class PExpress_Heartbeat
{

    /**
     * Initialize heartbeat integration
     */
    public static function init()
    {
        // Send data with heartbeat
        add_filter('heartbeat_send', array(__CLASS__, 'heartbeat_send'), 10, 2);

        // Receive heartbeat data
        add_filter('heartbeat_received', array(__CLASS__, 'heartbeat_received'), 10, 2);

        // AJAX endpoint for fetching tasks
        add_action('wp_ajax_polar_heartbeat', array(__CLASS__, 'heartbeat_callback'));
    }

    /**
     * Send data with heartbeat
     *
     * @param array $response Heartbeat response data.
     * @param array $data     Heartbeat sent data.
     * @return array
     */
    public static function heartbeat_send($response, $data)
    {
        // Only send data if user is logged in
        if (!is_user_logged_in()) {
            return $response;
        }

        $user_id = get_current_user_id();
        $user    = wp_get_current_user();

        // Get tasks based on user role
        $tasks = self::get_user_tasks($user_id, $user->roles);

        if (!empty($tasks)) {
            $response['polar_tasks'] = $tasks;
        }

        return $response;
    }

    /**
     * Receive heartbeat data
     *
     * @param array $response Heartbeat response data.
     * @param array $data     Heartbeat sent data.
     * @return array
     */
    public static function heartbeat_received($response, $data)
    {
        // Handle any incoming heartbeat data if needed
        return $response;
    }

    /**
     * AJAX callback for heartbeat
     */
    public static function heartbeat_callback()
    {
        check_ajax_referer('heartbeat-nonce', '_nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in.', 'pexpress')));
        }

        $user = wp_get_current_user();
        $tasks = self::get_user_tasks($user_id, $user->roles);

        wp_send_json_success(array('tasks' => $tasks));
    }

    /**
     * Get tasks for a user based on their role
     *
     * @param int   $user_id User ID.
     * @param array $roles   User roles.
     * @return array
     */
    private static function get_user_tasks($user_id, $roles)
    {
        $tasks = array();

        // Delivery person tasks
        if (in_array('polar_delivery', $roles, true)) {
            $orders = PExpress_Core::get_assigned_orders($user_id, 'delivery');
            foreach ($orders as $post) {
                $order = wc_get_order($post->ID);
                if ($order) {
                    $tasks[] = array(
                        'id'     => $post->ID,
                        'type'   => 'delivery',
                        'status' => $order->get_status(),
                        'title'  => sprintf(__('Order #%d', 'pexpress'), $post->ID),
                    );
                }
            }
        }

        // Fridge provider tasks
        if (in_array('polar_fridge', $roles, true)) {
            $orders = PExpress_Core::get_assigned_orders($user_id, 'fridge');
            foreach ($orders as $post) {
                $order = wc_get_order($post->ID);
                if ($order) {
                    $tasks[] = array(
                        'id'     => $post->ID,
                        'type'   => 'fridge',
                        'status' => $order->get_status(),
                        'title'  => sprintf(__('Order #%d', 'pexpress'), $post->ID),
                    );
                }
            }
        }

        // Distributor tasks
        if (in_array('polar_distributor', $roles, true)) {
            $orders = PExpress_Core::get_assigned_orders($user_id, 'distributor');
            foreach ($orders as $post) {
                $order = wc_get_order($post->ID);
                if ($order) {
                    $tasks[] = array(
                        'id'     => $post->ID,
                        'type'   => 'distributor',
                        'status' => $order->get_status(),
                        'title'  => sprintf(__('Order #%d', 'pexpress'), $post->ID),
                    );
                }
            }
        }

        // HR tasks (orders needing assignment)
        if (in_array('polar_hr', $roles, true)) {
            $args = array(
                'post_type'      => 'shop_order',
                'post_status'    => 'any',
                'posts_per_page' => 10,
                'meta_query'     => array(
                    array(
                        'key'   => '_polar_needs_assignment',
                        'value' => 'yes',
                        'compare' => '=',
                    ),
                ),
            );

            $query = new WP_Query($args);
            foreach ($query->posts as $post) {
                $tasks[] = array(
                    'id'     => $post->ID,
                    'type'   => 'hr',
                    'status' => get_post_status($post->ID),
                    'title'  => sprintf(__('Order #%d', 'pexpress'), $post->ID),
                );
            }
            wp_reset_postdata();
        }

        return $tasks;
    }
}
