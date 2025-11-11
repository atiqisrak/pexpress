<?php

/**
 * Order Tracking Template for Customers
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Status labels mapping
$status_labels = array(
    'agency' => array(
        'pending' => __('Pending', 'pexpress'),
        'assigned' => __('Assigned', 'pexpress'),
    ),
    'delivery' => array(
        'pending' => __('Pending', 'pexpress'),
        'meet_point_arrived' => __('Reached Meet Point', 'pexpress'),
        'delivery_location_arrived' => __('Reached Delivery Location', 'pexpress'),
        'service_in_progress' => __('Service In Progress', 'pexpress'),
        'service_complete' => __('Service Completed', 'pexpress'),
        'customer_served' => __('Ice-cream Delivered', 'pexpress'),
    ),
    'fridge' => array(
        'pending' => __('Pending', 'pexpress'),
        'fridge_drop' => __('Fridge Delivered On-site', 'pexpress'),
        'fridge_collected' => __('Fridge Collected On-site', 'pexpress'),
        'fridge_returned' => __('Fridge Returned to Base', 'pexpress'),
    ),
    'distributor' => array(
        'pending' => __('Pending', 'pexpress'),
        'distributor_prep' => __('Distributor Preparing', 'pexpress'),
        'out_for_delivery' => __('Out for Delivery', 'pexpress'),
        'handoff_complete' => __('Distributor Handoff Complete', 'pexpress'),
    ),
);

// Helper function to get status label
function pexpress_get_status_label($role, $status, $labels)
{
    if (isset($labels[$role][$status])) {
        return $labels[$role][$status];
    }
    return ucfirst(str_replace('_', ' ', $status));
}

// Helper function to get status class
function pexpress_get_status_class($status)
{
    $completed_statuses = array('customer_served', 'fridge_returned', 'handoff_complete', 'service_complete');
    $in_progress_statuses = array('meet_point_arrived', 'delivery_location_arrived', 'service_in_progress', 'fridge_drop', 'fridge_collected', 'distributor_prep', 'out_for_delivery', 'assigned');
    
    if (in_array($status, $completed_statuses, true)) {
        return 'completed';
    } elseif (in_array($status, $in_progress_statuses, true)) {
        return 'in-progress';
    }
    return 'pending';
}

?>
<div class="polar-order-tracking" data-order-id="<?php echo esc_attr($order_id); ?>">
    <div class="polar-tracking-header">
        <h2><?php echo esc_html__('Order Tracking', 'pexpress'); ?></h2>
        <div class="polar-order-info">
            <span class="polar-order-number"><?php echo esc_html__('Order #', 'pexpress') . esc_html($order_id); ?></span>
            <span class="polar-order-date"><?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format') . ' ' . get_option('time_format'))); ?></span>
        </div>
    </div>

    <div class="polar-tracking-statuses">
        <!-- HR/Agency Status -->
        <div class="polar-status-card polar-status-agency">
            <div class="polar-status-header">
                <h3><?php echo esc_html__('Agency (HR)', 'pexpress'); ?></h3>
                <span class="polar-status-badge polar-status-<?php echo esc_attr(pexpress_get_status_class($hr_status)); ?>" data-status="<?php echo esc_attr($hr_status); ?>">
                    <?php echo esc_html(pexpress_get_status_label('agency', $hr_status, $status_labels)); ?>
                </span>
            </div>
            <div class="polar-status-progress">
                <div class="polar-progress-bar">
                    <div class="polar-progress-fill polar-progress-<?php echo esc_attr(pexpress_get_status_class($hr_status)); ?>" style="width: <?php echo $hr_status === 'assigned' ? '100' : '0'; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Distributor Status -->
        <div class="polar-status-card polar-status-distributor">
            <div class="polar-status-header">
                <h3><?php echo esc_html__('Product Distributor', 'pexpress'); ?></h3>
                <span class="polar-status-badge polar-status-<?php echo esc_attr(pexpress_get_status_class($distributor_status)); ?>" data-status="<?php echo esc_attr($distributor_status); ?>">
                    <?php echo esc_html(pexpress_get_status_label('distributor', $distributor_status, $status_labels)); ?>
                </span>
            </div>
            <?php if ($distributor_user_name) : ?>
                <div class="polar-assigned-user">
                    <span class="polar-user-label"><?php echo esc_html__('Assigned to:', 'pexpress'); ?></span>
                    <span class="polar-user-name"><?php echo esc_html($distributor_user_name); ?></span>
                </div>
            <?php endif; ?>
            <div class="polar-status-progress">
                <div class="polar-progress-bar">
                    <?php
                    $distributor_progress = 0;
                    if ($distributor_status === 'distributor_prep') {
                        $distributor_progress = 33;
                    } elseif ($distributor_status === 'out_for_delivery') {
                        $distributor_progress = 66;
                    } elseif ($distributor_status === 'handoff_complete') {
                        $distributor_progress = 100;
                    }
                    ?>
                    <div class="polar-progress-fill polar-progress-<?php echo esc_attr(pexpress_get_status_class($distributor_status)); ?>" style="width: <?php echo esc_attr($distributor_progress); ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Delivery Status -->
        <div class="polar-status-card polar-status-delivery">
            <div class="polar-status-header">
                <h3><?php echo esc_html__('Delivery (HR)', 'pexpress'); ?></h3>
                <span class="polar-status-badge polar-status-<?php echo esc_attr(pexpress_get_status_class($delivery_status)); ?>" data-status="<?php echo esc_attr($delivery_status); ?>">
                    <?php echo esc_html(pexpress_get_status_label('delivery', $delivery_status, $status_labels)); ?>
                </span>
            </div>
            <?php if ($delivery_user_name) : ?>
                <div class="polar-assigned-user">
                    <span class="polar-user-label"><?php echo esc_html__('Assigned to:', 'pexpress'); ?></span>
                    <span class="polar-user-name"><?php echo esc_html($delivery_user_name); ?></span>
                </div>
            <?php endif; ?>
            <div class="polar-status-progress">
                <div class="polar-progress-bar">
                    <?php
                    $delivery_progress = 0;
                    if ($delivery_status === 'meet_point_arrived') {
                        $delivery_progress = 20;
                    } elseif ($delivery_status === 'delivery_location_arrived') {
                        $delivery_progress = 40;
                    } elseif ($delivery_status === 'service_in_progress') {
                        $delivery_progress = 60;
                    } elseif ($delivery_status === 'service_complete') {
                        $delivery_progress = 80;
                    } elseif ($delivery_status === 'customer_served') {
                        $delivery_progress = 100;
                    }
                    ?>
                    <div class="polar-progress-fill polar-progress-<?php echo esc_attr(pexpress_get_status_class($delivery_status)); ?>" style="width: <?php echo esc_attr($delivery_progress); ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Fridge Status -->
        <div class="polar-status-card polar-status-fridge">
            <div class="polar-status-header">
                <h3><?php echo esc_html__('Fridge Provider', 'pexpress'); ?></h3>
                <span class="polar-status-badge polar-status-<?php echo esc_attr(pexpress_get_status_class($fridge_status)); ?>" data-status="<?php echo esc_attr($fridge_status); ?>">
                    <?php echo esc_html(pexpress_get_status_label('fridge', $fridge_status, $status_labels)); ?>
                </span>
            </div>
            <?php if ($fridge_user_name) : ?>
                <div class="polar-assigned-user">
                    <span class="polar-user-label"><?php echo esc_html__('Assigned to:', 'pexpress'); ?></span>
                    <span class="polar-user-name"><?php echo esc_html($fridge_user_name); ?></span>
                </div>
            <?php endif; ?>
            <div class="polar-status-progress">
                <div class="polar-progress-bar">
                    <?php
                    $fridge_progress = 0;
                    if ($fridge_status === 'fridge_drop') {
                        $fridge_progress = 33;
                    } elseif ($fridge_status === 'fridge_collected') {
                        $fridge_progress = 66;
                    } elseif ($fridge_status === 'fridge_returned') {
                        $fridge_progress = 100;
                    }
                    ?>
                    <div class="polar-progress-fill polar-progress-<?php echo esc_attr(pexpress_get_status_class($fridge_status)); ?>" style="width: <?php echo esc_attr($fridge_progress); ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="polar-tracking-footer">
        <div class="polar-last-update">
            <span class="polar-update-label"><?php echo esc_html__('Last updated:', 'pexpress'); ?></span>
            <span class="polar-update-time" id="polar-last-update-time"><?php echo esc_html(current_time('mysql')); ?></span>
        </div>
        <div class="polar-auto-refresh-indicator">
            <span class="polar-refresh-icon">🔄</span>
            <span class="polar-refresh-text"><?php echo esc_html__('Auto-refreshing...', 'pexpress'); ?></span>
        </div>
    </div>
</div>

