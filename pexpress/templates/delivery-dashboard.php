<?php

/**
 * Delivery Person Dashboard Template
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap polar-dashboard polar-delivery-dashboard">
    <h1><?php esc_html_e('Delivery Dashboard', 'pexpress'); ?></h1>

    <div class="polar-stats">
        <div class="polar-stat-card">
            <h3><?php echo count($assigned_orders); ?></h3>
            <p><?php esc_html_e('Total Assigned Orders', 'pexpress'); ?></p>
        </div>
        <div class="polar-stat-card">
            <h3><?php echo count($out_orders); ?></h3>
            <p><?php esc_html_e('Out for Delivery', 'pexpress'); ?></p>
        </div>
        <div class="polar-stat-card">
            <h3><?php echo count($delivered_orders); ?></h3>
            <p><?php esc_html_e('Delivered', 'pexpress'); ?></p>
        </div>
    </div>

    <div class="polar-tasks-section">
        <h2><?php esc_html_e('My Delivery Tasks', 'pexpress'); ?></h2>

        <div class="polar-tabs">
            <button class="polar-tab active" data-tab="assigned"><?php esc_html_e('Assigned', 'pexpress'); ?></button>
            <button class="polar-tab" data-tab="out"><?php esc_html_e('Out for Delivery', 'pexpress'); ?></button>
            <button class="polar-tab" data-tab="delivered"><?php esc_html_e('Delivered', 'pexpress'); ?></button>
        </div>

        <div class="polar-tab-content active" id="tab-assigned">
            <div class="polar-tasks-list" id="polar-delivery-tasks">
                <?php
                $assigned_list = array_filter($assigned_orders, function ($order) {
                    $status = $order->get_status();
                    return $status === 'wc-polar-assigned' || $status === 'processing';
                });
                ?>
                <?php if (!empty($assigned_list)) : ?>
                    <?php foreach ($assigned_list as $order) :
                        $order_id = $order->get_id();
                        $order_status = $order->get_status();
                    ?>
                        <div class="polar-task-item" data-order-id="<?php echo esc_attr($order_id); ?>">
                            <div class="task-header">
                                <h4>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" target="_blank">
                                        <?php printf(esc_html__('Order #%d', 'pexpress'), $order_id); ?>
                                    </a>
                                </h4>
                                <span class="task-status status-<?php echo esc_attr($order_status); ?>">
                                    <?php echo esc_html(wc_get_order_status_name($order_status)); ?>
                                </span>
                            </div>
                            <div class="task-details">
                                <p><strong><?php esc_html_e('Customer:', 'pexpress'); ?></strong> <?php echo esc_html(PExpress_Core::get_billing_name($order)); ?></p>
                                <p><strong><?php esc_html_e('Address:', 'pexpress'); ?></strong> <?php echo wp_kses_post($order->get_formatted_billing_address()); ?></p>
                                <p><strong><?php esc_html_e('Phone:', 'pexpress'); ?></strong>
                                    <a href="tel:<?php echo esc_attr($order->get_billing_phone()); ?>">
                                        <?php echo esc_html($order->get_billing_phone()); ?>
                                    </a>
                                </p>
                                <p><strong><?php esc_html_e('Date:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_date_created()->date_i18n()); ?></p>
                                <p><strong><?php esc_html_e('Total:', 'pexpress'); ?></strong> <?php echo wp_kses_post($order->get_formatted_order_total()); ?></p>
                            </div>
                            <div class="task-actions">
                                <form class="polar-status-update-form" data-order-id="<?php echo esc_attr($order_id); ?>">
                                    <?php wp_nonce_field('polar_update_status_' . $order_id, 'polar_status_nonce'); ?>
                                    <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                                    <button type="submit" name="status" value="polar-out" class="polar-btn polar-btn-primary">
                                        <?php esc_html_e('Mark Out for Delivery', 'pexpress'); ?>
                                    </button>
                                    <span class="polar-update-loading" style="display:none;"><?php esc_html_e('Updating...', 'pexpress'); ?></span>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="polar-empty-state">
                        <p><?php esc_html_e('You have no assigned delivery tasks at this time.', 'pexpress'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="polar-tab-content" id="tab-out">
            <?php if (!empty($out_orders)) : ?>
                <?php foreach ($out_orders as $order) :
                    $order_id = $order->get_id();
                ?>
                    <div class="polar-task-item" data-order-id="<?php echo esc_attr($order_id); ?>">
                        <div class="task-header">
                            <h4>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" target="_blank">
                                    <?php printf(esc_html__('Order #%d', 'pexpress'), $order_id); ?>
                                </a>
                            </h4>
                            <span class="task-status status-polar-out"><?php esc_html_e('Out for Delivery', 'pexpress'); ?></span>
                        </div>
                        <div class="task-details">
                            <p><strong><?php esc_html_e('Customer:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_name()); ?></p>
                            <p><strong><?php esc_html_e('Address:', 'pexpress'); ?></strong> <?php echo wp_kses_post($order->get_formatted_billing_address()); ?></p>
                            <p><strong><?php esc_html_e('Phone:', 'pexpress'); ?></strong>
                                <a href="tel:<?php echo esc_attr($order->get_billing_phone()); ?>">
                                    <?php echo esc_html($order->get_billing_phone()); ?>
                                </a>
                            </p>
                        </div>
                        <div class="task-actions">
                            <form class="polar-status-update-form" data-order-id="<?php echo esc_attr($order_id); ?>">
                                <?php wp_nonce_field('polar_update_status_' . $order_id, 'polar_status_nonce'); ?>
                                <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                                <button type="submit" name="status" value="polar-delivered" class="polar-btn polar-btn-success">
                                    <?php esc_html_e('Mark Delivered', 'pexpress'); ?>
                                </button>
                                <span class="polar-update-loading" style="display:none;"><?php esc_html_e('Updating...', 'pexpress'); ?></span>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="polar-empty-state">
                    <p><?php esc_html_e('No orders out for delivery.', 'pexpress'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="polar-tab-content" id="tab-delivered">
            <?php if (!empty($delivered_orders)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Order ID', 'pexpress'); ?></th>
                            <th><?php esc_html_e('Customer', 'pexpress'); ?></th>
                            <th><?php esc_html_e('Delivery Date', 'pexpress'); ?></th>
                            <th><?php esc_html_e('Status', 'pexpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($delivered_orders as $order) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>">
                                        #<?php echo esc_html($order->get_id()); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html(PExpress_Core::get_billing_name($order)); ?></td>
                                <td><?php echo esc_html($order->get_date_modified()->date_i18n()); ?></td>
                                <td><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="polar-empty-state">
                    <p><?php esc_html_e('No delivered orders yet.', 'pexpress'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>