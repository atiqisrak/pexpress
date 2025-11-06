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

<?php
// Calculate stats
$total_assigned = count($assigned_orders);
$pending_count = count($return_pending);
$collected_count = count($collected_orders);
?>

<div class="wrap polar-dashboard polar-fridge-dashboard">
    <div class="polar-dashboard-header">
        <div class="polar-header-content">
            <h1 class="polar-dashboard-title">
                <span class="polar-title-icon">🧊</span>
                <?php esc_html_e('Fridge Dashboard', 'pexpress'); ?>
            </h1>
            <p class="polar-dashboard-subtitle"><?php esc_html_e('Manage fridge collections and returns', 'pexpress'); ?></p>
        </div>
    </div>

    <div class="polar-stats-grid">
        <div class="polar-stat-card stat-card-primary">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($total_assigned); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Total Assigned', 'pexpress'); ?></p>
            </div>
        </div>
        <div class="polar-stat-card stat-card-warning">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($pending_count); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Pending Collection', 'pexpress'); ?></p>
            </div>
        </div>
        <div class="polar-stat-card stat-card-success">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($collected_count); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Collected', 'pexpress'); ?></p>
            </div>
        </div>
    </div>

    <div class="polar-tasks-section">
        <div class="polar-section-header">
            <h2 class="polar-section-title"><?php esc_html_e('My Fridge Tasks', 'pexpress'); ?></h2>
        </div>

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
                                <div class="order-header-left">
                                    <h4 class="order-title">
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" target="_blank" class="order-link">
                                            <span class="order-id-badge">#<?php echo esc_html($order_id); ?></span>
                                        </a>
                                    </h4>
                                    <span class="order-date-badge">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <?php echo esc_html($order->get_date_created()->date_i18n('M d, Y')); ?>
                                    </span>
                                </div>
                                <span class="task-status status-<?php echo esc_attr($order->get_status()); ?>">
                                    <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                </span>
                            </div>
                            <div class="task-details">
                                <div class="order-detail-row">
                                    <div class="order-detail-item">
                                        <span class="detail-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <div class="detail-content">
                                            <span class="detail-label"><?php esc_html_e('Customer', 'pexpress'); ?></span>
                                            <span class="detail-value"><?php echo esc_html(PExpress_Core::get_billing_name($order)); ?></span>
                                        </div>
                                    </div>
                                    <div class="order-detail-item">
                                        <span class="detail-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3 5C3 3.89543 3.89543 3 5 3H8.27924C8.70967 3 9.09181 3.27543 9.22792 3.68377L10.7257 8.17721C10.8831 8.64932 10.6694 9.16531 10.2243 9.38787L7.96701 10.5165C9.06925 12.9612 11.0388 14.9308 13.4835 16.033L14.6121 13.7757C14.8347 13.3306 15.3507 13.1169 15.8228 13.2743L20.3162 14.7721C20.7246 14.9082 21 15.2903 21 15.7208V19C21 20.1046 20.1046 21 19 21H18C9.71573 21 3 14.2843 3 6V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <div class="detail-content">
                                            <span class="detail-label"><?php esc_html_e('Phone', 'pexpress'); ?></span>
                                            <a href="tel:<?php echo esc_attr($order->get_billing_phone()); ?>" class="detail-value detail-link">
                                                <?php echo esc_html($order->get_billing_phone()); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="order-detail-row">
                                    <div class="order-detail-item order-detail-full">
                                        <span class="detail-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5523 5.44772 21 6 21H9M19 10L21 12M19 10V20C19 20.5523 18.5523 21 18 21H15M9 21C9.55228 21 10 20.5523 10 20V16C10 15.4477 10.4477 15 11 15H13C13.5523 15 14 15.4477 14 16V20C14 20.5523 14.4477 21 15 21M9 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <div class="detail-content">
                                            <span class="detail-label"><?php esc_html_e('Delivery Address', 'pexpress'); ?></span>
                                            <span class="detail-value"><?php echo wp_kses_post($order->get_formatted_billing_address()); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($return_date) : ?>
                                    <div class="order-detail-row">
                                        <div class="order-detail-item">
                                            <span class="detail-icon">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                            <div class="detail-content">
                                                <span class="detail-label"><?php esc_html_e('Expected Return Date', 'pexpress'); ?></span>
                                                <span class="detail-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($return_date))); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="task-actions">
                                <form class="polar-fridge-status-form" data-order-id="<?php echo esc_attr($order_id); ?>">
                                    <?php wp_nonce_field('polar_fridge_status_' . $order_id, 'polar_fridge_nonce'); ?>
                                    <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                                    <button type="submit" name="status" value="fridge-collected" class="polar-btn polar-btn-success">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <?php esc_html_e('Mark Fridge Collected', 'pexpress'); ?>
                                    </button>
                                    <span class="polar-update-loading" style="display:none;"><?php esc_html_e('Updating...', 'pexpress'); ?></span>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="polar-empty-state">
                        <div class="empty-state-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <h3><?php esc_html_e('No pending tasks', 'pexpress'); ?></h3>
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
                    <div class="empty-state-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3><?php esc_html_e('No collected fridges yet', 'pexpress'); ?></h3>
                    <p><?php esc_html_e('No fridges have been collected yet.', 'pexpress'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>