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
?>

<div class="wrap polar-dashboard polar-distributor-dashboard">
    <h1><?php esc_html_e('Distributor Dashboard', 'pexpress'); ?></h1>

    <div class="polar-stats">
        <div class="polar-stat-card">
            <h3><?php echo count($assigned_orders); ?></h3>
            <p><?php esc_html_e('Total Assigned Orders', 'pexpress'); ?></p>
        </div>
        <div class="polar-stat-card">
            <h3><?php echo count(array_filter($assigned_orders, function ($o) {
                    return $o->get_status() === 'completed';
                })); ?></h3>
            <p><?php esc_html_e('Fulfilled', 'pexpress'); ?></p>
        </div>
    </div>

    <div class="polar-tasks-section">
        <h2><?php esc_html_e('My Distribution Tasks', 'pexpress'); ?></h2>

        <div class="polar-tasks-list" id="polar-distributor-tasks">
            <?php if (!empty($assigned_orders)) : ?>
                <?php foreach ($assigned_orders as $order) :
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
                            <p><strong><?php esc_html_e('Phone:', 'pexpress'); ?></strong>
                                <a href="tel:<?php echo esc_attr($order->get_billing_phone()); ?>">
                                    <?php echo esc_html($order->get_billing_phone()); ?>
                                </a>
                            </p>
                            <p><strong><?php esc_html_e('Items:', 'pexpress'); ?></strong></p>
                            <ul class="order-items">
                                <?php foreach ($order->get_items() as $item) : ?>
                                    <li>
                                        <?php echo esc_html($item->get_name()); ?>
                                        <strong>x <?php echo esc_html($item->get_quantity()); ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <p><strong><?php esc_html_e('Order Date:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_date_created()->date_i18n()); ?></p>
                            <p><strong><?php esc_html_e('Total:', 'pexpress'); ?></strong> <?php echo wp_kses_post($order->get_formatted_order_total()); ?></p>
                        </div>
                        <?php if ($order_status !== 'completed') : ?>
                            <div class="task-actions">
                                <form class="polar-distributor-status-form" data-order-id="<?php echo esc_attr($order_id); ?>">
                                    <?php wp_nonce_field('polar_distributor_status_' . $order_id, 'polar_distributor_nonce'); ?>
                                    <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                                    <button type="submit" name="status" value="fulfilled" class="polar-btn polar-btn-success">
                                        <?php esc_html_e('Mark Fulfilled', 'pexpress'); ?>
                                    </button>
                                    <span class="polar-update-loading" style="display:none;"><?php esc_html_e('Updating...', 'pexpress'); ?></span>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="polar-empty-state">
                    <p><?php esc_html_e('You have no assigned distribution tasks at this time.', 'pexpress'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>