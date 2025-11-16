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

<?php
// Calculate stats
if (!is_array($assigned_orders)) {
    $assigned_orders = array();
}

$distributor_groups = array(
    'pending'     => array(),
    'in_progress' => array(),
    'completed'   => array(),
);

$distributor_pending_statuses = array('processing', 'pending', 'on-hold', 'wc-polar-assigned');
$distributor_in_progress_statuses = array('wc-polar-distributor-prep', 'wc-polar-out');
$distributor_completed_statuses = array('wc-polar-distributor-complete', 'wc-polar-service-progress', 'wc-polar-service-complete', 'wc-polar-complete', 'completed');

if (!function_exists('pexpress_status_matches')) {
    /**
     * Compare order status with list of candidates while ignoring WooCommerce prefixes.
     *
     * @param string       $actual_status Actual status slug.
     * @param string|array $expected_statuses Candidate statuses.
     * @return bool
     */
    function pexpress_status_matches($actual_status, $expected_statuses)
    {
        if (!is_array($expected_statuses)) {
            $expected_statuses = array($expected_statuses);
        }

        $actual_status = (string) $actual_status;
        $normalized_actual = str_replace('wc-', '', $actual_status);

        foreach ($expected_statuses as $candidate) {
            $candidate = (string) $candidate;
            $normalized_candidate = str_replace('wc-', '', $candidate);

            if (
                $actual_status === $candidate
                || $actual_status === 'wc-' . $candidate
                || $normalized_actual === $candidate
                || $normalized_actual === $normalized_candidate
            ) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('pexpress_distributor_action_icon')) {
    /**
     * SVG icon helper for distributor actions.
     *
     * @param string $type Icon type key.
     * @return string
     */
    function pexpress_distributor_action_icon($type)
    {
        switch ($type) {
            case 'prep':
                return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5V3M12 21V19M5 12H3M21 12H19M5.63604 18.364L4.22183 19.7782M19.7782 4.22183L18.364 5.63604M5.63604 5.63604L4.22183 4.22183M19.7782 19.7782L18.364 18.364" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'delivery':
                return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 10V3L4 14H11V21L20 10H13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'complete':
            default:
                return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        }
    }
}

foreach ($assigned_orders as $order) {
    if (!$order || !is_a($order, 'WC_Order')) {
        continue;
    }

    $order_id = $order->get_id();
    // Use per-role status instead of WC status
    $role_status = PExpress_Core::get_role_status($order_id, 'distributor');

    if ($role_status === 'handoff_complete') {
        $distributor_groups['completed'][] = $order;
    } elseif (in_array($role_status, array('distributor_prep', 'out_for_delivery'), true)) {
        $distributor_groups['in_progress'][] = $order;
    } else {
        $distributor_groups['pending'][] = $order;
    }
}

$pending_tasks = $distributor_groups['pending'];
$in_progress_tasks = $distributor_groups['in_progress'];
$completed_tasks = $distributor_groups['completed'];

$pending_total = count($pending_tasks);
$in_progress_total = count($in_progress_tasks);
$completed_total = count($completed_tasks);
?>

<div class="wrap polar-dashboard polar-distributor-dashboard">
    <div class="polar-dashboard-header">
        <div class="polar-header-content">
            <h1 class="polar-dashboard-title">
                <span class="polar-title-icon">📦</span>
                <?php esc_html_e('Product Provider Dashboard', 'pexpress'); ?>
            </h1>
            <p class="polar-dashboard-subtitle"><?php esc_html_e('Manage product distribution tasks and fulfill orders', 'pexpress'); ?></p>
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
                <h3 class="stat-card-value"><?php echo esc_html($pending_total); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Pending Tasks', 'pexpress'); ?></p>
            </div>
        </div>
        <div class="polar-stat-card stat-card-warning">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($in_progress_total); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('In Progress', 'pexpress'); ?></p>
            </div>
        </div>
        <div class="polar-stat-card stat-card-success">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($completed_total); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Completed Orders', 'pexpress'); ?></p>
            </div>
        </div>
    </div>

    <div class="polar-tasks-section">
        <div class="polar-section-header">
            <h2 class="polar-section-title"><?php esc_html_e('My Distribution Tasks', 'pexpress'); ?></h2>
        </div>

        <?php
        if (!function_exists('pexpress_render_distributor_task_card')) {
            function pexpress_render_distributor_task_card($order, $show_action = true, $note = '')
            {
                if (!$order || !is_a($order, 'WC_Order')) {
                    return;
                }

                $order_id            = $order->get_id();
                // Get per-role status
                $role_status = PExpress_Core::get_role_status($order_id, 'distributor');
                $order_status        = $order->get_status();
                // Map role status to display label
                $status_labels = array(
                    'pending' => __('Pending', 'pexpress'),
                    'distributor_prep' => __('Distributor Preparing', 'pexpress'),
                    'out_for_delivery' => __('Out for Delivery', 'pexpress'),
                    'handoff_complete' => __('Handoff Complete', 'pexpress'),
                );
                $order_status_label  = $status_labels[$role_status] ?? wc_get_order_status_name($order_status);
                $order_date_obj      = $order->get_date_created();
                $order_date          = $order_date_obj ? $order_date_obj->date_i18n('M d, Y') : '';
                $items               = $order->get_items();
                $meeting_type        = PExpress_Core::get_meeting_type($order_id);
                $meeting_location    = PExpress_Core::get_meeting_location($order_id);
                $meeting_datetime    = PExpress_Core::get_meeting_datetime($order_id);
                $meeting_timestamp   = $meeting_datetime ? strtotime($meeting_datetime) : false;
                $meeting_display     = $meeting_timestamp ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $meeting_timestamp) : $meeting_datetime;
                $meeting_labels      = array(
                    'meet_point'        => __('Meet Point', 'pexpress'),
                    'delivery_location' => __('Delivery Location', 'pexpress'),
                );
                $meeting_label       = isset($meeting_labels[$meeting_type]) ? $meeting_labels[$meeting_type] : $meeting_labels['meet_point'];
                $distributor_note    = PExpress_Core::get_role_instructions($order_id, 'distributor');
                $assignment_note     = PExpress_Core::get_order_meta($order_id, '_polar_assignment_note');

                $available_actions = array();
                if ($show_action) {
                    // Use per-role status for determining available actions
                    if ($role_status === 'pending') {
                        $available_actions[] = array(
                            'value' => 'distributor_prep',
                            'label' => __('Start Preparing Products', 'pexpress'),
                            'icon'  => pexpress_distributor_action_icon('prep'),
                            'class' => 'polar-btn-primary',
                        );
                    }

                    if ($role_status === 'distributor_prep') {
                        $available_actions[] = array(
                            'value' => 'out_for_delivery',
                            'label' => __('Mark Out for Delivery', 'pexpress'),
                            'icon'  => pexpress_distributor_action_icon('delivery'),
                            'class' => 'polar-btn-warning',
                        );
                    }

                    if ($role_status === 'out_for_delivery') {
                        $available_actions[] = array(
                            'value' => 'handoff_complete',
                            'label' => __('Confirm Handoff Complete', 'pexpress'),
                            'icon'  => pexpress_distributor_action_icon('complete'),
                            'class' => 'polar-btn-success',
                        );
                    }
                }
        ?>
                <div class="polar-task-item" data-order-id="<?php echo esc_attr($order_id); ?>">
                    <div class="task-header">
                        <div class="order-header-left">
                            <h4 class="order-title">
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" target="_blank" class="order-link">
                                    <span class="order-id-badge">#<?php echo esc_html($order_id); ?></span>
                                </a>
                            </h4>
                            <?php if (!empty($order_date)) : ?>
                                <span class="order-date-badge">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <?php echo esc_html($order_date); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="task-status status-<?php echo esc_attr($role_status); ?>">
                            <?php echo esc_html($order_status_label); ?>
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
                                        <path d="M20 7H4C2.89543 7 2 7.89543 2 9V19C2 20.1046 2.89543 21 4 21H20C21.1046 21 22 20.1046 22 19V9C22 7.89543 21.1046 7 20 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M16 21V13C16 11.8954 15.1046 11 14 11H10C8.89543 11 8 11.8954 8 13V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div class="detail-content">
                                    <span class="detail-label"><?php esc_html_e('Items', 'pexpress'); ?></span>
                                    <div class="detail-value">
                                        <ul class="order-items" style="list-style: none; padding: 0; margin: 8px 0 0 0;">
                                            <?php foreach ($items as $item) : ?>
                                                <li style="margin: 4px 0; font-size: 13px;">
                                                    <?php echo esc_html($item->get_name()); ?>
                                                    <strong style="color: #667eea;"> x <?php echo esc_html($item->get_quantity()); ?></strong>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="order-detail-row">
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
                                    <span class="detail-value order-total"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($meeting_location) || !empty($meeting_display)) : ?>
                            <div class="order-detail-row">
                                <div class="order-detail-item order-detail-full">
                                    <span class="detail-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C8.13401 2 5 5.13401 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13401 15.866 2 12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M12 11C13.6569 11 15 9.65685 15 8C15 6.34315 13.6569 5 12 5C10.3431 5 9 6.34315 9 8C9 9.65685 10.3431 11 12 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <div class="detail-content">
                                        <span class="detail-label">
                                            <?php esc_html_e('Meetup Plan', 'pexpress'); ?>
                                            <span class="detail-badge"><?php echo esc_html($meeting_label); ?></span>
                                        </span>
                                        <?php if (!empty($meeting_location)) : ?>
                                            <span class="detail-value"><?php echo esc_html($meeting_location); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($meeting_display)) : ?>
                                            <span class="detail-meta"><?php echo esc_html($meeting_display); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($distributor_note) || !empty($assignment_note)) : ?>
                            <div class="order-detail-row order-detail-note">
                                <?php if (!empty($distributor_note)) : ?>
                                    <div class="order-detail-item order-detail-full">
                                        <span class="detail-label"><?php esc_html_e('Distributor Instructions', 'pexpress'); ?></span>
                                        <p class="detail-note"><?php echo nl2br(esc_html($distributor_note)); ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($assignment_note)) : ?>
                                    <div class="order-detail-item order-detail-full">
                                        <span class="detail-label"><?php esc_html_e('SR Notes', 'pexpress'); ?></span>
                                        <p class="detail-note"><?php echo nl2br(esc_html($assignment_note)); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="task-actions">
                        <?php if ($show_action && !empty($available_actions)) : ?>
                            <form class="polar-distributor-status-form" data-order-id="<?php echo esc_attr($order_id); ?>">
                                <?php wp_nonce_field('polar_distributor_status_' . $order_id, 'polar_distributor_nonce'); ?>
                                <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                                <?php foreach ($available_actions as $action) : ?>
                                    <button type="submit" name="status" value="<?php echo esc_attr($action['value']); ?>" class="polar-btn <?php echo esc_attr($action['class']); ?>" data-original-text="<?php echo esc_attr($action['label']); ?>">
                                        <?php echo $action['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                        ?>
                                        <?php echo esc_html($action['label']); ?>
                                    </button>
                                <?php endforeach; ?>
                                <span class="polar-update-loading" style="display:none;"><?php esc_html_e('Updating...', 'pexpress'); ?></span>
                            </form>
                            <?php if (!empty($note)) : ?>
                                <p class="polar-task-hint"><?php echo esc_html($note); ?></p>
                            <?php endif; ?>
                        <?php elseif (!empty($note)) : ?>
                            <p class="polar-task-hint"><?php echo esc_html($note); ?></p>
                        <?php else : ?>
                            <p class="polar-task-hint"><?php esc_html_e('No further actions available right now.', 'pexpress'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
        <?php
            }
        }
        ?>

        <div class="polar-tabs">
            <button class="polar-tab active" data-tab="pending"><?php esc_html_e('Pending', 'pexpress'); ?></button>
            <button class="polar-tab" data-tab="in-progress"><?php esc_html_e('In Progress', 'pexpress'); ?></button>
            <button class="polar-tab" data-tab="completed"><?php esc_html_e('Completed', 'pexpress'); ?></button>
        </div>

        <div class="polar-tab-content active" id="tab-pending">
            <div class="polar-tasks-list" id="polar-distributor-pending">
                <?php if (!empty($pending_tasks)) : ?>
                    <?php foreach ($pending_tasks as $order) :
                        $pending_note = esc_html__('Confirm when prep begins so the team stays aligned.', 'pexpress');
                        pexpress_render_distributor_task_card($order, true, $pending_note);
                    endforeach; ?>
                <?php else : ?>
                    <div class="polar-empty-state">
                        <div class="empty-state-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <h3><?php esc_html_e('No pending tasks', 'pexpress'); ?></h3>
                        <p><?php esc_html_e('You have no pending distribution tasks at this time.', 'pexpress'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="polar-tab-content" id="tab-in-progress">
            <div class="polar-tasks-list" id="polar-distributor-in-progress">
                <?php if (!empty($in_progress_tasks)) : ?>
                    <?php foreach ($in_progress_tasks as $order) :
                        pexpress_render_distributor_task_card($order, true);
                    endforeach; ?>
                <?php else : ?>
                    <div class="polar-empty-state">
                        <div class="empty-state-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <h3><?php esc_html_e('No in-progress tasks', 'pexpress'); ?></h3>
                        <p><?php esc_html_e('No distribution tasks are currently in progress.', 'pexpress'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="polar-tab-content" id="tab-completed">
            <div class="polar-tasks-list" id="polar-distributor-completed">
                <?php if (!empty($completed_tasks)) : ?>
                    <?php foreach ($completed_tasks as $order) :
                        $completed_note = esc_html__('Fulfillment completed. No further action required.', 'pexpress');
                        pexpress_render_distributor_task_card($order, false, $completed_note);
                    endforeach; ?>
                <?php else : ?>
                    <div class="polar-empty-state">
                        <div class="empty-state-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <h3><?php esc_html_e('No completed tasks yet', 'pexpress'); ?></h3>
                        <p><?php esc_html_e('No distribution tasks have been completed yet.', 'pexpress'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>