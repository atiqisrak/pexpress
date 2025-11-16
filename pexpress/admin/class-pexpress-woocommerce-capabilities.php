<?php

/**
 * WooCommerce Capabilities Manager
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Capabilities Manager class
 * Handles listing and organizing WooCommerce capabilities
 */
class PExpress_WooCommerce_Capabilities
{

    /**
     * Get all WooCommerce capability groups
     *
     * @return array Array of capability groups
     */
    public static function get_caps_groups()
    {
        $caps = array();

        // Core WooCommerce capabilities
        $caps['manage_woocommerce'] = array(
            'group' => 'woocommerce_core',
            'label' => __('Manage WooCommerce', 'pexpress'),
            'description' => __('Full access to WooCommerce settings and features', 'pexpress'),
        );

        $caps['view_woocommerce_reports'] = array(
            'group' => 'woocommerce_core',
            'label' => __('View WooCommerce Reports', 'pexpress'),
            'description' => __('Access to WooCommerce analytics and reports', 'pexpress'),
        );

        // Product capabilities
        $product_caps = array(
            'edit_product' => __('Edit Products', 'pexpress'),
            'read_product' => __('Read Products', 'pexpress'),
            'delete_product' => __('Delete Products', 'pexpress'),
            'edit_products' => __('Edit Products (bulk)', 'pexpress'),
            'edit_others_products' => __('Edit Others\' Products', 'pexpress'),
            'publish_products' => __('Publish Products', 'pexpress'),
            'read_private_products' => __('Read Private Products', 'pexpress'),
            'delete_products' => __('Delete Products (bulk)', 'pexpress'),
            'delete_private_products' => __('Delete Private Products', 'pexpress'),
            'delete_published_products' => __('Delete Published Products', 'pexpress'),
            'delete_others_products' => __('Delete Others\' Products', 'pexpress'),
            'edit_private_products' => __('Edit Private Products', 'pexpress'),
            'edit_published_products' => __('Edit Published Products', 'pexpress'),
            'manage_product_terms' => __('Manage Product Categories/Tags', 'pexpress'),
            'edit_product_terms' => __('Edit Product Categories/Tags', 'pexpress'),
            'delete_product_terms' => __('Delete Product Categories/Tags', 'pexpress'),
            'assign_product_terms' => __('Assign Product Categories/Tags', 'pexpress'),
        );

        foreach ($product_caps as $cap => $label) {
            $caps[$cap] = array(
                'group' => 'products',
                'label' => $label,
                'description' => '',
            );
        }

        // Shop Order capabilities
        $order_caps = array(
            'edit_shop_order' => __('Edit Orders', 'pexpress'),
            'read_shop_order' => __('Read Orders', 'pexpress'),
            'delete_shop_order' => __('Delete Orders', 'pexpress'),
            'edit_shop_orders' => __('Edit Orders (bulk)', 'pexpress'),
            'edit_others_shop_orders' => __('Edit Others\' Orders', 'pexpress'),
            'publish_shop_orders' => __('Publish Orders', 'pexpress'),
            'read_private_shop_orders' => __('Read Private Orders', 'pexpress'),
            'delete_shop_orders' => __('Delete Orders (bulk)', 'pexpress'),
            'delete_private_shop_orders' => __('Delete Private Orders', 'pexpress'),
            'delete_published_shop_orders' => __('Delete Published Orders', 'pexpress'),
            'delete_others_shop_orders' => __('Delete Others\' Orders', 'pexpress'),
            'edit_private_shop_orders' => __('Edit Private Orders', 'pexpress'),
            'edit_published_shop_orders' => __('Edit Published Orders', 'pexpress'),
        );

        foreach ($order_caps as $cap => $label) {
            $caps[$cap] = array(
                'group' => 'shop_orders',
                'label' => $label,
                'description' => '',
            );
        }

        // Shop Coupon capabilities
        $coupon_caps = array(
            'edit_shop_coupon' => __('Edit Coupons', 'pexpress'),
            'read_shop_coupon' => __('Read Coupons', 'pexpress'),
            'delete_shop_coupon' => __('Delete Coupons', 'pexpress'),
            'edit_shop_coupons' => __('Edit Coupons (bulk)', 'pexpress'),
            'edit_others_shop_coupons' => __('Edit Others\' Coupons', 'pexpress'),
            'publish_shop_coupons' => __('Publish Coupons', 'pexpress'),
            'read_private_shop_coupons' => __('Read Private Coupons', 'pexpress'),
            'delete_shop_coupons' => __('Delete Coupons (bulk)', 'pexpress'),
            'delete_private_shop_coupons' => __('Delete Private Coupons', 'pexpress'),
            'delete_published_shop_coupons' => __('Delete Published Coupons', 'pexpress'),
            'delete_others_shop_coupons' => __('Delete Others\' Coupons', 'pexpress'),
            'edit_private_shop_coupons' => __('Edit Private Coupons', 'pexpress'),
            'edit_published_shop_coupons' => __('Edit Published Coupons', 'pexpress'),
        );

        foreach ($coupon_caps as $cap => $label) {
            $caps[$cap] = array(
                'group' => 'shop_coupons',
                'label' => $label,
                'description' => '',
            );
        }

        return $caps;
    }

    /**
     * Get capability groups organized by category
     *
     * @return array Organized capability groups
     */
    public static function get_grouped_caps()
    {
        $all_caps = self::get_caps_groups();
        $grouped = array();

        foreach ($all_caps as $cap => $info) {
            $group = $info['group'];
            if (!isset($grouped[$group])) {
                $grouped[$group] = array(
                    'label' => self::get_group_label($group),
                    'caps' => array(),
                );
            }
            $grouped[$group]['caps'][$cap] = $info;
        }

        return $grouped;
    }

    /**
     * Get label for capability group
     *
     * @param string $group Group identifier
     * @return string Group label
     */
    public static function get_group_label($group)
    {
        $labels = array(
            'woocommerce_core' => __('WooCommerce Core', 'pexpress'),
            'products' => __('Products', 'pexpress'),
            'shop_orders' => __('Shop Orders', 'pexpress'),
            'shop_coupons' => __('Shop Coupons', 'pexpress'),
        );

        return isset($labels[$group]) ? $labels[$group] : ucfirst(str_replace('_', ' ', $group));
    }

    /**
     * Get all WooCommerce capabilities as flat array
     *
     * @return array Array of capability keys
     */
    public static function get_all_caps()
    {
        return array_keys(self::get_caps_groups());
    }
}
