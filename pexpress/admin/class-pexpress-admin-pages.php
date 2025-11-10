<?php

/**
 * Admin pages (Setup Guideline and Changelog)
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin pages handler
 */
class PExpress_Admin_Pages
{

    /**
     * Render Setup Guideline page
     */
    public function render_setup_guideline_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
        }

        $shortcodes = array(
            'polar_hr' => array(
                'name' => __('Agency Dashboard', 'pexpress'),
                'shortcode' => '[polar_hr]',
                'description' => __('Full access to assign orders and manage operations', 'pexpress'),
                'page_suggestion' => '/hr-dashboard',
            ),
            'polar_delivery' => array(
                'name' => __('Delivery Dashboard', 'pexpress'),
                'shortcode' => '[polar_delivery]',
                'description' => __('Can view and update delivery status for assigned orders', 'pexpress'),
                'page_suggestion' => '/my-deliveries',
            ),
            'polar_fridge' => array(
                'name' => __('Fridge Provider Dashboard', 'pexpress'),
                'shortcode' => '[polar_fridge]',
                'description' => __('Can view and mark fridge collection for assigned orders', 'pexpress'),
                'page_suggestion' => '/fridge-tasks',
            ),
            'polar_distributor' => array(
                'name' => __('Distributor Dashboard', 'pexpress'),
                'shortcode' => '[polar_distributor]',
                'description' => __('Can view and mark fulfillment for assigned orders', 'pexpress'),
                'page_suggestion' => '/distributor-tasks',
            ),
            'polar_support' => array(
                'name' => __('Support Dashboard', 'pexpress'),
                'shortcode' => '[polar_support]',
                'description' => __('Can view all orders and provide customer support', 'pexpress'),
                'page_suggestion' => '/support-dashboard',
            ),
        );

        $this->render_setup_guideline_html($shortcodes);
    }

    /**
     * Render Setup Guideline HTML
     */
    private function render_setup_guideline_html($shortcodes)
    {
?>
        <div class="wrap polar-setup-guideline">

            <div class="polar-setup-header">
                <h1><?php esc_html_e('Polar Express Setup Guideline', 'pexpress'); ?></h1>
                <p><?php esc_html_e('Follow these steps to set up and configure Polar Express for your team.', 'pexpress'); ?></p>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('📋 Quick Setup Checklist', 'pexpress'); ?></h2>
                <ul class="polar-checklist">
                    <li><?php esc_html_e('Assign users to Polar Express roles', 'pexpress'); ?></li>
                    <li><?php esc_html_e('Create dashboard pages with shortcodes', 'pexpress'); ?></li>
                    <li><?php esc_html_e('Configure SMS settings (optional)', 'pexpress'); ?></li>
                    <li><?php esc_html_e('Test order assignment workflow', 'pexpress'); ?></li>
                </ul>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('👥 Step 1: Assign Users to Roles', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <h3><?php esc_html_e('Role Management', 'pexpress'); ?></h3>
                    <ol>
                        <li><?php esc_html_e('Go to Polar Express → Settings', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Click "Add User to Role" button', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Select a role from the dropdown', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Select a user to assign to that role', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Click "Add User" to complete the assignment', 'pexpress'); ?></li>
                    </ol>
                    <div class="polar-info-box">
                        <strong><?php esc_html_e('Available Roles:', 'pexpress'); ?></strong>
                        <ul style="margin-top: 10px;">
                            <li><strong><?php esc_html_e('Polar HR:', 'pexpress'); ?></strong> <?php esc_html_e('Full access to assign orders and manage operations', 'pexpress'); ?></li>
                            <li><strong><?php esc_html_e('Polar Delivery:', 'pexpress'); ?></strong> <?php esc_html_e('Can view and update delivery status for assigned orders', 'pexpress'); ?></li>
                            <li><strong><?php esc_html_e('Polar Fridge Provider:', 'pexpress'); ?></strong> <?php esc_html_e('Can view and mark fridge collection for assigned orders', 'pexpress'); ?></li>
                            <li><strong><?php esc_html_e('Polar Distributor:', 'pexpress'); ?></strong> <?php esc_html_e('Can view and mark fulfillment for assigned orders', 'pexpress'); ?></li>
                            <li><strong><?php esc_html_e('Polar Support:', 'pexpress'); ?></strong> <?php esc_html_e('Can view all orders and provide customer support', 'pexpress'); ?></li>
                        </ul>
                    </div>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-settings')); ?>" class="button button-primary">
                            <?php esc_html_e('Go to Settings →', 'pexpress'); ?>
                        </a>
                    </p>
                </div>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('📄 Step 2: Create Dashboard Pages', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <h3><?php esc_html_e('Create Pages for Each Dashboard', 'pexpress'); ?></h3>
                    <ol>
                        <li><?php esc_html_e('Go to Pages → Add New', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Create a new page for each dashboard type', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Paste the corresponding shortcode in the page content', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Publish the page', 'pexpress'); ?></li>
                    </ol>
                    <div class="polar-warning-box">
                        <strong><?php esc_html_e('Important:', 'pexpress'); ?></strong> <?php esc_html_e('Each user will only see their own dashboard based on their assigned role. Make sure users are logged in when accessing these pages.', 'pexpress'); ?>
                    </div>
                </div>

                <h3 style="margin-top: 30px;"><?php esc_html_e('Available Shortcodes', 'pexpress'); ?></h3>
                <?php foreach ($shortcodes as $key => $shortcode) : ?>
                    <div class="polar-shortcode-card">
                        <h4><?php echo esc_html($shortcode['name']); ?></h4>
                        <p><?php echo esc_html($shortcode['description']); ?></p>
                        <div class="polar-shortcode-code">
                            <?php echo esc_html($shortcode['shortcode']); ?>
                        </div>
                        <button class="polar-copy-btn" data-shortcode="<?php echo esc_attr($shortcode['shortcode']); ?>">
                            <?php esc_html_e('Copy Shortcode', 'pexpress'); ?>
                        </button>
                        <p style="margin-top: 10px; color: #646970; font-size: 13px;">
                            <strong><?php esc_html_e('Suggested Page Slug:', 'pexpress'); ?></strong>
                            <code><?php echo esc_html($shortcode['page_suggestion']); ?></code>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('⚙️ Step 3: Configure SMS Settings (Optional)', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <h3><?php esc_html_e('SMS Notifications', 'pexpress'); ?></h3>
                    <p><?php esc_html_e('Configure SMS notifications to automatically notify team members when orders are assigned to them.', 'pexpress'); ?></p>
                    <ol>
                        <li><?php esc_html_e('Go to Polar Express → Settings', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Scroll to "SMS Configuration" section', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Enable SMS notifications', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Enter your SSLCommerz SMS API credentials', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Save settings', 'pexpress'); ?></li>
                    </ol>
                    <div class="polar-info-box">
                        <strong><?php esc_html_e('Note:', 'pexpress'); ?></strong> <?php esc_html_e('SMS notifications are optional. The plugin will work without SMS configuration, but team members won\'t receive automatic notifications.', 'pexpress'); ?>
                    </div>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-settings')); ?>" class="button button-primary">
                            <?php esc_html_e('Go to Settings →', 'pexpress'); ?>
                        </a>
                    </p>
                </div>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('🔄 Step 4: Workflow Overview', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <h3><?php esc_html_e('Order Processing Flow', 'pexpress'); ?></h3>
                    <ol>
                        <li><strong><?php esc_html_e('Order Received:', 'pexpress'); ?></strong> <?php esc_html_e('New orders appear in HR Dashboard for assignment', 'pexpress'); ?></li>
                        <li><strong><?php esc_html_e('HR Assignment:', 'pexpress'); ?></strong> <?php esc_html_e('HR assigns orders to Delivery, Fridge Provider, and Distributor', 'pexpress'); ?></li>
                        <li><strong><?php esc_html_e('Distributor Fulfills:', 'pexpress'); ?></strong> <?php esc_html_e('Distributor marks orders as fulfilled', 'pexpress'); ?></li>
                        <li><strong><?php esc_html_e('Fridge Collection:', 'pexpress'); ?></strong> <?php esc_html_e('Fridge Provider collects and later returns the fridge', 'pexpress'); ?></li>
                        <li><strong><?php esc_html_e('Delivery:', 'pexpress'); ?></strong> <?php esc_html_e('Delivery person marks orders as out for delivery and then delivered', 'pexpress'); ?></li>
                        <li><strong><?php esc_html_e('Support:', 'pexpress'); ?></strong> <?php esc_html_e('Support team can view all orders to assist customers', 'pexpress'); ?></li>
                    </ol>
                </div>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('💡 Tips & Best Practices', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <ul>
                        <li><?php esc_html_e('Create separate pages for each dashboard type for better organization', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Use descriptive page slugs (e.g., /my-deliveries, /fridge-tasks)', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Ensure all team members have WordPress user accounts before assigning roles', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Test the workflow with a test order before going live', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Keep SMS credentials secure and never share them publicly', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Regularly check the HR Dashboard for pending assignments', 'pexpress'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="polar-setup-section">
                <h2><?php esc_html_e('❓ Need Help?', 'pexpress'); ?></h2>
                <div class="polar-setup-step">
                    <p><?php esc_html_e('If you encounter any issues or need assistance:', 'pexpress'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Check the plugin documentation', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Review the error messages in WordPress admin notices', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Ensure WooCommerce is installed and active', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Verify user roles are correctly assigned', 'pexpress'); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.polar-copy-btn').on('click', function() {
                    var $btn = $(this);
                    var shortcode = $btn.data('shortcode');
                    var $temp = $('<textarea>');
                    $('body').append($temp);
                    $temp.val(shortcode).select();
                    document.execCommand('copy');
                    $temp.remove();

                    var originalText = $btn.text();
                    $btn.text('<?php echo esc_js(__('Copied!', 'pexpress')); ?>').addClass('copied');
                    setTimeout(function() {
                        $btn.text(originalText).removeClass('copied');
                    }, 2000);
                });
            });
        </script>
    <?php
    }

    /**
     * Render Changelog page
     */
    public function render_changelog_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
        }

        $changelog = array(
            '1.0.4' => array(
                'date' => '2024-12-19',
                'added' => array(
                    __('Role-based user management system with role table view', 'pexpress'),
                    __('Multi-select user assignment modal with intelligent filtering', 'pexpress'),
                    __('Setup Guideline page with comprehensive documentation', 'pexpress'),
                    __('Enhanced shortcodes for all dashboard types with proper data preparation', 'pexpress'),
                    __('User role filtering to prevent duplicate role assignments', 'pexpress'),
                    __('Support for assigning multiple roles to users', 'pexpress'),
                    __('Improved modal UI with full-width selection boxes', 'pexpress'),
                    __('Inline user removal with visual feedback', 'pexpress'),
                    __('Changelog page for version tracking', 'pexpress'),
                ),
                'improved' => array(
                    __('Settings page UI with role-centric design', 'pexpress'),
                    __('User assignment workflow with better UX', 'pexpress'),
                    __('Modal responsiveness and usability', 'pexpress'),
                    __('Table display showing all user roles at a glance', 'pexpress'),
                ),
                'fixed' => array(
                    __('Shortcode data preparation for frontend pages', 'pexpress'),
                    __('Role assignment validation and error handling', 'pexpress'),
                ),
            ),
            '1.0.3' => array(
                'date' => '2024-12-15',
                'added' => array(
                    __('Real-time order updates via WordPress Heartbeat API', 'pexpress'),
                    __('SMS notification system integration with SSLCommerz', 'pexpress'),
                    __('Custom order statuses for Polar Express workflow', 'pexpress'),
                    __('AJAX-powered order assignment system', 'pexpress'),
                ),
                'improved' => array(
                    __('Dashboard performance and loading times', 'pexpress'),
                    __('Order status update mechanisms', 'pexpress'),
                ),
            ),
            '1.0.2' => array(
                'date' => '2024-12-10',
                'added' => array(
                    __('Support dashboard for customer service team', 'pexpress'),
                    __('Distributor dashboard for order fulfillment tracking', 'pexpress'),
                    __('Fridge provider dashboard for rental management', 'pexpress'),
                    __('Delivery dashboard for delivery personnel', 'pexpress'),
                ),
                'improved' => array(
                    __('Dashboard templates with mobile-responsive design', 'pexpress'),
                    __('Order filtering and status management', 'pexpress'),
                ),
            ),
            '1.0.1' => array(
                'date' => '2024-12-05',
                'added' => array(
                    __('HR dashboard for order assignment', 'pexpress'),
                    __('Role-based access control system', 'pexpress'),
                    __('WooCommerce order integration', 'pexpress'),
                ),
                'improved' => array(
                    __('Plugin initialization and dependency management', 'pexpress'),
                    __('Security with nonce verification and capability checks', 'pexpress'),
                ),
            ),
            '1.0.0' => array(
                'date' => '2024-12-01',
                'added' => array(
                    __('Initial release of Polar Express plugin', 'pexpress'),
                    __('Custom WordPress roles: Polar HR, Polar Delivery, Polar Fridge Provider, Polar Distributor, Polar Support', 'pexpress'),
                    __('WooCommerce integration for order management', 'pexpress'),
                    __('Plugin architecture and core functionality', 'pexpress'),
                    __('Admin interface foundation', 'pexpress'),
                ),
            ),
        );

        $this->render_changelog_html($changelog);
    }

    /**
     * Render Changelog HTML
     */
    private function render_changelog_html($changelog)
    {
    ?>
        <div class="wrap polar-changelog">
            <div class="polar-changelog-header">
                <h1><?php esc_html_e('Polar Express Changelog', 'pexpress'); ?></h1>
                <p><?php esc_html_e('Complete version history and release notes for Polar Express plugin', 'pexpress'); ?></p>
            </div>

            <?php foreach ($changelog as $version => $details) : ?>
                <div class="polar-version-section">
                    <div class="polar-version-header">
                        <h2>
                            <?php echo esc_html($version); ?>
                            <?php if ($version === '1.0.4') : ?>
                                <span class="polar-badge latest"><?php esc_html_e('Latest', 'pexpress'); ?></span>
                            <?php endif; ?>
                        </h2>
                        <span class="polar-version-date"><?php echo esc_html($details['date']); ?></span>
                    </div>

                    <?php if (!empty($details['added'])) : ?>
                        <div class="polar-changelog-category added">
                            <h3>
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php esc_html_e('Added', 'pexpress'); ?>
                            </h3>
                            <ul>
                                <?php foreach ($details['added'] as $item) : ?>
                                    <li><?php echo esc_html($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($details['improved'])) : ?>
                        <div class="polar-changelog-category improved">
                            <h3>
                                <span class="dashicons dashicons-arrow-up-alt"></span>
                                <?php esc_html_e('Improved', 'pexpress'); ?>
                            </h3>
                            <ul>
                                <?php foreach ($details['improved'] as $item) : ?>
                                    <li><?php echo esc_html($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($details['fixed'])) : ?>
                        <div class="polar-changelog-category fixed">
                            <h3>
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('Fixed', 'pexpress'); ?>
                            </h3>
                            <ul>
                                <?php foreach ($details['fixed'] as $item) : ?>
                                    <li><?php echo esc_html($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="polar-version-section" style="background: #f6f7f7; border-left: 4px solid #2271b1;">
                <h3 style="margin-top: 0; color: #1d2327;"><?php esc_html_e('About Polar Express', 'pexpress'); ?></h3>
                <p style="color: #646970; line-height: 1.6;">
                    <?php esc_html_e('Polar Express is a custom WordPress extension designed to enhance manual order processing and delivery workflows for Polar\'s bulk ice cream service. The plugin provides role-based dashboards, real-time task assignments, order management, and tracking capabilities for all stakeholders involved in the order fulfillment process.', 'pexpress'); ?>
                </p>
                <p style="color: #646970; line-height: 1.6; margin-top: 15px;">
                    <strong><?php esc_html_e('Plugin Version:', 'pexpress'); ?></strong> <?php echo esc_html(PEXPRESS_VERSION); ?><br>
                    <strong><?php esc_html_e('WooCommerce Compatibility:', 'pexpress'); ?></strong> 8.0+<br>
                    <strong><?php esc_html_e('WordPress Compatibility:', 'pexpress'); ?></strong> 6.0+<br>
                    <strong><?php esc_html_e('PHP Requirement:', 'pexpress'); ?></strong> 7.4+
                </p>
            </div>
        </div>
<?php
    }
}
