<?php

/**
 * Role and Permission Management
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create custom roles for Polar Express
 */
function polar_create_roles()
{
    // 1. Customer Support
    add_role(
        'polar_support',
        'Polar Support',
        array(
            'read'                   => true,
            'edit_shop_orders'      => true,
            'read_shop_order'        => true,
            'publish_shop_orders'   => true,
            'delete_shop_orders'     => true,
        )
    );

    // 2. Agency (formerly HR - the boss)
    add_role(
        'polar_hr',
        'Polar Agency',
        array(
            'read'                 => true,
            'manage_woocommerce'   => true,
            'edit_users'           => true,
            'edit_shop_orders'     => true,
            'read_shop_order'      => true,
            'publish_shop_orders' => true,
        )
    );

    // 3. HR (formerly Delivery Person)
    add_role(
        'polar_delivery',
        'Polar HR',
        array(
            'read' => true,
        )
    );

    // 4. FRIDGE PROVIDER
    add_role(
        'polar_fridge',
        'Polar Fridge Provider',
        array(
            'read' => true,
        )
    );

    // 5. PRODUCT DISTRIBUTOR
    add_role(
        'polar_distributor',
        'Polar Distributor',
        array(
            'read' => true,
        )
    );
}

/**
 * Remove custom roles on plugin deactivation/uninstall
 */
function polar_remove_roles()
{
    remove_role('polar_support');
    remove_role('polar_hr');
    remove_role('polar_delivery');
    remove_role('polar_fridge');
    remove_role('polar_distributor');
}

// Note: Roles are registered via the main plugin activation hook
