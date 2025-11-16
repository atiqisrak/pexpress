<?php

/**
 * Shortcode Definitions
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Global flag to track if shortcode was used
global $pexpress_shortcode_used;
$pexpress_shortcode_used = false;

// Detect shortcodes early in the_content filter
add_filter('the_content', 'pexpress_detect_shortcodes', 1);
function pexpress_detect_shortcodes($content)
{
    global $pexpress_shortcode_used;
    $shortcodes = array('polar_hr', 'polar_agency', 'polar_delivery', 'polar_sr', 'polar_fridge', 'polar_distributor', 'polar_product_provider', 'polar_support');
    foreach ($shortcodes as $shortcode) {
        if (has_shortcode($content, $shortcode)) {
            $pexpress_shortcode_used = true;
            break;
        }
    }
    return $content;
}

// Output CSS directly in head when shortcode is detected
// This ensures CSS loads even if shortcode runs after wp_enqueue_scripts
add_action('wp_head', 'pexpress_output_shortcode_css', 99);
function pexpress_output_shortcode_css()
{
    global $pexpress_shortcode_used;
    if ($pexpress_shortcode_used && !wp_style_is('polar-express', 'enqueued') && !wp_style_is('polar-express', 'done')) {
        $css_url = PEXPRESS_PLUGIN_URL . 'assets/css/polar.css';
        $version = PEXPRESS_VERSION;
        echo '<link rel="stylesheet" id="polar-express-css" href="' . esc_url($css_url) . '?ver=' . esc_attr($version) . '" type="text/css" media="all">' . "\n";
    }
}

// Enqueue scripts in footer (scripts can load in footer)
add_action('wp_footer', 'pexpress_enqueue_shortcode_scripts', 1);
function pexpress_enqueue_shortcode_scripts()
{
    global $pexpress_shortcode_used;
    if ($pexpress_shortcode_used && !wp_script_is('polar-express', 'enqueued')) {
        wp_enqueue_script(
            'polar-express',
            PEXPRESS_PLUGIN_URL . 'assets/js/polar.js',
            array('jquery', 'heartbeat'),
            PEXPRESS_VERSION,
            true
        );

        wp_localize_script(
            'polar-express',
            'polarExpress',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('polar_express_nonce'),
            )
        );
    }
}

/**
 * Agency Dashboard Shortcode (formerly HR)
 */
add_shortcode('polar_hr', 'polar_agency_dashboard_shortcode');
function polar_agency_dashboard_shortcode($atts)
{
    // Check role
    $current_user = wp_get_current_user();
    if (!in_array('polar_hr', $current_user->roles) && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Agency.', 'pexpress') . '</p>';
    }

    // Get orders needing assignment
    $pending_orders = wc_get_orders(array(
        'status' => 'processing',
        'limit' => -1,
        'meta_key' => '_polar_needs_assignment',
        'meta_value' => 'yes',
    ));

    // Get all HR (formerly delivery), fridge, and distributor users
    $hr_users = get_users(array('role' => 'polar_delivery'));
    $fridge_users = get_users(array('role' => 'polar_fridge'));
    $distributor_users = get_users(array('role' => 'polar_distributor'));

    // Set flag to enqueue assets
    global $pexpress_shortcode_used;
    $pexpress_shortcode_used = true;

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/hr-dashboard.php';
    return ob_get_clean();
}

/**
 * HR Dashboard Shortcode (formerly Delivery)
 */
add_shortcode('polar_delivery', 'polar_hr_dashboard_shortcode');
function polar_hr_dashboard_shortcode($atts)
{
    $current_user = wp_get_current_user();
    if (!in_array('polar_delivery', $current_user->roles) && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar HR.', 'pexpress') . '</p>';
    }

    $user_id = get_current_user_id();

    // Get orders assigned to this HR person
    $assigned_orders = PExpress_Core::get_assigned_orders($user_id, 'delivery');

    // Get orders by status
    $out_orders = array();
    $delivered_orders = array();

    foreach ($assigned_orders as $order) {
        if ($order->get_status() === 'wc-polar-out') {
            $out_orders[] = $order;
        } elseif ($order->get_status() === 'wc-polar-delivered') {
            $delivered_orders[] = $order;
        }
    }

    // Set flag to enqueue assets
    global $pexpress_shortcode_used;
    $pexpress_shortcode_used = true;

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/delivery-dashboard.php';
    return ob_get_clean();
}

/**
 * Fridge Provider Dashboard Shortcode
 */
