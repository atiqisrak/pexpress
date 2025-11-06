<?php
/**
 * Plugin Name: Polar Express
 * Plugin URI: https://github.com/atiqisrak/pexpress
 * Description: Custom WordPress extension designed to enhance manual order processing and delivery workflows for Polar's bulk ice cream service.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: pexpress
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 8.9
 */

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
class PExpress {
    
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
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize plugin
     */
    private function init() {
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
    public function load_textdomain() {
        load_plugin_textdomain(
            'pexpress',
            false,
            dirname(PEXPRESS_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Check for required dependencies
     */
    public function check_dependencies() {
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
    private function load_dependencies() {
        // Load core files
        require_once PEXPRESS_PLUGIN_DIR . 'includes/class-pexpress-core.php';

        // Load admin files if in admin
        if (is_admin()) {
            require_once PEXPRESS_PLUGIN_DIR . 'admin/class-pexpress-admin.php';
        }

        // Load public files
        require_once PEXPRESS_PLUGIN_DIR . 'public/class-pexpress-public.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Check for WooCommerce
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                esc_html__('Polar Express requires WooCommerce to be installed and active.', 'pexpress'),
                esc_html__('Plugin Activation Error', 'pexpress'),
                array('back_link' => true)
            );
        }

        // Create database tables if needed
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 */
function pexpress_init() {
    return PExpress::get_instance();
}

// Start the plugin
pexpress_init();

