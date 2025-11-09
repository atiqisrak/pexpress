<?php

/**
 * Order Manipulation Handler
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Order Manipulation class
 */
class PExpress_Admin_Order_Manipulation
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize hooks
     */
    private function init()
    {
        // AJAX handlers
        add_action('wp_ajax_polar_add_order_item', array($this, 'ajax_add_order_item'));
        add_action('wp_ajax_polar_remove_order_item', array($this, 'ajax_remove_order_item'));
        add_action('wp_ajax_polar_update_order_item', array($this, 'ajax_update_order_item'));
        add_action('wp_ajax_polar_replace_order_item', array($this, 'ajax_replace_order_item'));
        add_action('wp_ajax_polar_get_product_alternatives', array($this, 'ajax_get_product_alternatives'));
        add_action('wp_ajax_polar_recalculate_order_totals', array($this, 'ajax_recalculate_order_totals'));
        add_action('wp_ajax_polar_search_products', array($this, 'ajax_search_products'));

        // Allow WooCommerce product search for our users
        add_filter('woocommerce_json_search_found_products', array($this, 'allow_product_search'), 10, 1);

        // Enqueue assets on custom order edit page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * AJAX handler: Search products for select dropdown
     */
    public function ajax_search_products()
    {
        if (!current_user_can('polar_support') && !current_user_can('edit_shop_orders')) {
            wp_send_json(array());
        }

        $nonce = isset($_REQUEST['security']) ? sanitize_text_field(wp_unslash($_REQUEST['security'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'search-products')) {
            wp_send_json(array());
        }

        $term = isset($_REQUEST['term']) ? wc_clean(wp_unslash($_REQUEST['term'])) : '';
        if ('' === $term) {
            wp_send_json(array());
        }

        $limit = isset($_REQUEST['limit']) ? max(absint($_REQUEST['limit']), 1) : absint(apply_filters('woocommerce_json_search_limit', 30));
        $include_ids = !empty($_REQUEST['include']) ? array_map('absint', (array) wp_unslash($_REQUEST['include'])) : array();
        $exclude_ids = !empty($_REQUEST['exclude']) ? array_map('absint', (array) wp_unslash($_REQUEST['exclude'])) : array();

        $exclude_types = array();
        if (!empty($_REQUEST['exclude_type'])) {
            $exclude_types = wp_unslash($_REQUEST['exclude_type']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            if (!is_array($exclude_types)) {
                $exclude_types = explode(',', $exclude_types);
            }

            foreach ($exclude_types as &$exclude_type) {
                $exclude_type = strtolower(trim($exclude_type));
            }
            $exclude_types = array_intersect(
                array_merge(array(\Automattic\WooCommerce\Enums\ProductType::VARIATION), array_keys(wc_get_product_types())),
                $exclude_types
            );
        }

        $data_store = WC_Data_Store::load('product');
        $ids = $data_store->search_products($term, '', true, false, $limit, $include_ids, $exclude_ids);

        $products = array();

        foreach ($ids as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product || in_array($product->get_type(), $exclude_types, true) || !wc_products_array_filter_readable($product)) {
                continue;
            }

            $label = rawurldecode(wp_strip_all_tags($product->get_formatted_name()));

            if ($product->managing_stock() && !empty($_REQUEST['display_stock'])) {
                $stock_amount = $product->get_stock_quantity();
                /* translators: %d stock amount */
                $label .= ' - ' . sprintf(__('Stock: %d', 'woocommerce'), wc_format_stock_quantity_for_display($stock_amount, $product));
            }

            $products[$product_id] = $label;
        }

        $products = apply_filters('woocommerce_json_search_found_products', $products);

        $results = array();
        foreach ($products as $product_id => $label) {
            $results[] = array(
                'id' => $product_id,
                'text' => $label,
            );
        }

        wp_send_json_success($results);
    }

    /**
     * Allow product search for support users
     */
    public function allow_product_search($products)
    {
        if (current_user_can('polar_support') || current_user_can('edit_shop_orders')) {
            return $products;
        }
        return array();
    }

    /**
     * Enqueue assets on order edit screen
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets($hook)
    {
        // Only load on custom order edit page - check both hook and screen
        $is_order_edit_page = false;

        // Check hook first
        if (isset($_GET['page']) && $_GET['page'] === 'polar-express-order-edit') {
            $is_order_edit_page = true;
        }

        // Also check screen ID if available
        $screen = get_current_screen();
        if ($screen && isset($screen->id)) {
            $screen_id = (string) $screen->id;
            if (strpos($screen_id, 'polar-express-order-edit') !== false || strpos($screen_id, 'polar-express') !== false) {
                $is_order_edit_page = true;
            }
        }

        if (!$is_order_edit_page) {
            return;
        }

        // Check if user has permission
        if (!current_user_can('polar_support') && !current_user_can('edit_shop_orders')) {
            return;
        }

        // Enqueue main dashboard styles for consistency
        wp_enqueue_style(
            'pexpress-admin',
            PEXPRESS_PLUGIN_URL . 'assets/css/polar.css',
            array(),
            PEXPRESS_VERSION
        );

        wp_enqueue_style(
            'pexpress-order-edit',
            PEXPRESS_PLUGIN_URL . 'assets/css/polar-order-edit.css',
            array('pexpress-admin'),
            PEXPRESS_VERSION
        );

        // Enqueue Select2 (WooCommerce includes it, but ensure it's loaded)
        if (!wp_script_is('select2', 'enqueued') && !wp_script_is('select2', 'registered')) {
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
        } else {
            wp_enqueue_script('select2');
            wp_enqueue_style('select2');
        }

        wp_enqueue_script(
            'pexpress-order-edit',
            PEXPRESS_PLUGIN_URL . 'assets/js/polar-order-edit.js',
            array('jquery', 'select2', 'wp-util'),
            PEXPRESS_VERSION,
            true
        );

        // Get WooCommerce product search nonce
        // WooCommerce uses 'search-products' nonce for its product search AJAX
        $wc_search_nonce = wp_create_nonce('search-products');

        // Get order ID from URL
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        if (!$order_id) {
            return;
        }

        wp_localize_script(
            'pexpress-order-edit',
            'polarOrderEdit',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('polar_order_edit_nonce'),
                'orderId' => $order_id,
                'wcSearchNonce' => $wc_search_nonce,
                'currency' => array(
                    'symbol' => get_woocommerce_currency_symbol(),
                    'price_format' => get_woocommerce_price_format(),
                    'decimals' => wc_get_price_decimals(),
                    'decimal_separator' => wc_get_price_decimal_separator(),
                    'thousand_separator' => wc_get_price_thousand_separator(),
                    'locale' => get_locale(),
                ),
                'i18n' => array(
                    'searchProducts' => __('Search for a product...', 'pexpress'),
                    'selectProduct' => __('Please select a product.', 'pexpress'),
                    'confirmRemove' => __('Are you sure you want to remove this item?', 'pexpress'),
                    'invalidQuantity' => __('Please enter a valid quantity.', 'pexpress'),
                    'invalidPrice' => __('Please enter a valid price.', 'pexpress'),
                    'saveItem' => __('Save', 'pexpress'),
                    'cancelEdit' => __('Cancel', 'pexpress'),
                    'quantityLabel' => __('Quantity', 'pexpress'),
                    'priceLabel' => __('Price', 'pexpress'),
                    'addProducts' => __('Add products', 'pexpress'),
                    'addToOrder' => __('Add to order', 'pexpress'),
                    'cancel' => __('Cancel', 'pexpress'),
                    'closeModal' => __('Close modal', 'pexpress'),
                    'modalProductLabel' => __('Product', 'pexpress'),
                    'modalQuantityLabel' => __('Quantity', 'pexpress'),
                    'addProductError' => __('Error adding item.', 'pexpress'),
                    'genericError' => __('An error occurred. Please try again.', 'pexpress'),
                ),
            )
        );

        // Enqueue select2 if not already loaded
        wp_enqueue_script('select2');
        wp_enqueue_style('select2');
    }

    /**
     * Render custom order edit page
     */
    public function render_order_edit_page()
    {
        // Check permissions
        if (!current_user_can('polar_support') && !current_user_can('edit_shop_orders')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
        }

        // Get order ID from URL
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        if (!$order_id) {
            wp_die(__('Invalid order ID.', 'pexpress'));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_die(__('Order not found.', 'pexpress'));
        }

        // Get order data with null safety
        $order_items = $order->get_items() ? $order->get_items() : array();
        $modification_log = $this->get_modification_log($order_id);
        $delivery_id = PExpress_Core::get_delivery_user_id($order_id);
        $fridge_id = PExpress_Core::get_fridge_user_id($order_id);
        $distributor_id = PExpress_Core::get_distributor_user_id($order_id);

        // Make variables available to template
        $order_id = $order_id;
        $order = $order;
        $order_items = $order_items;
        $modification_log = $modification_log;
        $delivery_id = $delivery_id ? $delivery_id : 0;
        $fridge_id = $fridge_id ? $fridge_id : 0;
        $distributor_id = $distributor_id ? $distributor_id : 0;

        // Include the template
        include PEXPRESS_PLUGIN_DIR . 'templates/order-edit.php';
    }

    /**
     * Add order manipulation UI to order edit screen
     *
     * @param WC_Order $order Order object.
     */
    public function add_order_manipulation_ui($order)
    {
        // Check permissions
        if (!current_user_can('polar_support') && !current_user_can('edit_shop_orders')) {
            return;
        }

        $order_id = $order->get_id();
        $modification_log = $this->get_modification_log($order_id);
?>
        <div class="polar-order-manipulation-wrapper">
            <h3><?php esc_html_e('Order Manipulation', 'pexpress'); ?></h3>

            <div class="polar-add-item-section">
                <h4><?php esc_html_e('Add Item to Order', 'pexpress'); ?></h4>
                <div class="polar-add-item-form">
                    <select id="polar-product-search" class="polar-product-select" style="width: 100%;">
                        <option value=""><?php esc_html_e('Search for a product...', 'pexpress'); ?></option>
                    </select>
                    <input type="number" id="polar-item-quantity" min="1" value="1" placeholder="<?php esc_attr_e('Quantity', 'pexpress'); ?>" />
                    <button type="button" class="button button-primary polar-add-item-btn" data-order-id="<?php echo esc_attr($order_id); ?>">
                        <?php esc_html_e('Add to Order', 'pexpress'); ?>
                    </button>
                </div>
            </div>

            <div class="polar-modification-history">
                <h4>
                    <?php esc_html_e('Modification History', 'pexpress'); ?>
                    <span class="polar-toggle-history dashicons dashicons-arrow-down-alt2"></span>
                </h4>
                <div class="polar-history-content" style="display: none;">
                    <?php if (empty($modification_log)) : ?>
                        <p><?php esc_html_e('No modifications recorded.', 'pexpress'); ?></p>
                    <?php else : ?>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date/Time', 'pexpress'); ?></th>
                                    <th><?php esc_html_e('User', 'pexpress'); ?></th>
                                    <th><?php esc_html_e('Action', 'pexpress'); ?></th>
                                    <th><?php esc_html_e('Details', 'pexpress'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($modification_log) as $log_entry) : ?>
                                    <tr class="polar-log-entry polar-log-<?php echo esc_attr($log_entry['action']); ?>">
                                        <td><?php echo esc_html($log_entry['timestamp']); ?></td>
                                        <td><?php echo esc_html($log_entry['user_name']); ?></td>
                                        <td>
                                            <span class="polar-action-badge polar-action-<?php echo esc_attr($log_entry['action']); ?>">
                                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $log_entry['action']))); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            if (!empty($log_entry['old_value']) || !empty($log_entry['new_value'])) {
                                                echo '<div class="polar-log-details">';
                                                if (!empty($log_entry['old_value'])) {
                                                    echo '<strong>Before:</strong> ';
                                                    echo esc_html($this->format_log_value($log_entry['old_value']));
                                                    echo '<br>';
                                                }
                                                if (!empty($log_entry['new_value'])) {
                                                    echo '<strong>After:</strong> ';
                                                    echo esc_html($this->format_log_value($log_entry['new_value']));
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Format log value for display
     *
     * @param array $value Log value array.
     * @return string
     */
    private function format_log_value($value)
    {
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
    }

    /**
     * AJAX handler: Add item to order
     */
    public function ajax_add_order_item()
    {
        $this->verify_request();

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $quantity_input = isset($_POST['quantity']) ? wp_unslash($_POST['quantity']) : 1;
        $quantity = wc_stock_amount($quantity_input);

        if (!$order_id || !$product_id || $quantity <= 0) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'pexpress')));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'pexpress')));
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found.', 'pexpress')));
        }

        if (\Automattic\WooCommerce\Enums\ProductType::VARIABLE === $product->get_type()) {
            wp_send_json_error(array('message' => __('Variable product parents cannot be added directly.', 'pexpress')));
        }

        $validation_error = apply_filters('woocommerce_ajax_add_order_item_validation', new WP_Error(), $product, $order, $quantity);
        if ($validation_error instanceof WP_Error && $validation_error->get_error_code()) {
            wp_send_json_error(array('message' => $validation_error->get_error_message()));
        }

        $old_total = $order->get_total();

        $item_id = $order->add_product($product, $quantity, array('order' => $order));
        if (!$item_id) {
            wp_send_json_error(array('message' => __('Failed to add item.', 'pexpress')));
        }

        $item = $order->get_item($item_id);
        if ($item instanceof WC_Order_Item) {
            $item = apply_filters('woocommerce_ajax_order_item', $item, $item_id, $order, $product);
            do_action('woocommerce_ajax_add_order_item_meta', $item_id, $item, $order);
        }

        $order->calculate_totals(true);
        $order->save();

        $added_items = array();
        if ($item instanceof WC_Order_Item) {
            $added_items[$item_id] = $item;
        }

        if (!empty($added_items)) {
            do_action('woocommerce_ajax_order_items_added', $added_items, $order);
            $order->add_order_note(sprintf(__('Added line items: %s', 'woocommerce'), $product->get_formatted_name()), false, true);
        }

        $new_total = $order->get_total();

        $new_value = null;
        if ($item instanceof WC_Order_Item_Product) {
            $new_value = array(
                'product_id' => $item->get_product_id(),
                'quantity' => $item->get_quantity(),
                'price' => wc_format_decimal($item->get_total()),
            );
        }

        $this->log_order_modification(
            $order_id,
            'item_added',
            null,
            $new_value,
            $old_total,
            $new_total
        );

        wp_send_json_success(array(
            'message' => __('Item added successfully.', 'pexpress'),
            'order_total' => $new_total,
            'item_id' => $item_id,
        ));
    }

    /**
     * AJAX handler: Remove item from order
     */
    public function ajax_remove_order_item()
    {
        $this->verify_request();

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;

        if (!$order_id || !$item_id) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'pexpress')));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'pexpress')));
        }

        $item = $order->get_item($item_id);
        if (!$item || !($item instanceof WC_Order_Item_Product)) {
            wp_send_json_error(array('message' => __('Item not found.', 'pexpress')));
        }
        /** @var WC_Order_Item_Product $item */

        $old_total = $order->get_total();
        $old_value = array(
            'product_id' => $item->get_product_id(),
            'quantity' => $item->get_quantity(),
            'price' => $item->get_total(),
        );

        // Remove item
        $order->remove_item($item_id);
        $order->calculate_totals();
        $order->save();

        $new_total = $order->get_total();

        // Log modification
        $this->log_order_modification(
            $order_id,
            'item_removed',
            $old_value,
            null,
            $old_total,
            $new_total
        );

        wp_send_json_success(array(
            'message' => __('Item removed successfully.', 'pexpress'),
            'order_total' => $new_total,
        ));
    }

    /**
     * AJAX handler: Update order item
     */
    public function ajax_update_order_item()
    {
        $this->verify_request();

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;

        $quantity_provided = array_key_exists('quantity', $_POST);
        $price_provided = array_key_exists('price', $_POST);

        $quantity = $quantity_provided ? absint($_POST['quantity']) : null;

        $price = null;
        if ($price_provided) {
            $price_raw = wc_clean(wp_unslash($_POST['price']));
            if ($price_raw === '') {
                $price_provided = false;
            } elseif (!is_numeric($price_raw)) {
                wp_send_json_error(array('message' => __('Invalid price value.', 'pexpress')));
            } else {
                $price = (float) wc_format_decimal($price_raw);
            }
        }

        if (!$order_id || !$item_id) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'pexpress')));
        }

        if (!$quantity_provided && !$price_provided) {
            wp_send_json_error(array('message' => __('No changes specified.', 'pexpress')));
        }

        if ($quantity_provided && $quantity < 1) {
            wp_send_json_error(array('message' => __('Quantity must be at least 1.', 'pexpress')));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'pexpress')));
        }

        $item = $order->get_item($item_id);
        /** @var WC_Order_Item_Product|null $item */
        if (!$item) {
            wp_send_json_error(array('message' => __('Item not found.', 'pexpress')));
        }

        $old_total = $order->get_total();
        $old_value = array(
            'product_id' => $item->get_product_id(),
            'quantity' => $item->get_quantity(),
            'price' => $item->get_total(),
        );

        $existing_quantity = max($item->get_quantity(), 1);
        $existing_line_total = wc_format_decimal($item->get_total());
        $unit_price = $existing_quantity > 0 ? $existing_line_total / $existing_quantity : 0;
        $unit_price = wc_format_decimal($unit_price);

        $quantity_changed = false;
        $new_quantity = $item->get_quantity();
        if ($quantity_provided && $quantity > 0 && $quantity !== $item->get_quantity()) {
            $item->set_quantity($quantity);
            $new_quantity = $quantity;
            $quantity_changed = true;
        }

        if ($price_provided && $price !== null) {
            $unit_price = wc_format_decimal($price);
        }

        if ($price_provided || $quantity_changed) {
            $line_total = wc_format_decimal($unit_price * max($new_quantity, 1));
            $item->set_subtotal($line_total);
            $item->set_total($line_total);
            $item->set_subtotal_tax(0);
            $item->set_total_tax(0);
            $item->set_taxes(array());
        }

        $item->save();

        $order->calculate_totals(true);
        $order->save();

        $new_total = $order->get_total();
        $new_value = array(
            'product_id' => $item->get_product_id(),
            'quantity' => $item->get_quantity(),
            'price' => $item->get_total(),
        );

        // Determine action type
        $action = 'item_updated';
        if ($quantity_provided && $price_provided) {
            $action = 'item_updated';
        } elseif ($quantity_provided) {
            $action = 'quantity_changed';
        } elseif ($price_provided) {
            $action = 'price_changed';
        }

        // Log modification
        $this->log_order_modification(
            $order_id,
            $action,
            $old_value,
            $new_value,
            $old_total,
            $new_total
        );

        wp_send_json_success(array(
            'message' => __('Item updated successfully.', 'pexpress'),
            'order_total' => $new_total,
        ));
    }

    /**
     * AJAX handler: Replace order item
     */
    public function ajax_replace_order_item()
    {
        $this->verify_request();

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;
        $new_product_id = isset($_POST['new_product_id']) ? absint($_POST['new_product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;

        if (!$order_id || !$item_id || !$new_product_id) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'pexpress')));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'pexpress')));
        }

        $item = $order->get_item($item_id);
        /** @var WC_Order_Item_Product|null $item */
        if (!$item) {
            wp_send_json_error(array('message' => __('Item not found.', 'pexpress')));
        }

        $new_product = wc_get_product($new_product_id);
        if (!$new_product) {
            wp_send_json_error(array('message' => __('New product not found.', 'pexpress')));
        }

        $old_total = $order->get_total();
        $old_value = array(
            'product_id' => $item->get_product_id(),
            'quantity' => $item->get_quantity(),
            'price' => $item->get_total(),
        );

        // Remove old item
        $order->remove_item($item_id);

        // Add new item
        $new_item_id = $order->add_product($new_product, $quantity);
        if (!$new_item_id) {
            wp_send_json_error(array('message' => __('Failed to add replacement item.', 'pexpress')));
        }

        $order->calculate_totals();
        $order->save();

        $new_total = $order->get_total();
        $new_value = array(
            'product_id' => $new_product_id,
            'quantity' => $quantity,
            'price' => $new_product->get_price() * $quantity,
        );

        // Log modification
        $this->log_order_modification(
            $order_id,
            'item_replaced',
            $old_value,
            $new_value,
            $old_total,
            $new_total
        );

        wp_send_json_success(array(
            'message' => __('Item replaced successfully.', 'pexpress'),
            'order_total' => $new_total,
            'item_id' => $new_item_id,
        ));
    }

    /**
     * AJAX handler: Get product alternatives for replacement
     */
    public function ajax_get_product_alternatives()
    {
        $this->verify_request();

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID.', 'pexpress')));
        }

        $current_product = wc_get_product($product_id);
        if (!$current_product) {
            wp_send_json_error(array('message' => __('Product not found.', 'pexpress')));
        }

        // Get products in same category
        $categories = $current_product->get_category_ids();
        $args = array(
            'status' => 'publish',
            'limit' => 20,
            'exclude' => array($product_id),
        );

        if (!empty($categories)) {
            $args['category'] = $categories;
        }

        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }

        $products = wc_get_products($args);
        $results = array();

        foreach ($products as $product) {
            $results[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'stock_status' => $product->get_stock_status(),
                'sku' => $product->get_sku(),
            );
        }

        wp_send_json_success(array('products' => $results));
    }

    /**
     * AJAX handler: Recalculate order totals
     */
    public function ajax_recalculate_order_totals()
    {
        $this->verify_request();

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;

        if (!$order_id) {
            wp_send_json_error(array('message' => __('Invalid order ID.', 'pexpress')));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'pexpress')));
        }

        $old_total = $order->get_total();
        $order->calculate_totals();
        $order->save();
        $new_total = $order->get_total();

        wp_send_json_success(array(
            'message' => __('Totals recalculated.', 'pexpress'),
            'order_total' => $new_total,
            'old_total' => $old_total,
        ));
    }

    /**
     * Verify AJAX request
     */
    private function verify_request()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'polar_order_edit_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'pexpress')));
        }

        // Check permissions
        if (!current_user_can('polar_support') && !current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'pexpress')));
        }
    }

    /**
     * Log order modification
     *
     * @param int    $order_id        Order ID.
     * @param string $action          Action type.
     * @param array  $old_value       Old value.
     * @param array  $new_value       New value.
     * @param float  $order_total_before Old total.
     * @param float  $order_total_after  New total.
     */
    private function log_order_modification($order_id, $action, $old_value = null, $new_value = null, $order_total_before = null, $order_total_after = null)
    {
        $user = wp_get_current_user();
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => $user->ID,
            'user_name' => $user->display_name,
            'action' => $action,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'order_total_before' => $order_total_before,
            'order_total_after' => $order_total_after,
        );

        $log = $this->get_modification_log($order_id);
        $log[] = $log_entry;

        PExpress_Core::update_order_meta($order_id, '_polar_modification_log', $log);
    }

    /**
     * Get modification log for an order
     *
     * @param int $order_id Order ID.
     * @return array
     */
    private function get_modification_log($order_id)
    {
        $log = PExpress_Core::get_order_meta($order_id, '_polar_modification_log');
        return is_array($log) ? $log : array();
    }
}
