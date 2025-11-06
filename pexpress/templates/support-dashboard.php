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

// Get recent orders
$args = array(
    'post_type'      => 'shop_order',
    'post_status'    => 'any',
    'posts_per_page' => 20,
    'orderby'        => 'date',
    'order'          => 'DESC',
);

$recent_orders = new WP_Query($args);
?>

<div class="polar-dashboard polar-support-dashboard">
    <h2><?php esc_html_e('Customer Support Dashboard', 'pexpress'); ?></h2>

    <div class="polar-orders-section">
        <h3><?php esc_html_e('Recent Orders', 'pexpress'); ?></h3>

        <?php if ($recent_orders->have_posts()) : ?>
            <div class="polar-orders-list" id="polar-support-orders">
                <?php while ($recent_orders->have_posts()) : $recent_orders->the_post(); ?>
                    <?php
                    $order_id = get_the_ID();
                    $order = wc_get_order($order_id);
                    if (!$order) continue;
                    ?>
                    <div class="polar-order-item" data-order-id="<?php echo esc_attr($order_id); ?>">
                        <div class="order-header">
                            <h4><?php printf(esc_html__('Order #%d', 'pexpress'), $order_id); ?></h4>
                            <span class="order-status"><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span>
                        </div>
                        <div class="order-details">
                            <p><strong><?php esc_html_e('Customer:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_name()); ?></p>
                            <p><strong><?php esc_html_e('Email:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_email()); ?></p>
                            <p><strong><?php esc_html_e('Phone:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_phone()); ?></p>
                            <p><strong><?php esc_html_e('Date:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_date_created()->date_i18n()); ?></p>
                            <p><strong><?php esc_html_e('Total:', 'pexpress'); ?></strong> <?php echo wp_kses_post($order->get_formatted_order_total()); ?></p>
                        </div>
                        <div class="order-actions">
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" class="polar-btn">
                                <?php esc_html_e('View/Edit Order', 'pexpress'); ?>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('No orders found.', 'pexpress'); ?></p>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</div>