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
        'distributor_prep' => __('Product Provider Preparing', 'pexpress'),
        'out_for_delivery' => __('Out for Delivery', 'pexpress'),
        'handoff_complete' => __('Product Provider Handoff Complete', 'pexpress'),
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
            <?php 
            $order_date = $order->get_date_created();
            if ($order_date) {
                $formatted_date = $order_date->date_i18n(get_option('date_format') . ' ' . get_option('time_format'));
            } else {
                $formatted_date = __('Date not available', 'pexpress');
            }
            ?>
            <span class="polar-order-date"><?php echo esc_html($formatted_date); ?></span>
        </div>
    </div>

    <div class="polar-tracking-statuses">
        <!-- Agency Status -->
        <?php
        $agency_history = PExpress_Core::get_role_status_history($order_id, 'agency');
        $agency_last_update = '';
        if (!empty($agency_history)) {
            $last_entry = end($agency_history);
            if (isset($last_entry['timestamp'])) {
                $agency_last_update = mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $last_entry['timestamp']);
            }
        }
        ?>
        <div class="polar-status-card polar-status-agency">
            <div class="polar-status-header">
                <h3><?php echo esc_html__('Agency', 'pexpress'); ?></h3>
                <span class="polar-status-badge polar-status-<?php echo esc_attr(pexpress_get_status_class($hr_status ? $hr_status : 'pending')); ?>" data-status="<?php echo esc_attr($hr_status ? $hr_status : 'pending'); ?>">
                    <?php echo esc_html(pexpress_get_status_label('agency', $hr_status ? $hr_status : 'pending', $status_labels)); ?>
                </span>
            </div>
            <?php if ($agency_last_update) : ?>
                <div class="polar-status-timestamp">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 4px;">
                        <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span><?php echo esc_html__('Updated:', 'pexpress') . ' ' . esc_html($agency_last_update); ?></span>
                </div>
            <?php endif; ?>
            <div class="polar-status-progress">
                <div class="polar-progress-bar">
                    <?php $current_hr_status = $hr_status ? $hr_status : 'pending'; ?>
                    <div class="polar-progress-fill polar-progress-<?php echo esc_attr(pexpress_get_status_class($current_hr_status)); ?>" style="width: <?php echo ($current_hr_status === 'assigned') ? '100' : '0'; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Product Provider Status -->
        <?php
        $distributor_history = PExpress_Core::get_role_status_history($order_id, 'distributor');
        $distributor_last_update = '';
        if (!empty($distributor_history)) {
            $last_entry = end($distributor_history);
            if (isset($last_entry['timestamp'])) {
                $distributor_last_update = mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $last_entry['timestamp']);
            }
        }
        ?>
        <div class="polar-status-card polar-status-distributor">
            <div class="polar-status-header">
                <h3><?php echo esc_html__('Product Provider', 'pexpress'); ?></h3>
                <?php $current_distributor_status = $distributor_status ? $distributor_status : 'pending'; ?>
                <span class="polar-status-badge polar-status-<?php echo esc_attr(pexpress_get_status_class($current_distributor_status)); ?>" data-status="<?php echo esc_attr($current_distributor_status); ?>">
                    <?php echo esc_html(pexpress_get_status_label('distributor', $current_distributor_status, $status_labels)); ?>
                </span>
            </div>
            <?php if ($distributor_user_name) : ?>
                <div class="polar-assigned-user">
                    <span class="polar-user-label"><?php echo esc_html__('Assigned to:', 'pexpress'); ?></span>
                    <span class="polar-user-name"><?php echo esc_html($distributor_user_name); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($distributor_last_update) : ?>
                <div class="polar-status-timestamp">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 4px;">
                        <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span><?php echo esc_html__('Updated:', 'pexpress') . ' ' . esc_html($distributor_last_update); ?></span>
                </div>
            <?php endif; ?>
            <div class="polar-status-progress">
                <div class="polar-progress-bar">
                    <?php
                    $current_distributor_status = $distributor_status ? $distributor_status : 'pending';
                    $distributor_progress = 0;
                    if ($current_distributor_status === 'distributor_prep') {
                        $distributor_progress = 33;
                    } elseif ($current_distributor_status === 'out_for_delivery') {
                        $distributor_progress = 66;
                    } elseif ($current_distributor_status === 'handoff_complete') {
                        $distributor_progress = 100;
                    }
                    ?>
                    <div class="polar-progress-fill polar-progress-<?php echo esc_attr(pexpress_get_status_class($current_distributor_status)); ?>" style="width: <?php echo esc_attr($distributor_progress); ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Delivery Status -->
        <?php
        $delivery_history = PExpress_Core::get_role_status_history($order_id, 'delivery');
        $delivery_last_update = '';
        if (!empty($delivery_history)) {
            $last_entry = end($delivery_history);
            if (isset($last_entry['timestamp'])) {
                $delivery_last_update = mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $last_entry['timestamp']);
            }
        }
        ?>
        <div class="polar-status-card polar-status-delivery">
            <div class="polar-status-header">
                <h3><?php echo esc_html__('Delivery (SR)', 'pexpress'); ?></h3>
                <?php $current_delivery_status = $delivery_status ? $delivery_status : 'pending'; ?>
                <span class="polar-status-badge polar-status-<?php echo esc_attr(pexpress_get_status_class($current_delivery_status)); ?>" data-status="<?php echo esc_attr($current_delivery_status); ?>">
                    <?php echo esc_html(pexpress_get_status_label('delivery', $current_delivery_status, $status_labels)); ?>
                </span>
            </div>
            <?php if ($delivery_user_name) : ?>
                <div class="polar-assigned-user">
                    <span class="polar-user-label"><?php echo esc_html__('Assigned to:', 'pexpress'); ?></span>
                    <span class="polar-user-name"><?php echo esc_html($delivery_user_name); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($delivery_last_update) : ?>
                <div class="polar-status-timestamp">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 4px;">
                        <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span><?php echo esc_html__('Updated:', 'pexpress') . ' ' . esc_html($delivery_last_update); ?></span>
                </div>
            <?php endif; ?>
            <div class="polar-status-progress">
                <div class="polar-progress-bar">
                    <?php
                    $current_delivery_status = $delivery_status ? $delivery_status : 'pending';
                    $delivery_progress = 0;
                    if ($current_delivery_status === 'meet_point_arrived') {
                        $delivery_progress = 20;
                    } elseif ($current_delivery_status === 'delivery_location_arrived') {
                        $delivery_progress = 40;
                    } elseif ($current_delivery_status === 'service_in_progress') {
                        $delivery_progress = 60;
                    } elseif ($current_delivery_status === 'service_complete') {
                        $delivery_progress = 80;
                    } elseif ($current_delivery_status === 'customer_served') {
                        $delivery_progress = 100;
                    }
                    ?>
                    <div class="polar-progress-fill polar-progress-<?php echo esc_attr(pexpress_get_status_class($current_delivery_status)); ?>" style="width: <?php echo esc_attr($delivery_progress); ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Fridge Status -->
        <?php
        $fridge_history = PExpress_Core::get_role_status_history($order_id, 'fridge');
        $fridge_last_update = '';
        if (!empty($fridge_history)) {
            $last_entry = end($fridge_history);
            if (isset($last_entry['timestamp'])) {
                $fridge_last_update = mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $last_entry['timestamp']);
            }
        }
        ?>
        <div class="polar-status-card polar-status-fridge">
            <div class="polar-status-header">
                <h3><?php echo esc_html__('Fridge Provider', 'pexpress'); ?></h3>
                <?php $current_fridge_status = $fridge_status ? $fridge_status : 'pending'; ?>
                <span class="polar-status-badge polar-status-<?php echo esc_attr(pexpress_get_status_class($current_fridge_status)); ?>" data-status="<?php echo esc_attr($current_fridge_status); ?>">
                    <?php echo esc_html(pexpress_get_status_label('fridge', $current_fridge_status, $status_labels)); ?>
                </span>
            </div>
            <?php if ($fridge_user_name) : ?>
                <div class="polar-assigned-user">
                    <span class="polar-user-label"><?php echo esc_html__('Assigned to:', 'pexpress'); ?></span>
                    <span class="polar-user-name"><?php echo esc_html($fridge_user_name); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($fridge_last_update) : ?>
                <div class="polar-status-timestamp">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 4px;">
                        <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span><?php echo esc_html__('Updated:', 'pexpress') . ' ' . esc_html($fridge_last_update); ?></span>
                </div>
            <?php endif; ?>
            <div class="polar-status-progress">
                <div class="polar-progress-bar">
                    <?php
                    $current_fridge_status = $fridge_status ? $fridge_status : 'pending';
                    $fridge_progress = 0;
                    if ($current_fridge_status === 'fridge_drop') {
                        $fridge_progress = 33;
                    } elseif ($current_fridge_status === 'fridge_collected') {
                        $fridge_progress = 66;
                    } elseif ($current_fridge_status === 'fridge_returned') {
                        $fridge_progress = 100;
                    }
                    ?>
                    <div class="polar-progress-fill polar-progress-<?php echo esc_attr(pexpress_get_status_class($current_fridge_status)); ?>" style="width: <?php echo esc_attr($fridge_progress); ?>%"></div>
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

