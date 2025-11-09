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
?>

<?php
// Calculate stats
$pending_count = count($pending_orders);
$delivery_count = count($delivery_users);
$fridge_count = count($fridge_users);
$distributor_count = count($distributor_users);
?>

<div class="wrap polar-dashboard polar-hr-dashboard">
    <div class="polar-dashboard-header">
        <div class="polar-header-content">
            <h1 class="polar-dashboard-title">
                <span class="polar-title-icon">👥</span>
                <?php esc_html_e('HR Dashboard - Task Assignment', 'pexpress'); ?>
            </h1>
            <p class="polar-dashboard-subtitle"><?php esc_html_e('Assign orders to delivery, fridge, and distributor teams', 'pexpress'); ?></p>
        </div>
    </div>

    <div class="polar-stats-grid">
        <div class="polar-stat-card stat-card-primary">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($pending_count); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Orders Pending Assignment', 'pexpress'); ?></p>
            </div>
        </div>
        <div class="polar-stat-card stat-card-warning">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88M13 7C13 9.20914 10.2091 11 8 11C5.79086 11 3 9.20914 3 7C3 4.79086 5.79086 3 8 3C10.2091 3 13 4.79086 13 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($delivery_count); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Delivery Personnel', 'pexpress'); ?></p>
            </div>
        </div>
        <div class="polar-stat-card stat-card-success">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88M13 7C13 9.20914 10.2091 11 8 11C5.79086 11 3 9.20914 3 7C3 4.79086 5.79086 3 8 3C10.2091 3 13 4.79086 13 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($fridge_count); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Fridge Providers', 'pexpress'); ?></p>
            </div>
        </div>
        <div class="polar-stat-card stat-card-info">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88M13 7C13 9.20914 10.2091 11 8 11C5.79086 11 3 9.20914 3 7C3 4.79086 5.79086 3 8 3C10.2091 3 13 4.79086 13 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="stat-card-content">
                <h3 class="stat-card-value"><?php echo esc_html($distributor_count); ?></h3>
                <p class="stat-card-label"><?php esc_html_e('Distributors', 'pexpress'); ?></p>
            </div>
        </div>
    </div>

    <div class="polar-orders-section">
        <div class="polar-section-header">
            <h2 class="polar-section-title"><?php esc_html_e('Orders Needing Assignment', 'pexpress'); ?></h2>
        </div>

        <?php if (!empty($pending_orders)) : ?>
            <div class="polar-orders-list" id="polar-orders-list">
                <?php foreach ($pending_orders as $order) :
                    $order_id = $order->get_id();
                    $forwarded_by_id = (int) PExpress_Core::get_order_meta($order_id, '_polar_forwarded_by');
                    $forwarded_at_raw = PExpress_Core::get_order_meta($order_id, '_polar_forwarded_at');
                    $forwarded_note = PExpress_Core::get_order_meta($order_id, '_polar_forward_note');
                    $forwarded_by_user = $forwarded_by_id ? get_userdata($forwarded_by_id) : false;
                    $forwarded_by_name = $forwarded_by_user ? $forwarded_by_user->display_name : '';
                    $forwarded_at = $forwarded_at_raw ? mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $forwarded_at_raw) : '';
                ?>
                    <div class="polar-order-item" data-order-id="<?php echo esc_attr($order_id); ?>">
                        <div class="order-header">
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
                            <span class="order-status status-<?php echo esc_attr($order->get_status()); ?>">
                                <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
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
                                <div class="order-detail-item">
                                    <span class="detail-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <div class="detail-content">
                                        <span class="detail-label"><?php esc_html_e('Email', 'pexpress'); ?></span>
                                        <a href="mailto:<?php echo esc_attr($order->get_billing_email()); ?>" class="detail-value detail-link">
                                            <?php echo esc_html($order->get_billing_email()); ?>
                                        </a>
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
                                        <span class="detail-value order-total"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="polar-forward-summary">
                                <span class="forward-summary-badge">
                                    <?php esc_html_e('Forwarded from Support', 'pexpress'); ?>
                                </span>
                                <div class="forward-summary-details">
                                    <?php if ($forwarded_by_name || $forwarded_at) : ?>
                                        <p>
                                            <?php
                                            if ($forwarded_by_name && $forwarded_at) {
                                                printf(
                                                    /* translators: 1: name 2: date */
                                                    esc_html__('By %1$s on %2$s', 'pexpress'),
                                                    esc_html($forwarded_by_name),
                                                    esc_html($forwarded_at)
                                                );
                                            } elseif ($forwarded_by_name) {
                                                printf(
                                                    /* translators: %s: name */
                                                    esc_html__('By %s', 'pexpress'),
                                                    esc_html($forwarded_by_name)
                                                );
                                            } elseif ($forwarded_at) {
                                                printf(
                                                    /* translators: %s: date */
                                                    esc_html__('On %s', 'pexpress'),
                                                    esc_html($forwarded_at)
                                                );
                                            }
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($forwarded_note)) : ?>
                                        <p class="forward-note">
                                            <strong><?php esc_html_e('Support Note:', 'pexpress'); ?></strong>
                                            <?php echo esc_html($forwarded_note); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="assignment-form">
                            <form class="polar-assign-form" data-order-id="<?php echo esc_attr($order_id); ?>">
                                <?php wp_nonce_field('polar_assign_' . $order_id, 'polar_assign_nonce'); ?>

                                <div class="assign-row">
                                    <div class="assign-field">
                                        <label><?php esc_html_e('Delivery Person:', 'pexpress'); ?></label>
                                        <select name="delivery_user_id" class="polar-select">
                                            <option value=""><?php esc_html_e('Select...', 'pexpress'); ?></option>
                                            <?php foreach ($delivery_users as $user) : ?>
                                                <option value="<?php echo esc_attr($user->ID); ?>">
                                                    <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="assign-field">
                                        <label><?php esc_html_e('Fridge Provider:', 'pexpress'); ?></label>
                                        <select name="fridge_user_id" class="polar-select">
                                            <option value=""><?php esc_html_e('Select...', 'pexpress'); ?></option>
                                            <?php foreach ($fridge_users as $user) : ?>
                                                <option value="<?php echo esc_attr($user->ID); ?>">
                                                    <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="assign-field">
                                        <label><?php esc_html_e('Distributor:', 'pexpress'); ?></label>
                                        <select name="distributor_user_id" class="polar-select">
                                            <option value=""><?php esc_html_e('Select...', 'pexpress'); ?></option>
                                            <?php foreach ($distributor_users as $user) : ?>
                                                <option value="<?php echo esc_attr($user->ID); ?>">
                                                    <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="assign-row">
                                    <div class="assign-field">
                                        <label><?php esc_html_e('Fridge Return Date:', 'pexpress'); ?></label>
                                        <input type="date" name="fridge_return_date" class="polar-input">
                                    </div>

                                    <div class="assign-field assign-field-full">
                                        <label><?php esc_html_e('Assignment Notes:', 'pexpress'); ?></label>
                                        <textarea name="assignment_note" class="polar-textarea" rows="2" placeholder="<?php esc_attr_e('Add any special instructions...', 'pexpress'); ?>"></textarea>
                                    </div>
                                </div>

                                <button type="submit" class="polar-btn polar-btn-primary">
                                    <?php esc_html_e('Assign Order', 'pexpress'); ?>
                                </button>
                                <span class="polar-assign-loading" style="display:none;"><?php esc_html_e('Assigning...', 'pexpress'); ?></span>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="polar-empty-state">
                <div class="empty-state-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h3><?php esc_html_e('No orders need assignment', 'pexpress'); ?></h3>
                <p><?php esc_html_e('No orders need assignment at this time.', 'pexpress'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="polar-recent-assignments">
        <h2><?php esc_html_e('Recently Assigned Orders', 'pexpress'); ?></h2>
        <?php
        $recent_assigned = wc_get_orders(array(
            'status' => 'wc-polar-assigned',
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        ?>
        <?php if (!empty($recent_assigned)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Order ID', 'pexpress'); ?></th>
                        <th><?php esc_html_e('Customer', 'pexpress'); ?></th>
                        <th><?php esc_html_e('Delivery', 'pexpress'); ?></th>
                        <th><?php esc_html_e('Fridge', 'pexpress'); ?></th>
                        <th><?php esc_html_e('Distributor', 'pexpress'); ?></th>
                        <th><?php esc_html_e('Date', 'pexpress'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_assigned as $order) :
                        $delivery_id = PExpress_Core::get_delivery_user_id($order->get_id());
                        $fridge_id = PExpress_Core::get_fridge_user_id($order->get_id());
                        $distributor_id = PExpress_Core::get_distributor_user_id($order->get_id());
                    ?>
                        <tr>
                            <td><a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>">#<?php echo esc_html($order->get_id()); ?></a></td>
                            <td><?php echo esc_html(PExpress_Core::get_billing_name($order)); ?></td>
                            <td><?php echo $delivery_id ? esc_html(get_userdata($delivery_id)->display_name) : '—'; ?></td>
                            <td><?php echo $fridge_id ? esc_html(get_userdata($fridge_id)->display_name) : '—'; ?></td>
                            <td><?php echo $distributor_id ? esc_html(get_userdata($distributor_id)->display_name) : '—'; ?></td>
                            <td><?php echo esc_html($order->get_date_created()->date_i18n('Y-m-d H:i')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('No recently assigned orders.', 'pexpress'); ?></p>
        <?php endif; ?>
    </div>
</div>