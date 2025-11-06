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

$user_id = get_current_user_id();
$assigned_orders = PExpress_Core::get_assigned_orders($user_id, 'delivery');
?>

<div class="polar-dashboard polar-delivery-dashboard">
    <h2><?php esc_html_e('My Delivery Tasks', 'pexpress'); ?></h2>

    <div class="polar-tasks-list" id="polar-delivery-tasks">
        <?php if (!empty($assigned_orders)) : ?>
            <?php foreach ($assigned_orders as $post) : ?>
                <?php
                $order = wc_get_order($post->ID);
                if (!$order) continue;
                $order_status = $order->get_status();
                ?>
                <div class="polar-task-item" data-order-id="<?php echo esc_attr($post->ID); ?>">
                    <div class="task-header">
                        <h4><?php printf(esc_html__('Order #%d', 'pexpress'), $post->ID); ?></h4>
                        <span class="task-status status-<?php echo esc_attr($order_status); ?>">
                            <?php echo esc_html(wc_get_order_status_name($order_status)); ?>
                        </span>
                    </div>
                    <div class="task-details">
                        <p><strong><?php esc_html_e('Customer:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_name()); ?></p>
                        <p><strong><?php esc_html_e('Address:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_formatted_billing_address()); ?></p>
                        <p><strong><?php esc_html_e('Phone:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_phone()); ?></p>
                        <p><strong><?php esc_html_e('Date:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_date_created()->date_i18n()); ?></p>
                    </div>
                    <div class="task-actions">
                        <?php if ('processing' === $order_status || 'polar-assigned' === $order_status) : ?>
                            <form class="polar-status-update-form" data-order-id="<?php echo esc_attr($post->ID); ?>">
                                <?php wp_nonce_field('polar_update_status_' . $post->ID, 'polar_status_nonce'); ?>
                                <button type="submit" name="status" value="polar-out" class="polar-btn">
                                    <?php esc_html_e('Mark Out for Delivery', 'pexpress'); ?>
                                </button>
                            </form>
                        <?php elseif ('polar-out' === $order_status) : ?>
                            <form class="polar-status-update-form" data-order-id="<?php echo esc_attr($post->ID); ?>">
                                <?php wp_nonce_field('polar_update_status_' . $post->ID, 'polar_status_nonce'); ?>
                                <button type="submit" name="status" value="polar-delivered" class="polar-btn polar-btn-success">
                                    <?php esc_html_e('Mark Delivered', 'pexpress'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p><?php esc_html_e('You have no assigned delivery tasks at this time.', 'pexpress'); ?></p>
        <?php endif; ?>
    </div>
</div>