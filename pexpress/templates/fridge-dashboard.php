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
?>

<div class="wrap polar-dashboard polar-fridge-dashboard">
    <h1><?php esc_html_e('Fridge Dashboard', 'pexpress'); ?></h1>

    <div class="polar-stats">
        <div class="polar-stat-card">
            <h3><?php echo count($assigned_orders); ?></h3>
            <p><?php esc_html_e('Total Assigned', 'pexpress'); ?></p>
        </div>
        <div class="polar-stat-card">
            <h3><?php echo count($return_pending); ?></h3>
            <p><?php esc_html_e('Pending Collection', 'pexpress'); ?></p>
        </div>
        <div class="polar-stat-card">
            <h3><?php echo count($collected_orders); ?></h3>
            <p><?php esc_html_e('Collected', 'pexpress'); ?></p>
        </div>
    </div>

    <div class="polar-tasks-section">
        <h2><?php esc_html_e('My Fridge Tasks', 'pexpress'); ?></h2>

        <div class="polar-tabs">
            <button class="polar-tab active" data-tab="pending"><?php esc_html_e('Pending Collection', 'pexpress'); ?></button>
            <button class="polar-tab" data-tab="collected"><?php esc_html_e('Collected', 'pexpress'); ?></button>
        </div>

        <div class="polar-tab-content active" id="tab-pending">
            <div class="polar-tasks-list" id="polar-fridge-tasks">
                <?php if (!empty($return_pending)) : ?>
                    <?php foreach ($return_pending as $order) :
                        $order_id = $order->get_id();
                        $return_date = PExpress_Core::get_order_meta($order_id, '_polar_fridge_return_date');
                    ?>
                        <div class="polar-task-item" data-order-id="<?php echo esc_attr($order_id); ?>">
                            <div class="task-header">
                                <h4>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" target="_blank">
                                        <?php printf(esc_html__('Order #%d', 'pexpress'), $order_id); ?>
                                    </a>
                                </h4>
                                <span class="task-status status-<?php echo esc_attr($order->get_status()); ?>">
                                    <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                </span>
                            </div>
                            <div class="task-details">
                                <p><strong><?php esc_html_e('Customer:', 'pexpress'); ?></strong> <?php echo esc_html(PExpress_Core::get_billing_name($order)); ?></p>
                                <p><strong><?php esc_html_e('Delivery Address:', 'pexpress'); ?></strong> <?php echo wp_kses_post($order->get_formatted_billing_address()); ?></p>
                                <p><strong><?php esc_html_e('Phone:', 'pexpress'); ?></strong>
                                    <a href="tel:<?php echo esc_attr($order->get_billing_phone()); ?>">
                                        <?php echo esc_html($order->get_billing_phone()); ?>
                                    </a>
                                </p>
                                <?php if ($return_date) : ?>
                                    <p><strong><?php esc_html_e('Expected Return Date:', 'pexpress'); ?></strong>
                                        <span class="return-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($return_date))); ?></span>
                                    </p>
                                <?php endif; ?>
                                <p><strong><?php esc_html_e('Order Date:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_date_created()->date_i18n()); ?></p>
                            </div>
                            <div class="task-actions">
                                <form class="polar-fridge-status-form" data-order-id="<?php echo esc_attr($order_id); ?>">
                                    <?php wp_nonce_field('polar_fridge_status_' . $order_id, 'polar_fridge_nonce'); ?>
                                    <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                                    <button type="submit" name="status" value="fridge-collected" class="polar-btn polar-btn-success">
                                        <?php esc_html_e('Mark Fridge Collected', 'pexpress'); ?>
                                    </button>
                                    <span class="polar-update-loading" style="display:none;"><?php esc_html_e('Updating...', 'pexpress'); ?></span>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="polar-empty-state">
                        <p><?php esc_html_e('You have no pending fridge collection tasks at this time.', 'pexpress'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="polar-tab-content" id="tab-collected">
            <?php if (!empty($collected_orders)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Order ID', 'pexpress'); ?></th>
                            <th><?php esc_html_e('Customer', 'pexpress'); ?></th>
                            <th><?php esc_html_e('Return Date', 'pexpress'); ?></th>
                            <th><?php esc_html_e('Collection Date', 'pexpress'); ?></th>
                            <th><?php esc_html_e('Status', 'pexpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($collected_orders as $order) :
                            $order_id = $order->get_id();
                            $return_date = PExpress_Core::get_order_meta($order_id, '_polar_fridge_return_date');
                        ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>">
                                        #<?php echo esc_html($order_id); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html(PExpress_Core::get_billing_name($order)); ?></td>
                                <td><?php echo $return_date ? esc_html(date_i18n(get_option('date_format'), strtotime($return_date))) : '—'; ?></td>
                                <td><?php echo esc_html($order->get_date_modified()->date_i18n()); ?></td>
                                <td><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="polar-empty-state">
                    <p><?php esc_html_e('No collected fridges yet.', 'pexpress'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>