<?php

/**
 * Fridge Provider Dashboard Template
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$assigned_orders = PExpress_Core::get_assigned_orders($user_id, 'fridge');
?>

<div class="polar-dashboard polar-fridge-dashboard">
    <h2><?php esc_html_e('My Fridge Tasks', 'pexpress'); ?></h2>

    <div class="polar-tasks-list" id="polar-fridge-tasks">
        <?php if (!empty($assigned_orders)) : ?>
            <?php foreach ($assigned_orders as $post) : ?>
                <?php
                $order = wc_get_order($post->ID);
                if (!$order) continue;
                $return_date = PExpress_Core::get_order_meta($post->ID, '_polar_fridge_return_date');
                ?>
                <div class="polar-task-item" data-order-id="<?php echo esc_attr($post->ID); ?>">
                    <div class="task-header">
                        <h4><?php printf(esc_html__('Order #%d', 'pexpress'), $post->ID); ?></h4>
                    </div>
                    <div class="task-details">
                        <p><strong><?php esc_html_e('Customer:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_name()); ?></p>
                        <p><strong><?php esc_html_e('Delivery Address:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_formatted_billing_address()); ?></p>
                        <p><strong><?php esc_html_e('Phone:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_phone()); ?></p>
                        <?php if ($return_date) : ?>
                            <p><strong><?php esc_html_e('Return Date:', 'pexpress'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($return_date))); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="task-actions">
                        <form class="polar-fridge-status-form" data-order-id="<?php echo esc_attr($post->ID); ?>">
                            <?php wp_nonce_field('polar_fridge_status_' . $post->ID, 'polar_fridge_nonce'); ?>
                            <button type="submit" name="status" value="fridge-collected" class="polar-btn polar-btn-success">
                                <?php esc_html_e('Mark Fridge Collected', 'pexpress'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p><?php esc_html_e('You have no assigned fridge tasks at this time.', 'pexpress'); ?></p>
        <?php endif; ?>
    </div>
</div>