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

// Ensure $recent_orders is always an array
if (!is_array($recent_orders)) {
    $recent_orders = array();
}

// Helper function to get order edit URL that works from both admin and frontend
if (!function_exists('pexpress_get_order_edit_url')) {
    function pexpress_get_order_edit_url($order_id)
    {
        $order_id = absint($order_id);
        if (!$order_id) {
            return '';
        }

        // Always use admin_url() which generates the correct URL
        $admin_base = admin_url('admin.php');
        $url = add_query_arg(
            array(
                'page' => 'polar-express-order-edit',
                'order_id' => $order_id,
            ),
            $admin_base
        );

        return $url;
    }
}

// Calculate stats
$total_orders = count($recent_orders);
$processing_count = count(array_filter($recent_orders, function ($o) {
    return $o && is_a($o, 'WC_Order') && $o->get_status() === 'processing';
}));
$completed_count = count(array_filter($recent_orders, function ($o) {
    return $o && is_a($o, 'WC_Order') && $o->get_status() === 'completed';
}));
$delivery_count = count(array_filter($recent_orders, function ($o) {
    return $o && is_a($o, 'WC_Order') && in_array($o->get_status(), array('wc-polar-out', 'wc-polar-delivered'));
}));
?>

<div class="wrap polar-dashboard polar-support-dashboard">
    <div class="polar-dashboard-header">
        <div class="polar-header-content">
            <h1 class="polar-dashboard-title">
                <span class="polar-title-icon">🎧</span>
                <?php esc_html_e('Customer Support Dashboard', 'pexpress'); ?>
            </h1>
            <p class="polar-dashboard-subtitle"><?php esc_html_e('Manage and track all customer orders', 'pexpress'); ?></p>
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
                <h3 class="stat-card-value"><?php echo esc_html($total_orders); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Recent Orders', 'pexpress'); ?></p>
            </div>
        </div>
        <div class="polar-stat-card stat-card-warning">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($processing_count); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Processing', 'pexpress'); ?></p>
            </div>
        </div>
        <div class="polar-stat-card stat-card-success">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($completed_count); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Completed', 'pexpress'); ?></p>
            </div>
        </div>
        <div class="polar-stat-card stat-card-info">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 7H16M8 12H16M8 17H16M3 3H21C21.5523 3 22 3.44772 22 4V20C22 20.5523 21.5523 21 21 21H3C2.44772 21 2 20.5523 2 20V4C2 3.44772 2.44772 3 3 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($delivery_count); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('In Delivery', 'pexpress'); ?></p>
            </div>
        </div>
    </div>

    <div class="polar-orders-section">
        <div class="polar-section-header">
            <h2 class="polar-section-title"><?php esc_html_e('Recent Orders', 'pexpress'); ?></h2>
        </div>

        <div class="polar-filters-wrapper">
            <div class="polar-filters">
                <div class="polar-filter-group">
                    <label class="polar-filter-label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 4H21M7 8H17M10 12H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        <?php esc_html_e('Filter', 'pexpress'); ?>
                    </label>
                    <select id="polar-status-filter" class="polar-select">
                        <option value=""><?php esc_html_e('All Statuses', 'pexpress'); ?></option>
                        <?php
                        $statuses = wc_get_order_statuses();
                        if (is_array($statuses)) {
                            foreach ($statuses as $status_key => $status_label) :
                                if (empty($status_key) || empty($status_label)) {
                                    continue;
                                }
                        ?>
                                <option value="<?php echo esc_attr($status_key); ?>">
                                    <?php echo esc_html($status_label); ?>
                                </option>
                        <?php
                            endforeach;
                        }
                        ?>
                    </select>
                </div>
                <div class="polar-filter-group polar-search-group">
                    <label class="polar-filter-label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <?php esc_html_e('Search', 'pexpress'); ?>
                    </label>
                    <input type="text" id="polar-search" class="polar-input" placeholder="<?php esc_attr_e('Search by order ID, customer name, or phone...', 'pexpress'); ?>">
                </div>
            </div>
        </div>

        <div class="polar-orders-list" id="polar-support-orders">
            <?php if (!empty($recent_orders)) : ?>
                <?php foreach ($recent_orders as $order) :
                    // Skip invalid orders
                    if (!$order || !is_a($order, 'WC_Order')) {
                        continue;
                    }

                    $order_id = $order->get_id();
                    $order_status = $order->get_status() ?: '';
                    $delivery_id = PExpress_Core::get_delivery_user_id($order_id);
                    $fridge_id = PExpress_Core::get_fridge_user_id($order_id);
                    $distributor_id = PExpress_Core::get_distributor_user_id($order_id);

                    // Get order date safely
                    $order_date = '';
                    $date_created = $order->get_date_created();
                    if ($date_created) {
                        $order_date = $date_created->date_i18n('M d, Y') ?: '';
                    }

                    // Get billing info safely
                    $billing_email = $order->get_billing_email() ?: '';
                    $billing_phone = $order->get_billing_phone() ?: '';

                    // Get order status name safely
                    $order_status_name = '';
                    if (!empty($order_status)) {
                        $status_name = wc_get_order_status_name($order_status);
                        $order_status_name = $status_name ?: $order_status;
                    }

                    // Get formatted order total safely
                    $formatted_total = $order->get_formatted_order_total() ?: '';
                ?>
                    <div class="polar-order-item" data-order-id="<?php echo esc_attr($order_id); ?>" data-status="<?php echo esc_attr($order_status); ?>">
                        <div class="order-header">
                            <div class="order-header-left">
                                <h4 class="order-title">
                                    <a href="<?php echo esc_url(pexpress_get_order_edit_url($order_id)); ?>" class="order-link">
                                        <span class="order-id-badge">#<?php echo esc_html($order_id); ?></span>
                                    </a>
                                </h4>
                                <span class="order-date-badge">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <?php echo esc_html($order_date); ?>
                                </span>
                            </div>
                            <span class="order-status status-<?php echo esc_attr($order_status); ?>">
                                <?php echo esc_html($order_status_name); ?>
                            </span>
                        </div>
                        <div class="order-details">
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
                                        <span class="detail-value customer-name"><?php echo esc_html(PExpress_Core::get_billing_name($order)); ?></span>
                                    </div>
                                </div>
                                <div class="order-detail-item">
                                    <span class="detail-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <div class="detail-content">
                                        <span class="detail-label"><?php esc_html_e('Email', 'pexpress'); ?></span>
                                        <?php if (!empty($billing_email)) : ?>
                                            <a href="mailto:<?php echo esc_attr($billing_email); ?>" class="detail-value detail-link">
                                                <?php echo esc_html($billing_email); ?>
                                            </a>
                                        <?php else : ?>
                                            <span class="detail-value"><?php esc_html_e('N/A', 'pexpress'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="order-detail-row">
                                <div class="order-detail-item">
                                    <span class="detail-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 5C3 3.89543 3.89543 3 5 3H8.27924C8.70967 3 9.09181 3.27543 9.22792 3.68377L10.7257 8.17721C10.8831 8.64932 10.6694 9.16531 10.2243 9.38787L7.96701 10.5165C9.06925 12.9612 11.0388 14.9308 13.4835 16.033L14.6121 13.7757C14.8347 13.3306 15.3507 13.1169 15.8228 13.2743L20.3162 14.7721C20.7246 14.9082 21 15.2903 21 15.7208V19C21 20.1046 20.1046 21 19 21H18C9.71573 21 3 14.2843 3 6V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <div class="detail-content">
                                        <span class="detail-label"><?php esc_html_e('Phone', 'pexpress'); ?></span>
                                        <?php if (!empty($billing_phone)) : ?>
                                            <a href="tel:<?php echo esc_attr($billing_phone); ?>" class="detail-value detail-link phone-number">
                                                <?php echo esc_html($billing_phone); ?>
                                            </a>
                                        <?php else : ?>
                                            <span class="detail-value"><?php esc_html_e('N/A', 'pexpress'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="order-detail-item">
                                    <span class="detail-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 8C12.5523 8 13 8.44772 13 9V13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13V9C11 8.44772 11.4477 8 12 8Z" fill="currentColor" />
                                            <path d="M12 6C12.5523 6 13 5.55228 13 5C13 4.44772 12.5523 4 12 4C11.4477 4 11 4.44772 11 5C11 5.55228 11.4477 6 12 6Z" fill="currentColor" />
                                            <path d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM4 12C4 7.58172 7.58172 4 12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12Z" fill="currentColor" />
                                        </svg>
                                    </span>
                                    <div class="detail-content">
                                        <span class="detail-label"><?php esc_html_e('Total', 'pexpress'); ?></span>
                                        <span class="detail-value order-total"><?php echo wp_kses_post($formatted_total); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php if ($delivery_id || $fridge_id || $distributor_id) : ?>
                                <div class="assignment-info">
                                    <div class="assignment-header">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88M13 7C13 9.20914 10.2091 11 8 11C5.79086 11 3 9.20914 3 7C3 4.79086 5.79086 3 8 3C10.2091 3 13 4.79086 13 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <span><?php esc_html_e('Assignments', 'pexpress'); ?></span>
                                    </div>
                                    <div class="assignment-badges">
                                        <?php if ($delivery_id) :
                                            $delivery_user = get_userdata($delivery_id);
                                            if ($delivery_user) :
                                        ?>
                                                <span class="assignment-badge badge-delivery">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M5 13L12 20L19 13M12 4V20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <?php esc_html_e('Delivery:', 'pexpress'); ?> <?php echo esc_html($delivery_user->display_name); ?>
                                                </span>
                                        <?php
                                            endif;
                                        endif; ?>
                                        <?php if ($fridge_id) :
                                            $fridge_user = get_userdata($fridge_id);
                                            if ($fridge_user) :
                                        ?>
                                                <span class="assignment-badge badge-fridge">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M5 13L12 20L19 13M12 4V20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <?php esc_html_e('Fridge:', 'pexpress'); ?> <?php echo esc_html($fridge_user->display_name); ?>
                                                </span>
                                        <?php
                                            endif;
                                        endif; ?>
                                        <?php if ($distributor_id) :
                                            $distributor_user = get_userdata($distributor_id);
                                            if ($distributor_user) :
                                        ?>
                                                <span class="assignment-badge badge-distributor">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M5 13L12 20L19 13M12 4V20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <?php esc_html_e('Distributor:', 'pexpress'); ?> <?php echo esc_html($distributor_user->display_name); ?>
                                                </span>
                                        <?php
                                            endif;
                                        endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Real-time Status Cards -->
                        <?php
                        $agency_status = PExpress_Core::get_role_status($order_id, 'agency');
                        $delivery_status = PExpress_Core::get_role_status($order_id, 'delivery');
                        $fridge_status = PExpress_Core::get_role_status($order_id, 'fridge');
                        $distributor_status = PExpress_Core::get_role_status($order_id, 'distributor');

                        // Status labels
                        $status_labels = array(
                            'agency' => array(
                                'pending' => __('Pending', 'pexpress'),
                                'assigned' => __('Assigned', 'pexpress'),
                                'proceeded' => __('Proceeded', 'pexpress'),
                            ),
                            'delivery' => array(
                                'pending' => __('Pending', 'pexpress'),
                                'meet_point_arrived' => __('Meet Point Arrived', 'pexpress'),
                                'delivery_location_arrived' => __('Delivery Location Arrived', 'pexpress'),
                                'service_in_progress' => __('Service In Progress', 'pexpress'),
                                'service_complete' => __('Service Complete', 'pexpress'),
                                'customer_served' => __('Customer Served', 'pexpress'),
                            ),
                            'fridge' => array(
                                'pending' => __('Pending', 'pexpress'),
                                'fridge_drop' => __('Fridge Dropped', 'pexpress'),
                                'fridge_collected' => __('Fridge Collected', 'pexpress'),
                                'fridge_returned' => __('Fridge Returned', 'pexpress'),
                            ),
                            'distributor' => array(
                                'pending' => __('Pending', 'pexpress'),
                                'distributor_prep' => __('Preparing Products', 'pexpress'),
                                'out_for_delivery' => __('Out for Delivery', 'pexpress'),
                                'handoff_complete' => __('Handoff Complete', 'pexpress'),
                            ),
                        );

                        $get_status_label = function ($role, $status) use ($status_labels) {
                            return isset($status_labels[$role][$status]) ? $status_labels[$role][$status] : ucfirst(str_replace('_', ' ', $status));
                        };

                        $get_status_class = function ($status) {
                            if (in_array($status, array('customer_served', 'handoff_complete', 'fridge_returned', 'proceeded'), true)) {
                                return 'success';
                            } elseif (in_array($status, array('pending'), true)) {
                                return 'pending';
                            } else {
                                return 'in-progress';
                            }
                        };
                        ?>
                        <div class="polar-realtime-status" data-order-id="<?php echo esc_attr($order_id); ?>" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e5e5;">
                            <h5 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #1d2327;"><?php esc_html_e('Real-time Status', 'pexpress'); ?></h5>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                                <?php if ($agency_status) : ?>
                                    <div class="polar-status-mini-card" data-role="agency">
                                        <div style="font-size: 11px; color: #646970; margin-bottom: 4px;"><?php esc_html_e('Agency', 'pexpress'); ?></div>
                                        <div class="polar-status-badge-mini polar-status-<?php echo esc_attr($get_status_class($agency_status)); ?>" style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                            <?php echo esc_html($get_status_label('agency', $agency_status)); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($distributor_status && $distributor_id) : ?>
                                    <div class="polar-status-mini-card" data-role="distributor">
                                        <div style="font-size: 11px; color: #646970; margin-bottom: 4px;"><?php esc_html_e('Product Provider', 'pexpress'); ?></div>
                                        <div class="polar-status-badge-mini polar-status-<?php echo esc_attr($get_status_class($distributor_status)); ?>" style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                            <?php echo esc_html($get_status_label('distributor', $distributor_status)); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($delivery_status && $delivery_id) : ?>
                                    <div class="polar-status-mini-card" data-role="delivery">
                                        <div style="font-size: 11px; color: #646970; margin-bottom: 4px;"><?php esc_html_e('Delivery', 'pexpress'); ?></div>
                                        <div class="polar-status-badge-mini polar-status-<?php echo esc_attr($get_status_class($delivery_status)); ?>" style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                            <?php echo esc_html($get_status_label('delivery', $delivery_status)); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($fridge_status && $fridge_id) : ?>
                                    <div class="polar-status-mini-card" data-role="fridge">
                                        <div style="font-size: 11px; color: #646970; margin-bottom: 4px;"><?php esc_html_e('Fridge', 'pexpress'); ?></div>
                                        <div class="polar-status-badge-mini polar-status-<?php echo esc_attr($get_status_class($fridge_status)); ?>" style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                            <?php echo esc_html($get_status_label('fridge', $fridge_status)); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php
                        // Check if current user can edit orders
                        // Support and HR users should always see the button
                        $current_user = wp_get_current_user();
                        $can_edit_order = false;
                        if ($current_user && !empty($current_user->roles)) {
                            $can_edit_order = in_array('polar_support', $current_user->roles) ||
                                in_array('polar_hr', $current_user->roles) ||
                                current_user_can('edit_shop_orders');
                        }
                        ?>
                        <?php if ($can_edit_order) : ?>
                            <div class="order-actions">
                                <a href="<?php echo esc_url(pexpress_get_order_edit_url($order_id)); ?>" class="polar-btn polar-btn-primary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.10218 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10218 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10218 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <?php esc_html_e('Edit Order', 'pexpress'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="polar-empty-state">
                    <div class="empty-state-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3><?php esc_html_e('No orders found', 'pexpress'); ?></h3>
                    <p><?php esc_html_e('There are no orders to display at the moment.', 'pexpress'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>