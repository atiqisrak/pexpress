<?php

/**
 * Support Dashboard Template
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap polar-dashboard polar-support-dashboard">
    <h1><?php esc_html_e('Customer Support Dashboard', 'pexpress'); ?></h1>

    <div class="polar-stats">
        <div class="polar-stat-card">
            <h3><?php echo count($recent_orders); ?></h3>
            <p><?php esc_html_e('Recent Orders', 'pexpress'); ?></p>
        </div>
        <div class="polar-stat-card">
            <h3><?php echo count(array_filter($recent_orders, function ($o) {
                    return $o->get_status() === 'processing';
                })); ?></h3>
            <p><?php esc_html_e('Processing', 'pexpress'); ?></p>
        </div>
        <div class="polar-stat-card">
            <h3><?php echo count(array_filter($recent_orders, function ($o) {
                    return $o->get_status() === 'completed';
                })); ?></h3>
            <p><?php esc_html_e('Completed', 'pexpress'); ?></p>
        </div>
        <div class="polar-stat-card">
            <h3><?php echo count(array_filter($recent_orders, function ($o) {
                    return in_array($o->get_status(), array('wc-polar-out', 'wc-polar-delivered'));
                })); ?></h3>
            <p><?php esc_html_e('In Delivery', 'pexpress'); ?></p>
        </div>
    </div>

    <div class="polar-orders-section">
        <h2><?php esc_html_e('Recent Orders', 'pexpress'); ?></h2>

        <div class="polar-filters">
            <select id="polar-status-filter" class="polar-select">
                <option value=""><?php esc_html_e('All Statuses', 'pexpress'); ?></option>
                <?php
                $statuses = wc_get_order_statuses();
                foreach ($statuses as $status_key => $status_label) :
                ?>
                    <option value="<?php echo esc_attr($status_key); ?>">
                        <?php echo esc_html($status_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="polar-search" class="polar-input" placeholder="<?php esc_attr_e('Search by order ID, customer name, or phone...', 'pexpress'); ?>">
        </div>

        <div class="polar-orders-list" id="polar-support-orders">
            <?php if (!empty($recent_orders)) : ?>
                <?php foreach ($recent_orders as $order) :
                    $order_id = $order->get_id();
                    $order_status = $order->get_status();
                ?>
                    <div class="polar-order-item" data-order-id="<?php echo esc_attr($order_id); ?>" data-status="<?php echo esc_attr($order_status); ?>">
                        <div class="order-header">
                            <h4>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" target="_blank">
                                    <?php printf(esc_html__('Order #%d', 'pexpress'), $order_id); ?>
                                </a>
                            </h4>
                            <span class="order-status status-<?php echo esc_attr($order_status); ?>">
                                <?php echo esc_html(wc_get_order_status_name($order_status)); ?>
                            </span>
                        </div>
                        <div class="order-details">
                            <p><strong><?php esc_html_e('Customer:', 'pexpress'); ?></strong>
                                <span class="customer-name"><?php echo esc_html(PExpress_Core::get_billing_name($order)); ?></span>
                            </p>
                            <p><strong><?php esc_html_e('Email:', 'pexpress'); ?></strong>
                                <a href="mailto:<?php echo esc_attr($order->get_billing_email()); ?>">
                                    <?php echo esc_html($order->get_billing_email()); ?>
                                </a>
                            </p>
                            <p><strong><?php esc_html_e('Phone:', 'pexpress'); ?></strong>
                                <a href="tel:<?php echo esc_attr($order->get_billing_phone()); ?>" class="phone-number">
                                    <?php echo esc_html($order->get_billing_phone()); ?>
                                </a>
                            </p>
                            <p><strong><?php esc_html_e('Date:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_date_created()->date_i18n()); ?></p>
                            <p><strong><?php esc_html_e('Total:', 'pexpress'); ?></strong> <?php echo wp_kses_post($order->get_formatted_order_total()); ?></p>

                            <?php
                            // Show assignment info if available
                            $delivery_id = PExpress_Core::get_delivery_user_id($order_id);
                            $fridge_id = PExpress_Core::get_fridge_user_id($order_id);
                            $distributor_id = PExpress_Core::get_distributor_user_id($order_id);
                            if ($delivery_id || $fridge_id || $distributor_id) :
                            ?>
                                <div class="assignment-info">
                                    <p><strong><?php esc_html_e('Assignments:', 'pexpress'); ?></strong></p>
                                    <ul>
                                        <?php if ($delivery_id) : ?>
                                            <li><?php esc_html_e('Delivery:', 'pexpress'); ?> <?php echo esc_html(get_userdata($delivery_id)->display_name); ?></li>
                                        <?php endif; ?>
                                        <?php if ($fridge_id) : ?>
                                            <li><?php esc_html_e('Fridge:', 'pexpress'); ?> <?php echo esc_html(get_userdata($fridge_id)->display_name); ?></li>
                                        <?php endif; ?>
                                        <?php if ($distributor_id) : ?>
                                            <li><?php esc_html_e('Distributor:', 'pexpress'); ?> <?php echo esc_html(get_userdata($distributor_id)->display_name); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="order-actions">
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" class="polar-btn polar-btn-primary" target="_blank">
                                <?php esc_html_e('View/Edit Order', 'pexpress'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="polar-empty-state">
                    <p><?php esc_html_e('No orders found.', 'pexpress'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>