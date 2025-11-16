<?php

/**
 * Custom Order Edit Page Template
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get order data safely
$order_id = isset($order_id) ? $order_id : 0;
$order = isset($order) ? $order : null;
$order_items = isset($order_items) ? $order_items : array();
$modification_log = isset($modification_log) ? $modification_log : array();
$delivery_id = isset($delivery_id) ? $delivery_id : 0;
$fridge_id = isset($fridge_id) ? $fridge_id : 0;
$distributor_id = isset($distributor_id) ? $distributor_id : 0;

if (!$order || !is_a($order, 'WC_Order')) {
?>
    <div class="wrap">
        <div class="notice notice-error">
            <p><?php esc_html_e('Order not found or invalid order ID.', 'pexpress'); ?></p>
        </div>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-support')); ?>" class="button">
                <?php esc_html_e('Back to Support Dashboard', 'pexpress'); ?>
            </a>
        </p>
    </div>
<?php
    return;
}

// Get safe values with null checks
$billing_email = $order->get_billing_email() ? $order->get_billing_email() : '';
$billing_phone = $order->get_billing_phone() ? $order->get_billing_phone() : '';
$billing_address = $order->get_formatted_billing_address() ? $order->get_formatted_billing_address() : __('No address provided', 'pexpress');
$order_status = $order->get_status() ? $order->get_status() : 'pending';
$order_date = $order->get_date_created() ? $order->get_date_created()->date_i18n('F j, Y g:i A') : '';
$order_total = $order->get_formatted_order_total() ? $order->get_formatted_order_total() : wc_price(0);
$customer_name = PExpress_Core::get_billing_name($order);
$needs_assignment = isset($needs_assignment) ? (bool) $needs_assignment : false;
$forwarded_at = isset($forwarded_at) ? $forwarded_at : '';
$forwarded_by = isset($forwarded_by) ? (int) $forwarded_by : 0;
$forward_note = isset($forward_note) ? $forward_note : '';
$forwarded_by_user = $forwarded_by ? get_userdata($forwarded_by) : false;
$forwarded_by_name = $forwarded_by_user ? $forwarded_by_user->display_name : '';
$forwarded_at_display = '';
if (!empty($forwarded_at)) {
    $forwarded_at_display = mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $forwarded_at);
}
$is_forwarded = !empty($forwarded_at) || !empty($forwarded_by);
$forward_button_label = $is_forwarded ? __('Update Forwarding', 'pexpress') : __('Forward to SR', 'pexpress');
?>

<div class="wrap polar-dashboard polar-order-edit-dashboard">
    <div class="polar-dashboard-header">
        <div class="polar-header-content">
            <h1 class="polar-dashboard-title">
                <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-support')); ?>" class="polar-back-link" aria-label="<?php esc_attr_e('Back to Support Dashboard', 'pexpress'); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </a>
                <span class="polar-title-icon">✏️</span>
                <?php esc_html_e('Edit Order', 'pexpress'); ?>
                <span class="order-id-badge">
                    #<?php echo esc_html($order_id); ?>
                </span>
            </h1>
            <p class="polar-dashboard-subtitle"><?php esc_html_e('Manage order items and details', 'pexpress'); ?></p>
        </div>
    </div>

    <div class="polar-order-edit-content">
        <div class="polar-order-main">
            <!-- Order Information Card -->
            <div class="polar-order-item">
                <div class="order-header">
                    <h4><?php esc_html_e('Order Information', 'pexpress'); ?></h4>
                    <span class="order-status status-<?php echo esc_attr($order_status); ?>">
                        <?php echo esc_html(wc_get_order_status_name($order_status)); ?>
                    </span>
                </div>
                <div class="order-details">
                    <div class="order-detail-row">
                        <div class="order-detail-item">
                            <span class="detail-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div class="detail-content">
                                <span class="detail-label"><?php esc_html_e('Date', 'pexpress'); ?></span>
                                <span class="detail-value"><?php echo esc_html($order_date); ?></span>
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
                                <span class="detail-value order-total"><?php echo wp_kses_post($order_total); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information Card -->
            <div class="polar-order-item">
                <div class="order-header">
                    <h4><?php esc_html_e('Customer Information', 'pexpress'); ?></h4>
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
                                <span class="detail-value customer-name"><?php echo esc_html($customer_name); ?></span>
                            </div>
                        </div>
                        <?php if ($billing_email) : ?>
                            <div class="order-detail-item">
                                <span class="detail-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div class="detail-content">
                                    <span class="detail-label"><?php esc_html_e('Email', 'pexpress'); ?></span>
                                    <a href="mailto:<?php echo esc_attr($billing_email); ?>" class="detail-value detail-link">
                                        <?php echo esc_html($billing_email); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="order-detail-row">
                        <?php if ($billing_phone) : ?>
                            <div class="order-detail-item">
                                <span class="detail-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 5C3 3.89543 3.89543 3 5 3H8.27924C8.70967 3 9.09181 3.27543 9.22792 3.68377L10.7257 8.17721C10.8831 8.64932 10.6694 9.16531 10.2243 9.38787L7.96701 10.5165C9.06925 12.9612 11.0388 14.9308 13.4835 16.033L14.6121 13.7757C14.8347 13.3306 15.3507 13.1169 15.8228 13.2743L20.3162 14.7721C20.7246 14.9082 21 15.2903 21 15.7208V19C21 20.1046 20.1046 21 19 21H18C9.71573 21 3 14.2843 3 6V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div class="detail-content">
                                    <span class="detail-label"><?php esc_html_e('Phone', 'pexpress'); ?></span>
                                    <a href="tel:<?php echo esc_attr($billing_phone); ?>" class="detail-value detail-link phone-number">
                                        <?php echo esc_html($billing_phone); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="order-detail-item order-detail-full">
                            <span class="detail-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 7H4C2.89543 7 2 7.89543 2 9V19C2 20.1046 2.89543 21 4 21H20C21.1046 21 22 20.1046 22 19V9C22 7.89543 21.1046 7 20 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M16 21V13C16 11.8954 15.1046 11 14 11H10C8.89543 11 8 11.8954 8 13V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div class="detail-content">
                                <span class="detail-label"><?php esc_html_e('Address', 'pexpress'); ?></span>
                                <span class="detail-value"><?php echo wp_kses_post(nl2br($billing_address)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items Card -->
            <div class="polar-order-item">
                <div class="order-header">
                    <h4><?php esc_html_e('Order Items', 'pexpress'); ?></h4>
                </div>

                <div class="polar-order-items-table-wrapper">
                    <table class="wp-list-table widefat striped polar-order-items-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Product', 'pexpress'); ?></th>
                                <th><?php esc_html_e('Quantity', 'pexpress'); ?></th>
                                <th><?php esc_html_e('Price', 'pexpress'); ?></th>
                                <th><?php esc_html_e('Total', 'pexpress'); ?></th>
                                <th class="column-actions"><?php esc_html_e('Actions', 'pexpress'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="polar-order-items-tbody">
                            <?php if (!empty($order_items)) : ?>
                                <?php foreach ($order_items as $item_id => $item) :
                                    $product = $item->get_product();
                                    $item_total = (float) $item->get_total();
                                    $item_quantity = (int) $item->get_quantity();
                                    $unit_price = $item_quantity > 0 ? ($item_total / $item_quantity) : 0;
                                ?>
                                    <tr
                                        class="polar-order-item-row"
                                        data-item-id="<?php echo esc_attr($item_id); ?>"
                                        data-quantity="<?php echo esc_attr($item_quantity); ?>"
                                        data-unit-price="<?php echo esc_attr(wc_format_decimal($unit_price)); ?>"
                                        data-line-total="<?php echo esc_attr(wc_format_decimal($item_total)); ?>">
                                        <td class="column-product">
                                            <strong><?php echo esc_html($item->get_name()); ?></strong>
                                            <?php if ($product && $product->get_sku()) : ?>
                                                <small class="item-meta"><?php esc_html_e('SKU:', 'pexpress'); ?> <?php echo esc_html($product->get_sku()); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="column-quantity">
                                            <span class="item-quantity"><?php echo esc_html($item_quantity); ?></span>
                                        </td>
                                        <td class="column-price">
                                            <span class="item-price"><?php echo wc_price($unit_price); ?></span>
                                        </td>
                                        <td class="column-total">
                                            <span class="item-total"><?php echo wc_price($item_total); ?></span>
                                        </td>
                                        <td class="column-actions">
                                            <div class="polar-item-actions">
                                                <button type="button" class="button button-small polar-edit-item" data-item-id="<?php echo esc_attr($item_id); ?>" title="<?php esc_attr_e('Edit', 'pexpress'); ?>">
                                                    <span class="dashicons dashicons-edit"></span>
                                                </button>
                                                <button type="button" class="button button-small polar-remove-item" data-item-id="<?php echo esc_attr($item_id); ?>" title="<?php esc_attr_e('Remove', 'pexpress'); ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                                <button type="button" class="button button-small polar-replace-item" data-item-id="<?php echo esc_attr($item_id); ?>" title="<?php esc_attr_e('Replace', 'pexpress'); ?>">
                                                    <span class="dashicons dashicons-update"></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="polar-empty-state"><?php esc_html_e('No items in this order.', 'pexpress'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"><strong><?php esc_html_e('Order Total', 'pexpress'); ?></strong></td>
                                <td colspan="2">
                                    <strong id="polar-order-total"><?php echo wp_kses_post($order_total); ?></strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Add Item Section -->
                <div class="polar-add-item-section">
                    <h3><?php esc_html_e('Add Item to Order', 'pexpress'); ?></h3>
                    <p class="polar-add-item-help"><?php esc_html_e('Use the product picker to add new items just like the WooCommerce order editor.', 'pexpress'); ?></p>
                    <div class="polar-add-item-actions">
                        <button type="button" class="button button-primary polar-open-add-item">
                            <?php esc_html_e('Add product(s)', 'pexpress'); ?>
                        </button>
                    </div>
                </div>

                <script type="text/template" id="tmpl-wc-modal-add-products">
                    <div class="wc-backbone-modal polar-add-product-modal">
                        <div class="wc-backbone-modal-content polar-modal-wrapper">
                            <section class="wc-backbone-modal-main" role="main">
                                <header class="wc-backbone-modal-header polar-modal-header">
                                    <div class="polar-modal-header-content">
                                        <div class="polar-modal-icon">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 4V20M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                            </svg>
                                        </div>
                                        <div class="polar-modal-title-wrapper">
                                            <h1><?php esc_html_e('Add Products to Order', 'pexpress'); ?></h1>
                                            <p class="polar-modal-subtitle"><?php esc_html_e('Search and select products to add to this order', 'pexpress'); ?></p>
                                        </div>
                                    </div>
                                    <button class="modal-close modal-close-link" aria-label="<?php esc_attr_e('Close modal panel', 'woocommerce'); ?>">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </header>
                                <article class="polar-modal-body">
                                    <form action="" method="post" class="polar-modal-add-product-form">
                                        <table class="widefat polar-modal-products-table">
                                            <thead>
                                                <tr>
                                                    <th class="polar-modal-th-product">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 6px;">
                                                            <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        <?php esc_html_e('Product', 'woocommerce'); ?>
                                                    </th>
                                                    <th class="polar-modal-th-quantity">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 6px;">
                                                            <path d="M3 6H21M6 6V4C6 3.46957 6.21071 2.96086 6.58579 2.58579C6.96086 2.21071 7.46957 2 8 2H16C16.5304 2 17.0391 2.21071 17.4142 2.58579C17.7893 2.96086 18 3.46957 18 4V6M6 6L5 20C5 20.5304 5.21071 21.0391 5.58579 21.4142C5.96086 21.7893 6.46957 22 7 22H17C17.5304 22 18.0391 21.7893 18.4142 21.4142C18.7893 21.0391 19 20.5304 19 20L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        <?php esc_html_e('Quantity', 'woocommerce'); ?>
                                                    </th>
                                                    <th class="polar-modal-th-actions" style="width: 60px;"></th>
                                                </tr>
                                            </thead>
                                            <tbody data-row='<tr data-row-index="{index}"><td class="polar-modal-td-product"><select id="polar-product-search-{index}" class="wc-product-search" name="item_id[{index}]" data-allow_clear="true" data-display_stock="true" data-exclude_type="variable" data-placeholder="<?php echo esc_js(__('Search for a product&hellip;', 'woocommerce')); ?>"></select></td><td class="polar-modal-td-quantity"><input type="number" id="polar-quantity-{index}" step="1" min="1" max="9999" autocomplete="off" name="item_qty[{index}]" value="1" placeholder="1" class="quantity polar-modal-quantity-field" /></td><td class="polar-modal-td-actions"><button type="button" class="polar-remove-row-btn" title="<?php echo esc_js(__('Remove row', 'pexpress')); ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button></td></tr>'>
                                                <tr data-row-index="0">
                                                    <td class="polar-modal-td-product">
                                                        <select id="polar-product-search-0" class="wc-product-search" name="item_id" data-allow_clear="true" data-display_stock="true" data-exclude_type="variable" data-placeholder="<?php esc_attr_e('Search for a product&hellip;', 'woocommerce'); ?>"></select>
                                                    </td>
                                                    <td class="polar-modal-td-quantity">
                                                        <input type="number" id="polar-quantity-0" step="1" min="1" max="9999" autocomplete="off" name="item_qty" value="1" placeholder="1" class="quantity polar-modal-quantity-field" />
                                                    </td>
                                                    <td class="polar-modal-td-actions">
                                                        <button type="button" class="polar-remove-row-btn" style="display: none;" title="<?php esc_attr_e('Remove row', 'pexpress'); ?>">
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <button type="button" class="polar-add-row-btn">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 4V20M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <?php esc_html_e('Add Another Product', 'pexpress'); ?>
                                        </button>
                                    </form>
                                </article>
                                <footer class="polar-modal-footer">
                                    <div class="inner">
                                        <button type="button" class="button cancel-action polar-btn-secondary">
                                            <?php esc_html_e('Cancel', 'woocommerce'); ?>
                                        </button>
                                        <button type="button" class="button button-primary button-large polar-modal-submit polar-btn-primary">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 8px;">
                                                <path d="M5 13L9 17L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <?php esc_html_e('Add to Order', 'woocommerce'); ?>
                                        </button>
                                    </div>
                                </footer>
                            </section>
                        </div>
                    </div>
                    <div class="wc-backbone-modal-backdrop modal-close"></div>
                </script>
            </div>

            <!-- Forward to HR -->
            <div class="polar-order-item polar-forward-card">
                <div class="order-header">
                    <h4><?php esc_html_e('Forward to SR', 'pexpress'); ?></h4>
                    <span class="forward-status-badge <?php echo $is_forwarded ? 'is-forwarded' : 'is-idle'; ?>">
                        <?php echo $is_forwarded ? esc_html__('Forwarded to SR', 'pexpress') : esc_html__('Not Yet Forwarded', 'pexpress'); ?>
                    </span>
                </div>
                <div class="forward-body">
                    <?php if ($forwarded_at_display || $forwarded_by_name) : ?>
                        <p class="forward-meta">
                            <?php
                            if ($forwarded_at_display && $forwarded_by_name) {
                                printf(
                                    /* translators: 1: forward date, 2: user name */
                                    esc_html__('Last forwarded on %1$s by %2$s.', 'pexpress'),
                                    esc_html($forwarded_at_display),
                                    esc_html($forwarded_by_name)
                                );
                            } elseif ($forwarded_at_display) {
                                printf(
                                    /* translators: %s: forward date */
                                    esc_html__('Last forwarded on %s.', 'pexpress'),
                                    esc_html($forwarded_at_display)
                                );
                            } elseif ($forwarded_by_name) {
                                printf(
                                    /* translators: %s: user name */
                                    esc_html__('Forwarded by %s.', 'pexpress'),
                                    esc_html($forwarded_by_name)
                                );
                            }
                            ?>
                        </p>
                    <?php endif; ?>
                    <label for="polar-forward-note" class="forward-label">
                        <?php esc_html_e('Support Notes for SR', 'pexpress'); ?>
                    </label>
                    <textarea id="polar-forward-note" class="polar-textarea forward-note" rows="3" placeholder="<?php esc_attr_e('Provide any context SR should know before assignment...', 'pexpress'); ?>"><?php echo esc_textarea($forward_note); ?></textarea>
                    <div class="forward-actions">
                        <button type="button" class="polar-btn polar-btn-primary polar-forward-to-hr" data-order-id="<?php echo esc_attr($order_id); ?>" <?php echo $is_forwarded && !$needs_assignment ? 'disabled' : ''; ?>>
                            <?php echo esc_html(str_replace('HR', 'SR', $forward_button_label)); ?>
                        </button>
                        <?php if ($is_forwarded) : ?>
                            <button type="button" class="polar-btn polar-btn-secondary polar-revoke-forward" data-order-id="<?php echo esc_attr($order_id); ?>" style="margin-left: 10px;">
                                <?php esc_html_e('Revoke from SR', 'pexpress'); ?>
                            </button>
                        <?php endif; ?>
                        <span class="polar-forward-feedback" role="status" aria-live="polite"></span>
                    </div>
                </div>
            </div>

            <!-- Order Actions -->
            <?php
            $current_user = wp_get_current_user();
            $is_support = in_array('polar_support', $current_user->roles) || current_user_can('manage_woocommerce');
            $order_confirmed = PExpress_Core::get_order_meta($order_id, '_polar_order_confirmed');
            $order_completed = PExpress_Core::get_order_meta($order_id, '_polar_order_completed');
            ?>
            <?php if ($is_support) : ?>
                <div class="polar-order-item polar-order-actions-card">
                    <div class="order-header">
                        <h4><?php esc_html_e('Order Actions', 'pexpress'); ?></h4>
                    </div>
                    <div class="order-actions-body">
                        <?php if (!$order_confirmed) : ?>
                            <button type="button" class="polar-btn polar-btn-success polar-confirm-order" data-order-id="<?php echo esc_attr($order_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('polar_confirm_order')); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <?php esc_html_e('Confirm Order', 'pexpress'); ?>
                            </button>
                        <?php else : ?>
                            <p class="polar-action-status">
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                <?php esc_html_e('Order Confirmed', 'pexpress'); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!$order_completed && $order->get_status() !== 'completed') : ?>
                            <button type="button" class="polar-btn polar-btn-primary polar-complete-order" data-order-id="<?php echo esc_attr($order_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('polar_complete_order')); ?>" style="margin-top: 10px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <?php esc_html_e('Order Completed', 'pexpress'); ?>
                            </button>
                        <?php elseif ($order_completed || $order->get_status() === 'completed') : ?>
                            <p class="polar-action-status" style="margin-top: 10px;">
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                <?php esc_html_e('Order Completed', 'pexpress'); ?>
                            </p>
                        <?php endif; ?>
                        <span class="polar-action-feedback" role="status" aria-live="polite"></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Assignments -->
            <?php if ($delivery_id || $fridge_id || $distributor_id) : ?>
                <div class="polar-order-item">
                    <div class="order-header">
                        <h4><?php esc_html_e('Assignments', 'pexpress'); ?></h4>
                    </div>
                    <div class="assignment-info">
                        <div class="assignment-badges">
                            <?php if ($delivery_id) :
                                $delivery_user = get_userdata($delivery_id);
                            ?>
                                <span class="assignment-badge badge-delivery">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 13L12 20L19 13M12 4V20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <?php esc_html_e('Delivery:', 'pexpress'); ?> <?php echo esc_html($delivery_user ? $delivery_user->display_name : 'N/A'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($fridge_id) :
                                $fridge_user = get_userdata($fridge_id);
                            ?>
                                <span class="assignment-badge badge-fridge">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 13L12 20L19 13M12 4V20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <?php esc_html_e('Fridge:', 'pexpress'); ?> <?php echo esc_html($fridge_user ? $fridge_user->display_name : 'N/A'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($distributor_id) :
                                $distributor_user = get_userdata($distributor_id);
                            ?>
                                <span class="assignment-badge badge-distributor">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 13L12 20L19 13M12 4V20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <?php esc_html_e('Product Provider:', 'pexpress'); ?> <?php echo esc_html($distributor_user ? $distributor_user->display_name : 'N/A'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="polar-order-sidebar">
            <!-- Modification History -->
            <div class="polar-order-item">
                <div class="order-header">
                    <h4>
                        <?php esc_html_e('Modification History', 'pexpress'); ?>
                        <span class="polar-toggle-history dashicons dashicons-arrow-down-alt2"></span>
                    </h4>
                </div>
                <div class="polar-history-content is-hidden">
                    <?php if (empty($modification_log)) : ?>
                        <p class="polar-empty-state"><?php esc_html_e('No modifications recorded.', 'pexpress'); ?></p>
                    <?php else : ?>
                        <div class="polar-history-list">
                            <?php foreach (array_reverse($modification_log) as $log_entry) :
                                $log_action = isset($log_entry['action']) ? $log_entry['action'] : '';
                                $log_timestamp = isset($log_entry['timestamp']) ? $log_entry['timestamp'] : '';
                                $log_user_name = isset($log_entry['user_name']) ? $log_entry['user_name'] : '';
                                $log_old_value = isset($log_entry['old_value']) ? $log_entry['old_value'] : null;
                                $log_new_value = isset($log_entry['new_value']) ? $log_entry['new_value'] : null;
                            ?>
                                <div class="polar-log-entry polar-log-<?php echo esc_attr($log_action); ?>">
                                    <div class="polar-log-header">
                                        <span class="polar-log-time"><?php echo esc_html($log_timestamp); ?></span>
                                        <span class="polar-action-badge polar-action-<?php echo esc_attr($log_action); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $log_action ? $log_action : ''))); ?>
                                        </span>
                                    </div>
                                    <div class="polar-log-user">
                                        <?php esc_html_e('By:', 'pexpress'); ?> <?php echo esc_html($log_user_name); ?>
                                    </div>
                                    <?php if (!empty($log_old_value) || !empty($log_new_value)) : ?>
                                        <div class="polar-log-details">
                                            <?php
                                            $format_value = function ($value) {
                                                if (!is_array($value)) {
                                                    return (string) $value;
                                                }
                                                $parts = array();
                                                if (isset($value['product_id'])) {
                                                    $product = wc_get_product($value['product_id']);
                                                    $parts[] = 'Product: ' . ($product ? $product->get_name() : 'ID ' . $value['product_id']);
                                                }
                                                if (isset($value['quantity'])) {
                                                    $parts[] = 'Qty: ' . $value['quantity'];
                                                }
                                                if (isset($value['price'])) {
                                                    $parts[] = 'Price: ' . wc_price($value['price']);
                                                }
                                                return implode(', ', $parts);
                                            };
                                            ?>
                                            <?php if (!empty($log_old_value)) : ?>
                                                <div><strong><?php esc_html_e('Before:', 'pexpress'); ?></strong> <?php echo esc_html($format_value($log_old_value)); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($log_new_value)) : ?>
                                                <div><strong><?php esc_html_e('After:', 'pexpress'); ?></strong> <?php echo esc_html($format_value($log_new_value)); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>