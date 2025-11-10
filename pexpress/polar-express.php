<?php

/**
 * Plugin Name: Polar Express
 * Plugin URI: https://github.com/atiqisrak/pexpress
 * Description: Custom WordPress extension designed to enhance manual order processing and delivery workflows for Polar's bulk ice cream service.
 * Version: 1.0.4
 * Author: Atiq Israk
 * Author URI: https://ethertech.ltd/
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: pexpress
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.5.2
 */

// Declare WooCommerce feature compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_item_tables', __FILE__, true);
    }
});

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PEXPRESS_VERSION', '1.0.4');
define('PEXPRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PEXPRESS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PEXPRESS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class PExpress
{

    /**
     * Single instance of the class
     *
     * @var PExpress
     */
    private static $instance = null;

    /**
     * Get single instance of the class
     *
     * @return PExpress
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     * Initialize plugin
     */
    private function init()
    {
        // Load plugin textdomain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Check for required dependencies
        add_action('admin_notices', array($this, 'check_dependencies'));

        // Load plugin files
        $this->load_dependencies();

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'pexpress',
            false,
            dirname(PEXPRESS_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Check for required dependencies
     */
    public function check_dependencies()
    {
        if (!class_exists('WooCommerce')) {
?>
            <div class="notice notice-error">
                <p><?php esc_html_e('Polar Express requires WooCommerce to be installed and active.', 'pexpress'); ?></p>
            </div>
<?php
        }
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies()
    {
        // Load core files
        require_once PEXPRESS_PLUGIN_DIR . 'includes/class-pexpress-core.php';
        require_once PEXPRESS_PLUGIN_DIR . 'includes/class-pexpress-order-statuses.php';

        // Load module files
        require_once PEXPRESS_PLUGIN_DIR . 'roles.php';
        require_once PEXPRESS_PLUGIN_DIR . 'shortcodes.php';
        require_once PEXPRESS_PLUGIN_DIR . 'webhook.php';
        require_once PEXPRESS_PLUGIN_DIR . 'heartbeat.php';
        require_once PEXPRESS_PLUGIN_DIR . 'sms.php';

        // Load admin files if in admin
        if (is_admin()) {
            require_once PEXPRESS_PLUGIN_DIR . 'admin/class-pexpress-admin-setup-wizard.php';
            require_once PEXPRESS_PLUGIN_DIR . 'admin/class-pexpress-admin.php';
            new PExpress_Admin();
        }

        // Load public files
        require_once PEXPRESS_PLUGIN_DIR . 'public/class-pexpress-public.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize modules
        add_action('init', array($this, 'init_modules'));

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // AJAX handlers
        add_action('wp_ajax_polar_assign_order', array($this, 'ajax_assign_order'));
        add_action('wp_ajax_polar_update_order_status', array($this, 'ajax_update_order_status'));

        // Plugin action links
        add_filter('plugin_action_links_' . PEXPRESS_PLUGIN_BASENAME, array($this, 'plugin_action_links'));

        // Redirect to setup wizard on activation (admin only)
        if (is_admin() && !wp_doing_ajax()) {
            add_action('admin_init', array($this, 'maybe_redirect_to_setup'));
        }
    }

    /**
     * Redirect to setup wizard if needed
     */
    public function maybe_redirect_to_setup()
    {
        // Only redirect if transient is set and user has permission
        if (!get_transient('pexpress_redirect_to_setup')) {
            return;
        }

        // Delete transient
        delete_transient('pexpress_redirect_to_setup');

        // Check if user has permission
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        // Don't redirect if already on setup wizard or if setup is completed
        if (isset($_GET['page']) && $_GET['page'] === 'polar-express-setup-wizard') {
            return;
        }

        // Ensure setup wizard class is loaded
        if (!class_exists('PExpress_Admin_Setup_Wizard')) {
            return;
        }

        if (PExpress_Admin_Setup_Wizard::is_setup_completed()) {
            return;
        }

        // Redirect to setup wizard
        wp_safe_redirect(admin_url('admin.php?page=polar-express-setup-wizard'));
        exit;
    }

    /**
     * Add Settings quick link in Plugins list
     */
    public function plugin_action_links($links)
    {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=polar-express-settings')) . '">' . esc_html__('Settings', 'pexpress') . '</a>';
        $docs_link = '<a href="https://github.com/atiqisrak/pexpress" target="_blank" rel="noopener">' . esc_html__('Docs', 'pexpress') . '</a>';
        array_unshift($links, $settings_link, $docs_link);
        return $links;
    }

    /**
     * Initialize plugin modules
     */
    public function init_modules()
    {
        // Initialize core
        new PExpress_Core();

        // Initialize webhook handler
        PExpress_Webhook::init();

        // Initialize heartbeat
        PExpress_Heartbeat::init();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets()
    {
        // Only enqueue on pages with shortcodes
        global $post;
        if (
            !$post || !has_shortcode($post->post_content, 'polar_hr')
            && !has_shortcode($post->post_content, 'polar_delivery')
            && !has_shortcode($post->post_content, 'polar_fridge')
            && !has_shortcode($post->post_content, 'polar_distributor')
            && !has_shortcode($post->post_content, 'polar_support')
        ) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'polar-express',
            PEXPRESS_PLUGIN_URL . 'assets/css/polar.css',
            array(),
            PEXPRESS_VERSION
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'polar-express',
            PEXPRESS_PLUGIN_URL . 'assets/js/polar.js',
            array('jquery', 'heartbeat'),
            PEXPRESS_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'polar-express',
            'polarExpress',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('polar_express_nonce'),
            )
        );
    }

    /**
     * AJAX handler for order assignment
     */
    public function ajax_assign_order()
    {
        // Prevent any output before headers
        if (ob_get_level()) {
            ob_clean();
        }

        // Verify nonce
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        if (!$order_id || !isset($_POST['polar_assign_nonce']) || !wp_verify_nonce($_POST['polar_assign_nonce'], 'polar_assign_' . $order_id)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'pexpress')));
        }

        // Check permissions
        $current_user = wp_get_current_user();
        if (!in_array('polar_hr', $current_user->roles) && !current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'pexpress')));
        }

        // Get form data
        $delivery_user_id   = isset($_POST['delivery_user_id']) ? absint($_POST['delivery_user_id']) : 0;
        $fridge_user_id     = isset($_POST['fridge_user_id']) ? absint($_POST['fridge_user_id']) : 0;
        $distributor_user_id = isset($_POST['distributor_user_id']) ? absint($_POST['distributor_user_id']) : 0;
        $meeting_type       = isset($_POST['meeting_type']) ? sanitize_text_field($_POST['meeting_type']) : 'meet_point';
        $meeting_location   = isset($_POST['meeting_location']) ? sanitize_text_field($_POST['meeting_location']) : '';
        $meeting_datetime   = isset($_POST['meeting_datetime']) ? sanitize_text_field($_POST['meeting_datetime']) : '';
        $fridge_asset_id    = isset($_POST['fridge_asset_id']) ? sanitize_text_field($_POST['fridge_asset_id']) : '';
        $delivery_note      = isset($_POST['delivery_instructions']) ? sanitize_textarea_field($_POST['delivery_instructions']) : '';
        $fridge_note        = isset($_POST['fridge_instructions']) ? sanitize_textarea_field($_POST['fridge_instructions']) : '';
        $distributor_note   = isset($_POST['distributor_instructions']) ? sanitize_textarea_field($_POST['distributor_instructions']) : '';
        $fridge_return_date = isset($_POST['fridge_return_date']) ? sanitize_text_field($_POST['fridge_return_date']) : '';
        $assignment_note   = isset($_POST['assignment_note']) ? sanitize_textarea_field($_POST['assignment_note']) : '';

        // Update order meta
        if ($delivery_user_id) {
            PExpress_Core::update_order_meta($order_id, '_polar_delivery_user_id', $delivery_user_id);
            // Send SMS notification
            polar_send_assignment_sms($order_id, 'delivery', $delivery_user_id);
        }

        if ($fridge_user_id) {
            PExpress_Core::update_order_meta($order_id, '_polar_fridge_user_id', $fridge_user_id);
            if ($fridge_return_date) {
                PExpress_Core::update_order_meta($order_id, '_polar_fridge_return_date', $fridge_return_date);
            }
            // Send SMS notification
            polar_send_assignment_sms($order_id, 'fridge', $fridge_user_id);
        }

        if ($distributor_user_id) {
            PExpress_Core::update_order_meta($order_id, '_polar_distributor_user_id', $distributor_user_id);
            // Send SMS notification
            polar_send_assignment_sms($order_id, 'distributor', $distributor_user_id);
        }

        if ($assignment_note) {
            PExpress_Core::update_order_meta($order_id, '_polar_assignment_note', $assignment_note);
        }

        // Persist meeting configuration
        PExpress_Core::update_order_meta($order_id, '_polar_meeting_type', in_array($meeting_type, array('meet_point', 'delivery_location'), true) ? $meeting_type : 'meet_point');
        PExpress_Core::update_order_meta($order_id, '_polar_meeting_location', $meeting_location);
        PExpress_Core::update_order_meta($order_id, '_polar_meeting_datetime', $meeting_datetime);

        if (!empty($fridge_asset_id)) {
            PExpress_Core::update_order_meta($order_id, '_polar_fridge_asset_id', $fridge_asset_id);
        }

        if (!empty($delivery_note)) {
            PExpress_Core::update_order_meta($order_id, '_polar_instructions_delivery', $delivery_note);
        }

        if (!empty($fridge_note)) {
            PExpress_Core::update_order_meta($order_id, '_polar_instructions_fridge', $fridge_note);
        }

        if (!empty($distributor_note)) {
            PExpress_Core::update_order_meta($order_id, '_polar_instructions_distributor', $distributor_note);
        }

        // Mark as assigned
        PExpress_Core::update_order_meta($order_id, '_polar_needs_assignment', 'no');

        // Update agency role status
        PExpress_Core::update_role_status($order_id, 'agency', 'assigned');
        PExpress_Core::add_role_status_history($order_id, 'agency', 'assigned', $assignment_note);

        // Initialize other role statuses to pending if not set
        if (!PExpress_Core::get_role_status($order_id, 'delivery')) {
            PExpress_Core::update_role_status($order_id, 'delivery', 'pending');
        }
        if (!PExpress_Core::get_role_status($order_id, 'fridge')) {
            PExpress_Core::update_role_status($order_id, 'fridge', 'pending');
        }
        if (!PExpress_Core::get_role_status($order_id, 'distributor')) {
            PExpress_Core::update_role_status($order_id, 'distributor', 'pending');
        }

        // Update order status
        $order = wc_get_order($order_id);
        if ($order) {
            $order->update_status('polar-assigned', __('Order assigned by Agency.', 'pexpress'));
        }

        wp_send_json_success(array('message' => __('Order assigned successfully.', 'pexpress')));
    }

    /**
     * AJAX handler for order status update
     */
    public function ajax_update_order_status()
    {
        // Prevent any output before headers
        if (ob_get_level()) {
            ob_clean();
        }

        // Verify nonce
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $nonce_key = 'polar_status_nonce';

        // Check which form type
        if (isset($_POST['polar_fridge_nonce'])) {
            $nonce_key = 'polar_fridge_nonce';
            $nonce_action = 'polar_fridge_status_' . $order_id;
        } elseif (isset($_POST['polar_distributor_nonce'])) {
            $nonce_key = 'polar_distributor_nonce';
            $nonce_action = 'polar_distributor_status_' . $order_id;
        } else {
            $nonce_action = 'polar_update_status_' . $order_id;
        }

        if (!$order_id || !wp_verify_nonce($_POST[$nonce_key], $nonce_action)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'pexpress')));
        }

        // Check permissions based on role
        $user = wp_get_current_user();
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'pexpress')));
        }

        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        // Validate status is not empty
        if (empty($new_status)) {
            wp_send_json_error(array('message' => __('Status is required.', 'pexpress')));
        }

        // Determine role-based mapping for per-role statuses
        $role_status_map = array(
            'polar_delivery' => array(
                'meet_point_arrived'      => 'meet_point_arrived',
                'delivery_location_arrived' => 'delivery_location_arrived',
                'service_in_progress'     => 'service_in_progress',
                'service_complete'        => 'service_complete',
                'customer_served'         => 'customer_served',
            ),
            'polar_fridge' => array(
                'fridge_drop'      => 'fridge_drop',
                'fridge_collected' => 'fridge_collected',
                'fridge_returned'  => 'fridge_returned',
            ),
            'polar_distributor' => array(
                'distributor_prep'     => 'distributor_prep',
                'out_for_delivery'     => 'out_for_delivery',
                'handoff_complete'     => 'handoff_complete',
            ),
        );

        // Map to WC status for backward compatibility
        $wc_status_map = array(
            'meet_point_arrived'      => 'wc-polar-meet-point',
            'delivery_location_arrived' => 'wc-polar-delivery-location',
            'service_in_progress'     => 'wc-polar-service-progress',
            'service_complete'        => 'wc-polar-service-complete',
            'customer_served'         => 'wc-polar-delivered',
            'fridge_drop'      => 'wc-polar-fridge-drop',
            'fridge_collected' => 'wc-polar-fridge-back',
            'fridge_returned'  => 'wc-polar-fridge-returned',
            'distributor_prep'     => 'wc-polar-distributor-prep',
            'out_for_delivery'     => 'wc-polar-out',
            'handoff_complete'     => 'wc-polar-distributor-complete',
        );

        $general_status_map = array(
            'service_wrap' => 'wc-polar-complete',
        );

        $matched_role = '';
        $role_key_for_status = '';
        foreach ($role_status_map as $role_key => $map) {
            if (in_array($role_key, $user->roles, true)) {
                $matched_role = $role_key;
                // Map role to status key
                if ($role_key === 'polar_delivery') {
                    $role_key_for_status = 'delivery';
                } elseif ($role_key === 'polar_fridge') {
                    $role_key_for_status = 'fridge';
                } elseif ($role_key === 'polar_distributor') {
                    $role_key_for_status = 'distributor';
                }
                break;
            }
        }

        $allowed_statuses = array();
        if ($matched_role && isset($role_status_map[$matched_role])) {
            $allowed_statuses = array_merge($allowed_statuses, array_keys($role_status_map[$matched_role]));
        }

        $current_user = wp_get_current_user();
        if (current_user_can('manage_woocommerce') || in_array('polar_hr', $current_user->roles)) {
            $allowed_statuses = array_merge($allowed_statuses, array_keys($general_status_map));
        }

        if (!in_array($new_status, $allowed_statuses, true)) {
            wp_send_json_error(array('message' => __('Invalid status.', 'pexpress')));
        }

        // Check if user is assigned to this order
        $is_assigned = false;
        if (in_array('polar_delivery', $user->roles, true)) {
            $is_assigned = (PExpress_Core::get_delivery_user_id($order_id) === $user->ID);
        } elseif (in_array('polar_fridge', $user->roles, true)) {
            $is_assigned = (PExpress_Core::get_fridge_user_id($order_id) === $user->ID);
        } elseif (in_array('polar_distributor', $user->roles, true)) {
            $is_assigned = (PExpress_Core::get_distributor_user_id($order_id) === $user->ID);
        }

        if (!$is_assigned) {
            wp_send_json_error(array('message' => __('You are not assigned to this order.', 'pexpress')));
        }

        // Map status to WooCommerce status for backward compatibility
        $wc_status = '';
        if (isset($wc_status_map[$new_status])) {
            $wc_status = $wc_status_map[$new_status];
        } elseif (isset($general_status_map[$new_status])) {
            $wc_status = $general_status_map[$new_status];
        }

        // Enforce sequential workflow for per-role statuses
        if ($matched_role && $role_key_for_status) {
            $sequence_map = array(
                'distributor' => array(
                    'pending',
                    'distributor_prep',
                    'out_for_delivery',
                    'handoff_complete',
                ),
                'delivery' => array(
                    'pending',
                    'meet_point_arrived',
                    'delivery_location_arrived',
                    'service_in_progress',
                    'service_complete',
                    'customer_served',
                ),
                'fridge' => array(
                    'pending',
                    'fridge_drop',
                    'fridge_collected',
                    'fridge_returned',
                ),
            );

            if (isset($sequence_map[$role_key_for_status])) {
                $current_role_status = PExpress_Core::get_role_status($order_id, $role_key_for_status);
                $sequence = $sequence_map[$role_key_for_status];
                $current_index = array_search($current_role_status, $sequence, true);
                $new_index = array_search($new_status, $sequence, true);

                if ($new_index === false) {
                    // Allow statuses outside sequence if explicitly mapped (e.g., admin overrides)
                    $current_index = false;
                }

                if ($new_index !== false && $current_index !== false && $new_index < $current_index) {
                    wp_send_json_error(array('message' => __('You cannot move backwards in the workflow.', 'pexpress')));
                }
            }
        }

        // Update per-role status
        if ($matched_role && $role_key_for_status) {
            PExpress_Core::update_role_status($order_id, $role_key_for_status, $new_status);
            $display_name = $user->display_name ?: $user->user_login ?: __('User', 'pexpress');
            PExpress_Core::add_role_status_history($order_id, $role_key_for_status, $new_status, sprintf(__('Status updated by %s.', 'pexpress'), $display_name));
        }

        // Update WC status for backward compatibility (if mapped)
        if (!empty($wc_status)) {
            $display_name = $user->display_name ?: $user->user_login ?: __('User', 'pexpress');
            $order->update_status(str_replace('wc-', '', $wc_status), sprintf(__('Status updated by %s.', 'pexpress'), $display_name));
        }

        // If all tasks complete, mark order complete for overview
        self::maybe_mark_order_complete($order);

        wp_send_json_success(array('message' => __('Status updated successfully.', 'pexpress')));
    }

    /**
     * Check role progress and mark order complete if criteria met
     *
     * @param WC_Order $order WooCommerce order.
     * @return void
     */
    private static function maybe_mark_order_complete($order)
    {
        if (!$order instanceof WC_Order) {
            return;
        }

        $order_id = $order->get_id();

        // Check per-role statuses
        $delivery_status = PExpress_Core::get_role_status($order_id, 'delivery');
        $fridge_status = PExpress_Core::get_role_status($order_id, 'fridge');
        $distributor_status = PExpress_Core::get_role_status($order_id, 'distributor');

        $delivery_done = in_array($delivery_status, array('service_complete', 'customer_served'), true);
        $fridge_progress = ('fridge_returned' === $fridge_status);
        $distributor_done = ('handoff_complete' === $distributor_status);

        // Fallback to WC status for backward compatibility
        if (!$delivery_done) {
            $delivery_user_id = PExpress_Core::get_delivery_user_id($order_id);
            if ($delivery_user_id) {
                $wc_status = $order->get_status();
                $delivery_done = in_array($wc_status, array('polar-service-complete', 'polar-delivered', 'polar-complete', 'completed'), true);
            }
        }

        if (!$fridge_progress) {
            $fridge_user_id = PExpress_Core::get_fridge_user_id($order_id);
            if ($fridge_user_id) {
                $wc_status = $order->get_status();
                $fridge_progress = in_array($wc_status, array('polar-fridge-returned', 'polar-complete', 'completed'), true);
            }
        }

        if (!$distributor_done) {
            $distributor_user_id = PExpress_Core::get_distributor_user_id($order_id);
            if ($distributor_user_id) {
                $wc_status = $order->get_status();
                $distributor_done = in_array($wc_status, array('polar-distributor-complete', 'polar-service-progress', 'polar-service-complete', 'polar-complete', 'completed'), true);
            }
        }

        if ($delivery_done && $fridge_progress && $distributor_done && 'polar-complete' !== $order->get_status()) {
            $order->update_status('polar-complete', __('All tasks completed. Marking service complete.', 'pexpress'));
        }
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Check for WooCommerce
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                esc_html__('Polar Express requires WooCommerce to be installed and active.', 'pexpress'),
                esc_html__('Plugin Activation Error', 'pexpress'),
                array('back_link' => true)
            );
        }

        // Create custom roles
        polar_create_roles();

        // Run data migration for per-role statuses
        self::migrate_to_per_role_statuses();

        // Check if setup is already completed
        $setup_completed = get_option('pexpress_setup_completed', false);
        
        // If setup not completed, set flag to redirect to wizard
        if (!$setup_completed) {
            set_transient('pexpress_redirect_to_setup', true, 30);
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Migrate existing orders to per-role status system
     *
     * @return void
     */
    public static function migrate_to_per_role_statuses()
    {
        // Get all orders that might have statuses
        $orders = wc_get_orders(array(
            'status' => 'any',
            'limit' => -1,
        ));

        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $wc_status = $order->get_status();

            // Skip if already migrated (has any per-role status)
            if (PExpress_Core::get_role_status($order_id, 'agency') !== 'pending' ||
                PExpress_Core::get_role_status($order_id, 'delivery') !== 'pending' ||
                PExpress_Core::get_role_status($order_id, 'fridge') !== 'pending' ||
                PExpress_Core::get_role_status($order_id, 'distributor') !== 'pending') {
                continue;
            }

            // Map WC status to per-role statuses based on order assignments
            $delivery_user_id = PExpress_Core::get_delivery_user_id($order_id);
            $fridge_user_id = PExpress_Core::get_fridge_user_id($order_id);
            $distributor_user_id = PExpress_Core::get_distributor_user_id($order_id);

            // Agency status
            if (in_array($wc_status, array('polar-assigned', 'processing'), true)) {
                PExpress_Core::update_role_status($order_id, 'agency', 'assigned');
            }

            // Delivery status mapping
            if ($delivery_user_id) {
                $delivery_status = 'pending';
                if (in_array($wc_status, array('polar-meet-point'), true)) {
                    $delivery_status = 'meet_point_arrived';
                } elseif (in_array($wc_status, array('polar-delivery-location'), true)) {
                    $delivery_status = 'delivery_location_arrived';
                } elseif (in_array($wc_status, array('polar-service-progress'), true)) {
                    $delivery_status = 'service_in_progress';
                } elseif (in_array($wc_status, array('polar-service-complete'), true)) {
                    $delivery_status = 'service_complete';
                } elseif (in_array($wc_status, array('polar-delivered'), true)) {
                    $delivery_status = 'customer_served';
                }
                PExpress_Core::update_role_status($order_id, 'delivery', $delivery_status);
            }

            // Fridge status mapping
            if ($fridge_user_id) {
                $fridge_status = 'pending';
                if (in_array($wc_status, array('polar-fridge-drop'), true)) {
                    $fridge_status = 'fridge_drop';
                } elseif (in_array($wc_status, array('polar-fridge-back'), true)) {
                    $fridge_status = 'fridge_collected';
                } elseif (in_array($wc_status, array('polar-fridge-returned'), true)) {
                    $fridge_status = 'fridge_returned';
                }
                PExpress_Core::update_role_status($order_id, 'fridge', $fridge_status);
            }

            // Distributor status mapping
            if ($distributor_user_id) {
                $distributor_status = 'pending';
                if (in_array($wc_status, array('polar-distributor-prep'), true)) {
                    $distributor_status = 'distributor_prep';
                } elseif (in_array($wc_status, array('polar-out'), true)) {
                    $distributor_status = 'out_for_delivery';
                } elseif (in_array($wc_status, array('polar-distributor-complete'), true)) {
                    $distributor_status = 'handoff_complete';
                }
                PExpress_Core::update_role_status($order_id, 'distributor', $distributor_status);
            }
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin uninstall cleanup
     */
    public static function uninstall()
    {
        // Remove custom roles
        polar_remove_roles();

        // Clean up options if needed
        // delete_option('polar_sms_user');
        // delete_option('polar_sms_pass');
        // delete_option('polar_sms_sid');
    }
}

/**
 * Initialize the plugin
 */
function pexpress_init()
{
    return PExpress::get_instance();
}

// Start the plugin
pexpress_init();
