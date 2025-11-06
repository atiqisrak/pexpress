<?php

/**
 * Distributor Dashboard Template
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$assigned_orders = PExpress_Core::get_assigned_orders($user_id, 'distributor');
?>

<div class="polar-dashboard polar-distributor-dashboard">
    <h2><?php esc_html_e('My Distribution Tasks', 'pexpress'); ?></h2>

    <div class="polar-tasks-list" id="polar-distributor-tasks">
        <?php if (!empty($assigned_orders)) : ?>
            <?php foreach ($assigned_orders as $post) : ?>
                <?php
                $order = wc_get_order($post->ID);
                if (!$order) continue;
                ?>
                <div class="polar-task-item" data-order-id="<?php echo esc_attr($post->ID); ?>">
                    <div class="task-header">
                        <h4><?php printf(esc_html__('Order #%d', 'pexpress'), $post->ID); ?></h4>
                        <span class="task-status status-<?php echo esc_attr($order->get_status()); ?>">
                            <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                        </span>
                    </div>
                    <div class="task-details">
                        <p><strong><?php esc_html_e('Customer:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_name()); ?></p>
                        <p><strong><?php esc_html_e('Items:', 'pexpress'); ?></strong></p>
                        <ul class="order-items">
                            <?php foreach ($order->get_items() as $item) : ?>
                                <li><?php echo esc_html($item->get_name()); ?> x <?php echo esc_html($item->get_quantity()); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p><strong><?php esc_html_e('Date:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_date_created()->date_i18n()); ?></p>
                    </div>
                    <div class="task-actions">
                        <form class="polar-distributor-status-form" data-order-id="<?php echo esc_attr($post->ID); ?>">
                            <?php wp_nonce_field('polar_distributor_status_' . $post->ID, 'polar_distributor_nonce'); ?>
                            <button type="submit" name="status" value="fulfilled" class="polar-btn polar-btn-success">
                                <?php esc_html_e('Mark Fulfilled', 'pexpress'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p><?php esc_html_e('You have no assigned distribution tasks at this time.', 'pexpress'); ?></p>
        <?php endif; ?>
    </div>
</div>