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
if (!is_array($assigned_orders)) {
    $assigned_orders = array();
}

$fridge_groups = array(
    'pending'     => array(),
    'in_progress' => array(),
    'completed'   => array(),
);

$fridge_pending_statuses = array(
    'processing',
    'pending',
    'on-hold',
    'wc-polar-assigned',
    'wc-polar-distributor-prep',
    'wc-polar-out',
    'wc-polar-meet-point',
    'wc-polar-delivery-location',
    'wc-polar-service-progress',
);
$fridge_in_progress_statuses = array('wc-polar-fridge-drop', 'wc-polar-fridge-back');
$fridge_completed_statuses = array('wc-polar-fridge-returned', 'wc-polar-complete', 'completed');

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

if (!function_exists('pexpress_fridge_action_icon')) {
    /**
     * Provide SVG icon markup for fridge actions.
     *
     * @param string $type Icon key.
     * @return string
     */
    function pexpress_fridge_action_icon($type)
    {
        switch ($type) {
            case 'drop':
                return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 3H20C20.5523 3 21 3.44772 21 4V20C21 20.5523 20.5523 21 20 21H4C3.44772 21 3 20.5523 3 20V4C3 3.44772 3.44772 3 4 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 3V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 3V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'collect':
                return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7H21M3 12H21M3 17H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'return':
            default:
                return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 10V8C21 6.93913 20.5786 5.92172 19.8284 5.17157C19.0783 4.42143 18.0609 4 17 4H6M3 14V16C3 17.0609 3.42143 18.0783 4.17157 18.8284C4.92172 19.5786 5.93913 20 7 20H18M7 4L4 7M7 4L4 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        }
    }
}

foreach ($assigned_orders as $order) {
    if (!$order || !is_a($order, 'WC_Order')) {
        continue;
    }

    $order_id = $order->get_id();
    // Use per-role status instead of WC status
    $role_status = PExpress_Core::get_role_status($order_id, 'fridge');

    if ($role_status === 'fridge_returned') {
        $fridge_groups['completed'][] = $order;
    } elseif (in_array($role_status, array('fridge_drop', 'fridge_collected'), true)) {
        $fridge_groups['in_progress'][] = $order;
    } else {
        $fridge_groups['pending'][] = $order;
    }
}

$pending_tasks = $fridge_groups['pending'];
$in_progress_tasks = $fridge_groups['in_progress'];
$completed_tasks = $fridge_groups['completed'];

$pending_count = count($pending_tasks);
$in_progress_count = count($in_progress_tasks);
$collected_count = count($completed_tasks);
?>

