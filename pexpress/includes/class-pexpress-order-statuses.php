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
        $statuses = array(
            'wc-polar-assigned'             => array(
                'label'       => _x('Assigned', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Assigned <span class="count">(%s)</span>', 'Assigned <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-distributor-prep'     => array(
                'label'       => _x('Distributor Preparing', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Distributor Preparing <span class="count">(%s)</span>', 'Distributor Preparing <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-out'                  => array(
                'label'       => _x('Out for Delivery', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Out for Delivery <span class="count">(%s)</span>', 'Out for Delivery <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-distributor-complete' => array(
                'label'       => _x('Distributor Handoff Complete', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Distributor Handoff Complete <span class="count">(%s)</span>', 'Distributor Handoff Complete <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-meet-point'           => array(
                'label'       => _x('Reached Meet Point', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Reached Meet Point <span class="count">(%s)</span>', 'Reached Meet Point <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-delivery-location'    => array(
                'label'       => _x('Reached Delivery Location', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Reached Delivery Location <span class="count">(%s)</span>', 'Reached Delivery Location <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-service-progress'     => array(
                'label'       => _x('Service In Progress', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Service In Progress <span class="count">(%s)</span>', 'Service In Progress <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-service-complete'     => array(
                'label'       => _x('Service Completed', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Service Completed <span class="count">(%s)</span>', 'Service Completed <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-delivered'            => array(
                'label'       => _x('Ice-cream Delivered', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Ice-cream Delivered <span class="count">(%s)</span>', 'Ice-cream Delivered <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-fridge-drop'          => array(
                'label'       => _x('Fridge Delivered On-site', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Fridge Delivered On-site <span class="count">(%s)</span>', 'Fridge Delivered On-site <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-fridge-back'          => array(
                'label'       => _x('Fridge Collected On-site', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Fridge Collected On-site <span class="count">(%s)</span>', 'Fridge Collected On-site <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-fridge-returned'      => array(
                'label'       => _x('Fridge Returned to Base', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Fridge Returned to Base <span class="count">(%s)</span>', 'Fridge Returned to Base <span class="count">(%s)</span>', 'pexpress'),
            ),
            'wc-polar-complete'             => array(
                'label'       => _x('Polar Service Complete', 'Order status', 'pexpress'),
                'label_count' => _n_noop('Polar Service Complete <span class="count">(%s)</span>', 'Polar Service Complete <span class="count">(%s)</span>', 'pexpress'),
            ),
        );

        foreach ($statuses as $status_key => $status_args) {
            register_post_status(
                $status_key,
                array(
                    'label'                     => $status_args['label'],
                    'public'                    => false,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => $status_args['label_count'],
                )
            );
        }
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
                $new_order_statuses['wc-polar-assigned']             = _x('Assigned', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-distributor-prep']     = _x('Distributor Preparing', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-out']                  = _x('Out for Delivery', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-distributor-complete'] = _x('Distributor Handoff Complete', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-meet-point']           = _x('Reached Meet Point', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-delivery-location']    = _x('Reached Delivery Location', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-service-progress']     = _x('Service In Progress', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-service-complete']     = _x('Service Completed', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-delivered']            = _x('Ice-cream Delivered', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-fridge-drop']          = _x('Fridge Delivered On-site', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-fridge-back']          = _x('Fridge Collected On-site', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-fridge-returned']      = _x('Fridge Returned to Base', 'Order status', 'pexpress');
                $new_order_statuses['wc-polar-complete']             = _x('Polar Service Complete', 'Order status', 'pexpress');
            }
        }

        return $new_order_statuses;
    }
}