add_shortcode('polar_fridge', 'polar_fridge_dashboard_shortcode');
function polar_fridge_dashboard_shortcode($atts)
{
    $current_user = wp_get_current_user();
    if (!in_array('polar_fridge', $current_user->roles) && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Fridge Provider.', 'pexpress') . '</p>';
    }

    $user_id = get_current_user_id();

    // Get orders assigned to this fridge provider
    $assigned_orders = PExpress_Core::get_assigned_orders($user_id, 'fridge');

    // Get orders by status
    $collected_orders = array();
    $return_pending = array();

    foreach ($assigned_orders as $order) {
        $return_date = PExpress_Core::get_order_meta($order->get_id(), '_polar_fridge_return_date');
        if ($order->get_status() === 'wc-polar-fridge-back') {
            $collected_orders[] = $order;
        } else {
            $return_pending[] = $order;
        }
    }

    // Set flag to enqueue assets
    global $pexpress_shortcode_used;
    $pexpress_shortcode_used = true;

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/fridge-dashboard.php';
    return ob_get_clean();
}

/**
 * Distributor Dashboard Shortcode
 */
add_shortcode('polar_distributor', 'polar_distributor_dashboard_shortcode');
function polar_distributor_dashboard_shortcode($atts)
{
    $current_user = wp_get_current_user();
    if (!in_array('polar_distributor', $current_user->roles) && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Distributor.', 'pexpress') . '</p>';
    }

    $user_id = get_current_user_id();

    // Get orders assigned to this distributor
    $assigned_orders = PExpress_Core::get_assigned_orders($user_id, 'distributor');

    // Set flag to enqueue assets
    global $pexpress_shortcode_used;
    $pexpress_shortcode_used = true;

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/distributor-dashboard.php';
    return ob_get_clean();
}

/**
 * Support Dashboard Shortcode
 */
