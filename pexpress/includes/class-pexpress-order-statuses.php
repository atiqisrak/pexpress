<?php

/**
 * Custom WooCommerce Order Statuses
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom order statuses
 */
class PExpress_Order_Statuses
{

    /**
     * Initialize order statuses
     */
    public static function init()
    {
        add_action('init', array(__CLASS__, 'register_order_statuses'));
        add_filter('wc_order_statuses', array(__CLASS__, 'add_order_statuses_to_dropdown'));
    }

    /**
     * Register custom order statuses
     */
    public static function register_order_statuses()
    {
        // Assigned
        register_post_status('wc-polar-assigned', array(
            'label'                     => _x('Assigned', 'Order status', 'pexpress'),
            'public'                    => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Assigned <span class="count">(%s)</span>', 'Assigned <span class="count">(%s)</span>', 'pexpress'),
        ));

        // Out for Delivery
        register_post_status('wc-polar-out', array(
            'label'                     => _x('Out for Delivery', 'Order status', 'pexpress'),
            'public'                    => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Out for Delivery <span class="count">(%s)</span>', 'Out for Delivery <span class="count">(%s)</span>', 'pexpress'),
        ));

        // Ice-cream Delivered
        register_post_status('wc-polar-delivered', array(
            'label'                     => _x('Ice-cream Delivered', 'Order status', 'pexpress'),
            'public'                    => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Ice-cream Delivered <span class="count">(%s)</span>', 'Ice-cream Delivered <span class="count">(%s)</span>', 'pexpress'),
        ));

        // Fridge Collected
        register_post_status('wc-polar-fridge-back', array(
            'label'                     => _x('Fridge Collected', 'Order status', 'pexpress'),
            'public'                    => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Fridge Collected <span class="count">(%s)</span>', 'Fridge Collected <span class="count">(%s)</span>', 'pexpress'),
        ));
    }

    /**
     * Add custom statuses to WooCommerce order status dropdown
     *
     * @param array $order_statuses Existing order statuses.
     * @return array
     */
    public static function add_order_statuses_to_dropdown($order_statuses)
    {
        $new_order_statuses = array();

        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;

            // Add custom statuses after 'processing'
            if ('wc-processing' === $key) {
                $new_order_statuses['wc-polar-assigned']    = _x('Assigned', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-out']        = _x('Out for Delivery', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-delivered']  = _x('Ice-cream Delivered', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-fridge-back'] = _x('Fridge Collected', 'Order status', 'pexpress');
            }
        }

        return $new_order_statuses;
    }
}