<?php
if (!function_exists('pexpress_render_fridge_task_card')) {
    /**
     * Render fridge provider task card with contextual actions.
     *
     * @param WC_Order $order        WooCommerce order.
     * @param bool     $show_actions Whether to render action buttons.
     * @param string   $context_note Optional hint text.
     * @return void
     */
    function pexpress_render_fridge_task_card($order, $show_actions = true, $context_note = '')
    {
        if (!$order || !is_a($order, 'WC_Order')) {
            return;
        }

        $order_id           = $order->get_id();
        // Get per-role status
        $role_status = PExpress_Core::get_role_status($order_id, 'fridge');
        $order_status       = $order->get_status();
        // Map role status to display label
        $status_labels = array(
            'pending' => __('Pending', 'pexpress'),
            'fridge_drop' => __('Fridge Delivered On-site', 'pexpress'),
            'fridge_collected' => __('Fridge Collected On-site', 'pexpress'),
            'fridge_returned' => __('Fridge Returned to Base', 'pexpress'),
        );
        $order_status_label = $status_labels[$role_status] ?? wc_get_order_status_name($order_status);
        $order_date_obj     = $order->get_date_created();
        $order_date         = $order_date_obj ? $order_date_obj->date_i18n('M d, Y') : '';
        $meeting_type       = PExpress_Core::get_meeting_type($order_id);
        $meeting_location   = PExpress_Core::get_meeting_location($order_id);
        $meeting_datetime   = PExpress_Core::get_meeting_datetime($order_id);
        $meeting_timestamp  = $meeting_datetime ? strtotime($meeting_datetime) : false;
        $meeting_display    = $meeting_timestamp ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $meeting_timestamp) : $meeting_datetime;
        $meeting_labels     = array(
            'meet_point'        => __('Meet Point', 'pexpress'),
            'delivery_location' => __('Delivery Location', 'pexpress'),
        );
        $meeting_label = isset($meeting_labels[$meeting_type]) ? $meeting_labels[$meeting_type] : $meeting_labels['meet_point'];

        $fridge_asset_id    = PExpress_Core::get_fridge_asset_id($order_id);
        $fridge_return_raw  = PExpress_Core::get_order_meta($order_id, '_polar_fridge_return_date');
        $fridge_return_time = $fridge_return_raw ? strtotime($fridge_return_raw) : false;
        $fridge_return_display = $fridge_return_time ? date_i18n(get_option('date_format'), $fridge_return_time) : $fridge_return_raw;
        $fridge_instructions = PExpress_Core::get_role_instructions($order_id, 'fridge');
        $assignment_note     = PExpress_Core::get_order_meta($order_id, '_polar_assignment_note');

        $available_actions = array();
        if ($show_actions) {
            // Use per-role status for determining available actions
            if ($role_status === 'pending') {
                $available_actions[] = array(
                    'value' => 'fridge_drop',
                    'label' => __('Confirm Fridge Delivered', 'pexpress'),
                    'icon'  => pexpress_fridge_action_icon('drop'),
                    'class' => 'polar-btn-primary',
                );
            }

            if ($role_status === 'fridge_drop') {
                $available_actions[] = array(
                    'value' => 'fridge_collected',
                    'label' => __('Mark Fridge Collected On-site', 'pexpress'),
                    'icon'  => pexpress_fridge_action_icon('collect'),
                    'class' => 'polar-btn-warning',
                );
            }

            if ($role_status === 'fridge_collected') {
                $available_actions[] = array(
                    'value' => 'fridge_returned',
                    'label' => __('Confirm Fridge Returned to Base', 'pexpress'),
                    'icon'  => pexpress_fridge_action_icon('return'),
                    'class' => 'polar-btn-success',
                );
            }
        }

        ob_start();
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
                                <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5523 5.44772 21 6 21H9M19 10L21 12M19 10V20C19 20.5523 18.5523 21 18 21H15M9 21C9.55228 21 10 20.5523 10 20V16C10 15.4477 10.4477 15 11 15H13C13.5523 15 14 15.4477 14 16V20C14 20.5523 14.4477 21 15 21M9 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div class="detail-content">
                            <span class="detail-label"><?php esc_html_e('Delivery Address', 'pexpress'); ?></span>
                            <span class="detail-value"><?php echo wp_kses_post($order->get_formatted_billing_address()); ?></span>
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
                <?php if (!empty($fridge_asset_id) || !empty($fridge_return_display)) : ?>
                    <div class="order-detail-row">
                        <?php if (!empty($fridge_asset_id)) : ?>
                            <div class="order-detail-item">
                                <span class="detail-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 7H4C2.89543 7 2 7.89543 2 9V19C2 20.1046 2.89543 21 4 21H20C21.1046 21 22 20.1046 22 19V9C22 7.89543 21.1046 7 20 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M16 21V13C16 11.8954 15.1046 11 14 11H10C8.89543 11 8 11.8954 8 13V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div class="detail-content">
                                    <span class="detail-label"><?php esc_html_e('Fridge Asset ID', 'pexpress'); ?></span>
                                    <span class="detail-value"><?php echo esc_html($fridge_asset_id); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($fridge_return_display)) : ?>
                            <div class="order-detail-item">
                                <span class="detail-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div class="detail-content">
                                    <span class="detail-label"><?php esc_html_e('Scheduled Return Date', 'pexpress'); ?></span>
                                    <span class="detail-value"><?php echo esc_html($fridge_return_display); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($fridge_instructions) || !empty($assignment_note)) : ?>
                    <div class="order-detail-row order-detail-note">
                        <?php if (!empty($fridge_instructions)) : ?>
                            <div class="order-detail-item order-detail-full">
                                <span class="detail-label"><?php esc_html_e('Fridge Instructions', 'pexpress'); ?></span>
                                <p class="detail-note"><?php echo nl2br(esc_html($fridge_instructions)); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($assignment_note)) : ?>
                            <div class="order-detail-item order-detail-full">
                                <span class="detail-label"><?php esc_html_e('HR Notes', 'pexpress'); ?></span>
                                <p class="detail-note"><?php echo nl2br(esc_html($assignment_note)); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="task-actions">
                <?php if ($show_actions && !empty($available_actions)) : ?>
                    <form class="polar-fridge-status-form" data-order-id="<?php echo esc_attr($order_id); ?>">
                        <?php wp_nonce_field('polar_fridge_status_' . $order_id, 'polar_fridge_nonce'); ?>
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
                    <?php if (!empty($context_note)) : ?>
                        <p class="polar-task-hint"><?php echo esc_html($context_note); ?></p>
                    <?php endif; ?>
                <?php elseif (!empty($context_note)) : ?>
                    <p class="polar-task-hint"><?php echo esc_html($context_note); ?></p>
                <?php else : ?>
                    <p class="polar-task-hint"><?php esc_html_e('No further actions available at this stage.', 'pexpress'); ?></p>
                <?php endif; ?>
            </div>
        </div>
