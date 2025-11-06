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

<div class="wrap polar-dashboard polar-hr-dashboard">
    <h1><?php esc_html_e('HR Dashboard - Task Assignment', 'pexpress'); ?></h1>

    <div class="polar-stats">
        <div class="polar-stat-card">
            <h3><?php echo count($pending_orders); ?></h3>
            <p><?php esc_html_e('Orders Pending Assignment', 'pexpress'); ?></p>
        </div>
        <div class="polar-stat-card">
            <h3><?php echo count($delivery_users); ?></h3>
            <p><?php esc_html_e('Delivery Personnel', 'pexpress'); ?></p>
        </div>
        <div class="polar-stat-card">
            <h3><?php echo count($fridge_users); ?></h3>
            <p><?php esc_html_e('Fridge Providers', 'pexpress'); ?></p>
        </div>
        <div class="polar-stat-card">
            <h3><?php echo count($distributor_users); ?></h3>
            <p><?php esc_html_e('Distributors', 'pexpress'); ?></p>
        </div>
    </div>

    <div class="polar-orders-section">
        <h2><?php esc_html_e('Orders Needing Assignment', 'pexpress'); ?></h2>

        <?php if (!empty($pending_orders)) : ?>
            <div class="polar-orders-list" id="polar-orders-list">
                <?php foreach ($pending_orders as $order) :
                    $order_id = $order->get_id();
                ?>
                    <div class="polar-order-item" data-order-id="<?php echo esc_attr($order_id); ?>">
                        <div class="order-header">
                            <h4>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" target="_blank">
                                    <?php printf(esc_html__('Order #%d', 'pexpress'), $order_id); ?>
                                </a>
                            </h4>
                            <span class="order-status status-<?php echo esc_attr($order->get_status()); ?>">
                                <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                            </span>
                        </div>
                        <div class="order-details">
                            <p><strong><?php esc_html_e('Customer:', 'pexpress'); ?></strong> <?php echo esc_html(PExpress_Core::get_billing_name($order)); ?></p>
                            <p><strong><?php esc_html_e('Phone:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_phone()); ?></p>
                            <p><strong><?php esc_html_e('Email:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_billing_email()); ?></p>
                            <p><strong><?php esc_html_e('Date:', 'pexpress'); ?></strong> <?php echo esc_html($order->get_date_created()->date_i18n()); ?></p>
                            <p><strong><?php esc_html_e('Total:', 'pexpress'); ?></strong> <?php echo wp_kses_post($order->get_formatted_order_total()); ?></p>
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