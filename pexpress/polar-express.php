<?php

/**
 * Plugin Name: Polar Express
 * Plugin URI: https://github.com/atiqisrak/pexpress
 * Description: Custom WordPress extension designed to enhance manual order processing and delivery workflows for Polar's bulk ice cream service.
 * Version: 1.0.0
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
define('PEXPRESS_VERSION', '1.0.0');
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
        // Verify nonce
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        if (!$order_id || !wp_verify_nonce($_POST['polar_assign_nonce'], 'polar_assign_' . $order_id)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'pexpress')));
        }

        // Check permissions
        if (!current_user_can('polar_hr')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'pexpress')));
        }

        // Get form data
        $delivery_user_id   = isset($_POST['delivery_user_id']) ? absint($_POST['delivery_user_id']) : 0;
        $fridge_user_id     = isset($_POST['fridge_user_id']) ? absint($_POST['fridge_user_id']) : 0;
        $distributor_user_id = isset($_POST['distributor_user_id']) ? absint($_POST['distributor_user_id']) : 0;
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

        // Mark as assigned
        PExpress_Core::update_order_meta($order_id, '_polar_needs_assignment', 'no');

        // Update order status
        $order = wc_get_order($order_id);
        if ($order) {
            $order->update_status('polar-assigned', __('Order assigned by HR.', 'pexpress'));
        }

        wp_send_json_success(array('message' => __('Order assigned successfully.', 'pexpress')));
    }

    /**
     * AJAX handler for order status update
     */
    public function ajax_update_order_status()
    {
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

        // Validate status and permissions
        $allowed_statuses = array('polar-out', 'polar-delivered', 'fridge-collected', 'fulfilled');
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

        // Update status
        $status_map = array(
            'polar-out'        => 'wc-polar-out',
            'polar-delivered' => 'wc-polar-delivered',
            'fridge-collected' => 'wc-polar-fridge-back',
            'fulfilled'       => 'completed',
        );

        $wc_status = isset($status_map[$new_status]) ? $status_map[$new_status] : $new_status;
        $order->update_status($wc_status, sprintf(__('Status updated by %s.', 'pexpress'), $user->display_name));

        wp_send_json_success(array('message' => __('Status updated successfully.', 'pexpress')));
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

        // Flush rewrite rules
        flush_rewrite_rules();
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
