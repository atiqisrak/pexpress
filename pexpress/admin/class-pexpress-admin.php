<?php

/**
 * Admin functionality - Main orchestrator
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class - Main orchestrator
 */
class PExpress_Admin
{

    /**
     * Admin modules
     *
     * @var array
     */
    private $modules = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->load_modules();
        $this->init();
    }

    /**
     * Load admin modules
     */
    private function load_modules()
    {
        require_once PEXPRESS_PLUGIN_DIR . 'admin/class-pexpress-admin-menus.php';
        require_once PEXPRESS_PLUGIN_DIR . 'admin/class-pexpress-admin-settings.php';
        require_once PEXPRESS_PLUGIN_DIR . 'admin/class-pexpress-admin-dashboards.php';
        require_once PEXPRESS_PLUGIN_DIR . 'admin/class-pexpress-admin-roles.php';
        require_once PEXPRESS_PLUGIN_DIR . 'admin/class-pexpress-admin-pages.php';
        require_once PEXPRESS_PLUGIN_DIR . 'admin/class-pexpress-admin-order-manipulation.php';

        $this->modules['menus'] = new PExpress_Admin_Menus();
        $this->modules['settings'] = new PExpress_Admin_Settings();
        $this->modules['dashboards'] = new PExpress_Admin_Dashboards();
        $this->modules['roles'] = new PExpress_Admin_Roles();
        $this->modules['pages'] = new PExpress_Admin_Pages();
        $this->modules['order_manipulation'] = new PExpress_Admin_Order_Manipulation();
    }

    /**
     * Initialize admin functionality
     */
    private function init()
    {
        add_action('admin_menu', array($this->modules['menus'], 'register_menus'));
        add_action('admin_init', array($this->modules['settings'], 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_post_pexpress_assign_role', array($this->modules['roles'], 'handle_role_assignment'));
        add_action('admin_post_pexpress_add_user_to_role', array($this->modules['roles'], 'handle_add_user_to_role'));
        add_action('admin_post_pexpress_remove_user_from_role', array($this->modules['roles'], 'handle_remove_user_from_role'));
        add_action('admin_notices', array($this->modules['roles'], 'show_role_assignment_notices'));
        add_action('wp_ajax_pexpress_get_users_for_role', array($this->modules['roles'], 'ajax_get_users_for_role'));

        // Filter admin page title to show correct title
        add_filter('admin_title', array($this, 'filter_admin_page_title'), 10, 2);
    }

    /**
     * Filter admin page title to show correct dashboard name
     */
    public function filter_admin_page_title($admin_title, $title)
    {
        $screen = get_current_screen();
        if (!$screen) {
            return $admin_title;
        }

        // Only filter Polar Express pages
        if (strpos($screen->id, 'polar-express') === false) {
            return $admin_title;
        }

        $current_user = wp_get_current_user();
        $page = isset($_GET['page']) ? $_GET['page'] : '';

        // Set correct title based on page
        if ($page === 'polar-express') {
            if (in_array('polar_hr', $current_user->roles) || current_user_can('manage_woocommerce')) {
                return __('Agency Dashboard', 'pexpress') . $title;
            } elseif (in_array('polar_delivery', $current_user->roles)) {
                return __('HR Dashboard', 'pexpress') . $title;
            } elseif (in_array('polar_support', $current_user->roles)) {
                return __('Support Portal', 'pexpress') . $title;
            }
        } elseif ($page === 'polar-express-delivery') {
            return __('HR Dashboard', 'pexpress') . $title;
        } elseif ($page === 'polar-express-support') {
            return __('Support Portal', 'pexpress') . $title;
        }

        return $admin_title;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on Polar Express pages
        if (strpos($hook, 'polar-express') === false) {
            return;
        }

        // Enqueue modern admin styles for all admin pages
        wp_enqueue_style(
            'pexpress-admin-modern',
            PEXPRESS_PLUGIN_URL . 'assets/css/polar-admin-modern.css',
            array(),
            PEXPRESS_VERSION
        );

        // Enqueue original styles for frontend dashboards (if needed)
        wp_enqueue_style(
            'pexpress-admin',
            PEXPRESS_PLUGIN_URL . 'assets/css/polar.css',
            array(),
            PEXPRESS_VERSION
        );

        // Enqueue setup wizard script if on setup wizard page
        if (strpos($hook, 'polar-express-setup-wizard') !== false) {
            wp_enqueue_script(
                'pexpress-admin-setup',
                PEXPRESS_PLUGIN_URL . 'assets/js/polar-admin-setup.js',
                array('jquery'),
                PEXPRESS_VERSION,
                true
            );
        }

        wp_enqueue_script(
            'pexpress-admin',
            PEXPRESS_PLUGIN_URL . 'assets/js/polar.js',
            array('jquery', 'heartbeat'),
            PEXPRESS_VERSION,
            true
        );

        wp_localize_script(
            'pexpress-admin',
            'polarExpress',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('polar_express_nonce'),
                'heartbeatInterval' => get_option('pexpress_options')['heartbeat_interval'] ?? 15
            )
        );
    }
}