<?php
        echo ob_get_clean();
    }
}
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
                <h3 class="stat-card-value"><?php echo esc_html($pending_count); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Pending Tasks', 'pexpress'); ?></p>
            </div>
        </div>
        <div class="polar-stat-card stat-card-warning">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($in_progress_count); ?></h3>
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
                <h3 class="stat-card-value"><?php echo esc_html($collected_count); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Completed Collections', 'pexpress'); ?></p>
            </div>
        </div>
    </div>

    <div class="polar-tasks-section">
        <div class="polar-section-header">
            <h2 class="polar-section-title"><?php esc_html_e('My Fridge Tasks', 'pexpress'); ?></h2>
        </div>

        <div class="polar-tabs">
            <button class="polar-tab active" data-tab="pending"><?php esc_html_e('Pending', 'pexpress'); ?></button>
            <button class="polar-tab" data-tab="in-progress"><?php esc_html_e('In Progress', 'pexpress'); ?></button>
            <button class="polar-tab" data-tab="completed"><?php esc_html_e('Completed', 'pexpress'); ?></button>
        </div>

        <div class="polar-tab-content active" id="tab-pending">
            <div class="polar-tasks-list" id="polar-fridge-pending">
                <?php if (!empty($pending_tasks)) : ?>
                    <?php foreach ($pending_tasks as $order) :
                        pexpress_render_fridge_task_card($order, true, esc_html__('Confirm drop-off once the fridge is set up on-site.', 'pexpress'));
                    endforeach; ?>
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

        <div class="polar-tab-content" id="tab-in-progress">
            <div class="polar-tasks-list" id="polar-fridge-in-progress">
                <?php if (!empty($in_progress_tasks)) : ?>
                    <?php foreach ($in_progress_tasks as $order) :
                        pexpress_render_fridge_task_card($order, true, esc_html__('Collect the fridge once service is complete.', 'pexpress'));
                    endforeach; ?>
                <?php else : ?>
                    <div class="polar-empty-state">
                        <div class="empty-state-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <h3><?php esc_html_e('No in-progress collections', 'pexpress'); ?></h3>
                        <p><?php esc_html_e('No fridge tasks are currently awaiting collection.', 'pexpress'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="polar-tab-content" id="tab-completed">
            <?php if (!empty($completed_tasks)) : ?>
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
                        <?php foreach ($completed_tasks as $order) :
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