add_shortcode('polar_support', 'polar_support_dashboard_shortcode');
function polar_support_dashboard_shortcode($atts)
{
    $current_user = wp_get_current_user();
    if (!in_array('polar_support', $current_user->roles) && !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('Access denied. You must be logged in as Polar Support.', 'pexpress') . '</p>';
    }

    // Get recent orders
    $recent_orders = wc_get_orders(array(
        'status' => 'any',
        'limit' => 50,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    // Ensure $recent_orders is always an array
    if (!is_array($recent_orders)) {
        $recent_orders = array();
    }

    // Set flag to enqueue assets
    global $pexpress_shortcode_used;
    $pexpress_shortcode_used = true;

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/support-dashboard.php';
    return ob_get_clean();
}

/**
 * Order Tracking Shortcode for Customers
 */
add_shortcode('polar_order_tracking', 'polar_order_tracking_shortcode');
function polar_order_tracking_shortcode($atts)
{
    // Don't execute during REST API content save/update requests (block editor) to prevent JSON errors
    // This prevents shortcode output from breaking REST API JSON responses
    if (defined('REST_REQUEST') && REST_REQUEST) {
        // Check if this is a POST/PUT/PATCH request (saving content)
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        if (in_array($method, array('POST', 'PUT', 'PATCH'), true)) {
            // Only block if it's a content endpoint
            $route = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            if (preg_match('#/wp/v2/(posts|pages)/#', $route)) {
                return '';
            }
        }
    }

    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'order_id' => 0,
    ), $atts, 'polar_order_tracking');

    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p>' . esc_html__('Please log in to view your order tracking.', 'pexpress') . '</p>';
    }

    $current_user = wp_get_current_user();
    $order_id = 0;

    // Get order ID from attribute, URL parameter, or current user's orders
    if (!empty($atts['order_id'])) {
        $order_id = absint($atts['order_id']);
    } elseif (isset($_GET['order_id'])) {
        $order_id = absint($_GET['order_id']);
    } else {
        // Get the most recent order for the current user
        $customer_orders = wc_get_orders(array(
            'customer_id' => $current_user->ID,
            'status' => 'any',
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        if (!empty($customer_orders)) {
            $order_id = $customer_orders[0]->get_id();
        }
    }

    if (!$order_id) {
        return '<p>' . esc_html__('No order found. Please provide an order ID.', 'pexpress') . '</p>';
    }

    // Get order
    $order = wc_get_order($order_id);
    if (!$order) {
        return '<p>' . esc_html__('Order not found.', 'pexpress') . '</p>';
    }

    // Check if user owns this order (unless admin)
    if (!current_user_can('manage_woocommerce')) {
        $customer_id = $order->get_customer_id();
        if (empty($customer_id) || $customer_id != $current_user->ID) {
            return '<p>' . esc_html__('Access denied. This order does not belong to you.', 'pexpress') . '</p>';
        }
    }

    // Get role statuses with default values
    $hr_status = PExpress_Core::get_role_status($order_id, 'agency');
    if (empty($hr_status)) {
        $hr_status = 'pending';
    }
    $delivery_status = PExpress_Core::get_role_status($order_id, 'delivery');
    if (empty($delivery_status)) {
        $delivery_status = 'pending';
    }
    $fridge_status = PExpress_Core::get_role_status($order_id, 'fridge');
    if (empty($fridge_status)) {
        $fridge_status = 'pending';
    }
    $distributor_status = PExpress_Core::get_role_status($order_id, 'distributor');
    if (empty($distributor_status)) {
        $distributor_status = 'pending';
    }

    // Get assigned users
    $delivery_user_id = PExpress_Core::get_delivery_user_id($order_id);
    $fridge_user_id = PExpress_Core::get_fridge_user_id($order_id);
    $distributor_user_id = PExpress_Core::get_distributor_user_id($order_id);

    // Get user names with safe checks
    $delivery_user_name = '';
    if ($delivery_user_id) {
        $delivery_user = get_userdata($delivery_user_id);
        $delivery_user_name = $delivery_user ? $delivery_user->display_name : '';
    }

    $fridge_user_name = '';
    if ($fridge_user_id) {
        $fridge_user = get_userdata($fridge_user_id);
        $fridge_user_name = $fridge_user ? $fridge_user->display_name : '';
    }

    $distributor_user_name = '';
    if ($distributor_user_id) {
        $distributor_user = get_userdata($distributor_user_id);
        $distributor_user_name = $distributor_user ? $distributor_user->display_name : '';
    }

    // Enqueue assets
    wp_enqueue_style(
        'polar-order-tracking',
        PEXPRESS_PLUGIN_URL . 'assets/css/polar-order-tracking.css',
        array(),
        PEXPRESS_VERSION
    );

    wp_enqueue_script(
        'polar-order-tracking',
        PEXPRESS_PLUGIN_URL . 'assets/js/polar-order-tracking.js',
        array('jquery', 'heartbeat'),
        PEXPRESS_VERSION,
        true
    );

    wp_localize_script(
        'polar-order-tracking',
        'polarOrderTracking',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('polar_order_tracking_nonce'),
            'orderId' => $order_id,
        )
    );

    // Make variables available to template
    $order_id = $order_id;
    $order = $order;
    $hr_status = $hr_status;
    $delivery_status = $delivery_status;
    $fridge_status = $fridge_status;
    $distributor_status = $distributor_status;
    $delivery_user_name = $delivery_user_name;
    $fridge_user_name = $fridge_user_name;
    $distributor_user_name = $distributor_user_name;

    ob_start();
    include PEXPRESS_PLUGIN_DIR . 'templates/order-tracking.php';
    return ob_get_clean();
}

/**
 * Agency Dashboard Shortcode (Alias for polar_hr)
 */
add_shortcode('polar_agency', 'polar_agency_dashboard_shortcode');

/**
 * SR Dashboard Shortcode (Alias for polar_delivery)
 */
add_shortcode('polar_sr', 'polar_hr_dashboard_shortcode');

/**
 * Product Provider Dashboard Shortcode (Alias for polar_distributor)
 */
add_shortcode('polar_product_provider', 'polar_distributor_dashboard_shortcode');

/**
 * Order Information Shortcode for Customers
 */
