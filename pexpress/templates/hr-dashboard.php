<?php

/**
 * HR Dashboard Template
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get orders needing assignment
$args = array(
    'post_type'      => 'shop_order',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'meta_query'     => array(
        array(
            'key'   => '_polar_needs_assignment',
            'value' => 'yes',
            'compare' => '=',
        ),
    ),
);

$orders_needing_assignment = new WP_Query($args);

// Get all users for each role
$delivery_users   = get_users(array('role' => 'polar_delivery'));
$fridge_users     = get_users(array('role' => 'polar_fridge'));
$distributor_users = get_users(array('role' => 'polar_distributor'));
?>

<div class="polar-dashboard polar-hr-dashboard">
    <h2><?php esc_html_e('HR Dashboard - Task Assignment', 'pexpress'); ?></h2>

    <div class="polar-orders-section">
        <h3><?php esc_html_e('Orders Needing Assignment', 'pexpress'); ?></h3>

        <?php if ($orders_needing_assignment->have_posts()) : ?>
            <div class="polar-orders-list" id="polar-orders-list">
                <?php while ($orders_needing_assignment->have_posts()) : $orders_needing_assignment->the_post(); ?>
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
                            <p><strong><?php esc_html_e('Phone:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_phone()); ?></p>
                            <p><strong><?php esc_html_e('Date:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_date_created()->date_i18n()); ?></p>
                        </div>
                        <div class="assignment-form">
                            <form class="polar-assign-form" data-order-id="<?php echo esc_attr($order_id); ?>">
                                <?php wp_nonce_field('polar_assign_' . $order_id, 'polar_assign_nonce'); ?>

                                <div class="assign-field">
                                    <label><?php esc_html_e('Delivery Person:', 'pexpress'); ?></label>
                                    <select name="delivery_user_id" class="polar-select">
                                        <option value=""><?php esc_html_e('Select...', 'pexpress'); ?></option>
                                        <?php foreach ($delivery_users as $user) : ?>
                                            <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="assign-field">
                                    <label><?php esc_html_e('Fridge Provider:', 'pexpress'); ?></label>
                                    <select name="fridge_user_id" class="polar-select">
                                        <option value=""><?php esc_html_e('Select...', 'pexpress'); ?></option>
                                        <?php foreach ($fridge_users as $user) : ?>
                                            <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="assign-field">
                                    <label><?php esc_html_e('Distributor:', 'pexpress'); ?></label>
                                    <select name="distributor_user_id" class="polar-select">
                                        <option value=""><?php esc_html_e('Select...', 'pexpress'); ?></option>
                                        <?php foreach ($distributor_users as $user) : ?>
                                            <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="assign-field">
                                    <label><?php esc_html_e('Fridge Return Date:', 'pexpress'); ?></label>
                                    <input type="date" name="fridge_return_date" class="polar-input">
                                </div>

                                <div class="assign-field">
                                    <label><?php esc_html_e('Notes:', 'pexpress'); ?></label>
                                    <textarea name="assignment_note" class="polar-textarea" rows="2"></textarea>
                                </div>

                                <button type="submit" class="polar-btn polar-btn-primary"><?php esc_html_e('Assign', 'pexpress'); ?></button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('No orders need assignment at this time.', 'pexpress'); ?></p>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</div>