add_shortcode('polar_order_information', 'polar_order_information_shortcode');
function polar_order_information_shortcode($atts)
{
    // Don't execute during REST API content save/update requests (block editor) to prevent JSON errors
    // This prevents shortcode output from breaking REST API JSON responses
    if (defined('REST_REQUEST') && REST_REQUEST) {
        // Check if this is a POST/PUT/PATCH request (saving content)
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        if (in_array($method, array('POST', 'PUT', 'PATCH'), true)) {
            // Only block if it's a content endpoint
            $route = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            if (preg_match('#/wp/v2/(posts|pages)/#', $route)) {
                return '';
            }
        }
    }

    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'order_id' => 0,
    ), $atts, 'polar_order_information');

    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p>' . esc_html__('Please log in to view your order information.', 'pexpress') . '</p>';
    }

    $current_user = wp_get_current_user();
    $order_id = 0;

    // Get order ID from attribute, URL parameter, or current user's orders
    if (!empty($atts['order_id'])) {
        $order_id = absint($atts['order_id']);
    } elseif (isset($_GET['order_id'])) {
        $order_id = absint($_GET['order_id']);
    } else {
        // Get the most recent order for the current user
        $customer_orders = wc_get_orders(array(
            'customer_id' => $current_user->ID,
            'status' => 'any',
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        if (!empty($customer_orders)) {
            $order_id = $customer_orders[0]->get_id();
        }
    }

    if (!$order_id) {
        return '<p>' . esc_html__('No order found. Please provide an order ID.', 'pexpress') . '</p>';
    }

    // Get order
    $order = wc_get_order($order_id);
    if (!$order) {
        return '<p>' . esc_html__('Order not found.', 'pexpress') . '</p>';
    }

    // Check if user owns this order (unless admin)
    if (!current_user_can('manage_woocommerce')) {
        $customer_id = $order->get_customer_id();
        if (empty($customer_id) || $customer_id != $current_user->ID) {
            return '<p>' . esc_html__('Access denied. This order does not belong to you.', 'pexpress') . '</p>';
        }
    }

    // Get order items
    $order_items = $order->get_items();

    // Get order meta
    $billing_email = $order->get_billing_email() ? $order->get_billing_email() : '';
    $billing_phone = $order->get_billing_phone() ? $order->get_billing_phone() : '';
    $billing_address = $order->get_formatted_billing_address() ? $order->get_formatted_billing_address() : __('No address provided', 'pexpress');
    $shipping_address = $order->get_formatted_shipping_address() ? $order->get_formatted_shipping_address() : __('No shipping address provided', 'pexpress');
    $order_status = $order->get_status() ? $order->get_status() : 'pending';
    $order_date = $order->get_date_created() ? $order->get_date_created()->date_i18n('F j, Y g:i A') : '';
    $order_total = $order->get_formatted_order_total() ? $order->get_formatted_order_total() : wc_price(0);
    $customer_name = PExpress_Core::get_billing_name($order);

    // Get meeting information
    $meeting_type = PExpress_Core::get_meeting_type($order_id);
    $meeting_location = PExpress_Core::get_meeting_location($order_id);
    $meeting_datetime = PExpress_Core::get_meeting_datetime($order_id);
    $meeting_datetime_display = '';
    if (!empty($meeting_datetime)) {
        $meeting_timestamp = strtotime($meeting_datetime);
        if ($meeting_timestamp) {
            $meeting_datetime_display = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $meeting_timestamp);
        } else {
            $meeting_datetime_display = $meeting_datetime;
        }
    }

    // Enqueue assets
    wp_enqueue_style(
        'polar-order-edit',
        PEXPRESS_PLUGIN_URL . 'assets/css/polar-order-edit.css',
        array(),
        PEXPRESS_VERSION
    );

    // Make variables available to template
    $order_id = $order_id;
    $order = $order;
    $order_items = $order_items;
    $billing_email = $billing_email;
    $billing_phone = $billing_phone;
    $billing_address = $billing_address;
    $shipping_address = $shipping_address;
    $order_status = $order_status;
    $order_date = $order_date;
    $order_total = $order_total;
    $customer_name = $customer_name;
    $meeting_type = $meeting_type;
    $meeting_location = $meeting_location;
    $meeting_datetime = $meeting_datetime;
    $meeting_datetime_display = $meeting_datetime_display;

    ob_start();
?>
    <div class="wrap polar-dashboard polar-order-information">
        <div class="polar-dashboard-header">
            <div class="polar-header-content">
                <h1 class="polar-dashboard-title">
                    <span class="polar-title-icon">📋</span>
                    <?php esc_html_e('Order Information', 'pexpress'); ?>
                    <span class="order-id-badge">
                        #<?php echo esc_html($order_id); ?>
                    </span>
                </h1>
                <p class="polar-dashboard-subtitle"><?php esc_html_e('View your order details', 'pexpress'); ?></p>
            </div>
        </div>

        <div class="polar-order-info-content">
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
                    <?php if ($billing_phone) : ?>
                        <div class="order-detail-row">
                            <div class="order-detail-item">
                                <span class="detail-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 5C3 3.89543 3.89543 3 5 3H8.27924C8.70967 3 9.09181 3.27543 9.22792 3.68377L10.7257 8.17721C10.8831 8.64932 10.6694 9.16531 10.2243 9.38787L7.96701 10.5165C9.06925 12.9612 11.0388 14.9308 13.4835 16.033L14.6121 13.7757C14.8347 13.3306 15.3507 13.1169 15.8228 13.2743L20.3162 14.7721C20.7246 14.9082 21 15.2903 21 15.7208V19C21 20.1046 20.1046 21 19 21H18C9.71573 21 3 14.2843 3 6V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div class="detail-content">
                                    <span class="detail-label"><?php esc_html_e('Phone', 'pexpress'); ?></span>
                                    <a href="tel:<?php echo esc_attr($billing_phone); ?>" class="detail-value detail-link">
                                        <?php echo esc_html($billing_phone); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Billing Address Card -->
            <?php if ($billing_address && $billing_address !== __('No address provided', 'pexpress')) : ?>
                <div class="polar-order-item">
                    <div class="order-header">
                        <h4><?php esc_html_e('Billing Address', 'pexpress'); ?></h4>
                    </div>
                    <div class="order-details">
                        <div class="order-detail-row">
                            <div class="order-detail-item">
                                <span class="detail-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.364 3.63604C20.0518 5.32387 21 7.61305 21 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div class="detail-content">
                                    <span class="detail-value"><?php echo wp_kses_post(nl2br($billing_address)); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Shipping Address Card -->
            <?php if ($shipping_address && $shipping_address !== __('No shipping address provided', 'pexpress')) : ?>
                <div class="polar-order-item">
                    <div class="order-header">
                        <h4><?php esc_html_e('Shipping Address', 'pexpress'); ?></h4>
                    </div>
                    <div class="order-details">
                        <div class="order-detail-row">
                            <div class="order-detail-item">
                                <span class="detail-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.364 3.63604C20.0518 5.32387 21 7.61305 21 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div class="detail-content">
                                    <span class="detail-value"><?php echo wp_kses_post(nl2br($shipping_address)); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Meeting Information Card -->
            <?php if ($meeting_type || $meeting_location || $meeting_datetime_display) : ?>
                <div class="polar-order-item">
                    <div class="order-header">
                        <h4><?php esc_html_e('Meeting Information', 'pexpress'); ?></h4>
                    </div>
                    <div class="order-details">
                        <?php if ($meeting_type) : ?>
                            <div class="order-detail-row">
                                <div class="order-detail-item">
                                    <span class="detail-label"><?php esc_html_e('Meeting Type', 'pexpress'); ?></span>
                                    <span class="detail-value">
                                        <?php
                                        if ($meeting_type === 'meet_point') {
                                            esc_html_e('Meet Point', 'pexpress');
                                        } elseif ($meeting_type === 'delivery_location') {
                                            esc_html_e('Delivery Location', 'pexpress');
                                        } else {
                                            echo esc_html($meeting_type);
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($meeting_location) : ?>
                            <div class="order-detail-row">
                                <div class="order-detail-item">
                                    <span class="detail-label"><?php esc_html_e('Meeting Location', 'pexpress'); ?></span>
                                    <span class="detail-value"><?php echo esc_html($meeting_location); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($meeting_datetime_display) : ?>
                            <div class="order-detail-row">
                                <div class="order-detail-item">
                                    <span class="detail-label"><?php esc_html_e('Meeting Date & Time', 'pexpress'); ?></span>
                                    <span class="detail-value"><?php echo esc_html($meeting_datetime_display); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Order Items Card -->
            <?php if (!empty($order_items)) : ?>
                <div class="polar-order-item">
                    <div class="order-header">
                        <h4><?php esc_html_e('Order Items', 'pexpress'); ?></h4>
                    </div>
                    <div class="order-items-list">
                        <table class="polar-order-items-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Product', 'pexpress'); ?></th>
                                    <th><?php esc_html_e('Quantity', 'pexpress'); ?></th>
                                    <th><?php esc_html_e('Price', 'pexpress'); ?></th>
                                    <th><?php esc_html_e('Total', 'pexpress'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item_id => $item) : ?>
                                    <?php
                                    $product = $item->get_product();
                                    $product_name = $item->get_name();
                                    $quantity = $item->get_quantity();
                                    $line_total = $item->get_total();
                                    $line_subtotal = $item->get_subtotal();
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($product_name); ?></strong>
                                            <?php
                                            $meta_data = $item->get_formatted_meta_data('');
                                            if (!empty($meta_data)) {
                                                echo '<div class="item-meta">';
                                                foreach ($meta_data as $meta) {
                                                    echo '<small>' . esc_html($meta->display_key) . ': ' . esc_html($meta->display_value) . '</small>';
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo esc_html($quantity); ?></td>
                                        <td><?php echo wp_kses_post(wc_price($line_subtotal / $quantity)); ?></td>
                                        <td><strong><?php echo wp_kses_post(wc_price($line_total)); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right"><strong><?php esc_html_e('Order Total', 'pexpress'); ?>:</strong></td>
                                    <td><strong><?php echo wp_kses_post($order_total); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}
