<?php

/**
 * Admin Setup Wizard
 *
 * @package PExpress
 * @since 1.0.5
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin setup wizard handler
 */
class PExpress_Admin_Setup_Wizard
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_post_pexpress_complete_setup', array($this, 'handle_setup_completion'));
        add_action('admin_post_pexpress_skip_setup', array($this, 'handle_setup_skip'));
    }

    /**
     * Check if setup is completed
     *
     * @return bool
     */
    public static function is_setup_completed()
    {
        return (bool) get_option('pexpress_setup_completed', false);
    }

    /**
     * Mark setup as completed
     */
    public static function mark_setup_completed()
    {
        update_option('pexpress_setup_completed', true);
    }

    /**
     * Reset setup status (for testing)
     */
    public static function reset_setup()
    {
        delete_option('pexpress_setup_completed');
    }

    /**
     * Handle setup completion
     */
    public function handle_setup_completion()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to perform this action.', 'pexpress'));
        }

        if (!isset($_POST['pexpress_setup_nonce']) || !wp_verify_nonce($_POST['pexpress_setup_nonce'], 'pexpress_complete_setup')) {
            wp_die(__('Security check failed.', 'pexpress'));
        }

        self::mark_setup_completed();
        wp_redirect(admin_url('admin.php?page=polar-express-support&setup_completed=1'));
        exit;
    }

    /**
     * Handle setup skip
     */
    public function handle_setup_skip()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to perform this action.', 'pexpress'));
        }

        if (!isset($_POST['pexpress_setup_nonce']) || !wp_verify_nonce($_POST['pexpress_setup_nonce'], 'pexpress_skip_setup')) {
            wp_die(__('Security check failed.', 'pexpress'));
        }

        self::mark_setup_completed();
        wp_redirect(admin_url('admin.php?page=polar-express&setup_skipped=1'));
        exit;
    }

    /**
     * Render setup wizard page
     */
    public function render_setup_wizard()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
        }

        $current_step = isset($_GET['step']) ? absint($_GET['step']) : 1;
        $total_steps = 5;

        // Get role data for step 2
        $polar_roles = array(
            'polar_hr' => __('Polar Agency', 'pexpress'),
            'polar_delivery' => __('Polar HR', 'pexpress'),
            'polar_fridge' => __('Polar Fridge Provider', 'pexpress'),
            'polar_distributor' => __('Polar Distributor', 'pexpress'),
            'polar_support' => __('Polar Support', 'pexpress'),
        );

        $role_users = array();
        foreach ($polar_roles as $role_key => $role_name) {
            $role_users[$role_key] = get_users(array('role' => $role_key));
        }

        $all_users = get_users(array('number' => -1, 'orderby' => 'display_name'));

        // Shortcodes for step 3
        $shortcodes = array(
            'polar_hr' => array(
                'name' => __('Agency Dashboard', 'pexpress'),
                'shortcode' => '[polar_hr]',
                'description' => __('Full access to assign orders and manage operations', 'pexpress'),
                'page_suggestion' => '/hr-dashboard',
            ),
            'polar_delivery' => array(
                'name' => __('HR Dashboard', 'pexpress'),
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

        // SMS settings for step 4
        $options = get_option('pexpress_options', array());
        $sms_enabled = !empty($options['enable_sms']);

        // Instantiate roles class for step 2
        if (!class_exists('PExpress_Admin_Roles')) {
            require_once PEXPRESS_PLUGIN_DIR . 'admin/class-pexpress-admin-roles.php';
        }
        $roles = new PExpress_Admin_Roles();

        $this->render_wizard_html($current_step, $total_steps, $polar_roles, $role_users, $all_users, $shortcodes, $sms_enabled, $roles);
    }

    /**
     * Render wizard HTML
     */
    private function render_wizard_html($current_step, $total_steps, $polar_roles, $role_users, $all_users, $shortcodes, $sms_enabled, $roles = null)
    {
        // Ensure roles class is instantiated
        if (null === $roles) {
            if (!class_exists('PExpress_Admin_Roles')) {
                require_once PEXPRESS_PLUGIN_DIR . 'admin/class-pexpress-admin-roles.php';
            }
            $roles = new PExpress_Admin_Roles();
        }
?>
        <div class="wrap pexpress-setup-wizard">
            <div class="pexpress-wizard-container">
                <!-- Progress Bar -->
                <div class="pexpress-wizard-progress">
                    <?php for ($i = 1; $i <= $total_steps; $i++) : ?>
                        <div class="pexpress-progress-step <?php echo $i <= $current_step ? 'active' : ''; ?> <?php echo $i < $current_step ? 'completed' : ''; ?>">
                            <div class="pexpress-progress-circle">
                                <?php if ($i < $current_step) : ?>
                                    <span class="dashicons dashicons-yes"></span>
                                <?php else : ?>
                                    <span class="pexpress-step-number"><?php echo esc_html($i); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="pexpress-progress-label">
                                <?php
                                $labels = array(
                                    1 => __('Welcome', 'pexpress'),
                                    2 => __('Roles', 'pexpress'),
                                    3 => __('Dashboards', 'pexpress'),
                                    4 => __('SMS', 'pexpress'),
                                    5 => __('Complete', 'pexpress'),
                                );
                                echo esc_html($labels[$i] ?? '');
                                ?>
                            </div>
                        </div>
                        <?php if ($i < $total_steps) : ?>
                            <div class="pexpress-progress-line <?php echo $i < $current_step ? 'completed' : ''; ?>"></div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <!-- Wizard Content -->
                <div class="pexpress-wizard-content">
                    <?php
                    switch ($current_step) {
                        case 1:
                            $this->render_step_welcome();
                            break;
                        case 2:
                            $this->render_step_roles($polar_roles, $role_users, $all_users, $roles);
                            break;
                        case 3:
                            $this->render_step_dashboards($shortcodes);
                            break;
                        case 4:
                            $this->render_step_sms($sms_enabled);
                            break;
                        case 5:
                            $this->render_step_complete();
                            break;
                    }
                    ?>
                </div>

                <!-- Navigation -->
                <div class="pexpress-wizard-navigation">
                    <?php if ($current_step > 1) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-setup-wizard&step=' . ($current_step - 1))); ?>" class="pexpress-btn pexpress-btn-secondary">
                            <?php esc_html_e('← Previous', 'pexpress'); ?>
                        </a>
                    <?php else : ?>
                        <span></span>
                    <?php endif; ?>

                    <div class="pexpress-wizard-actions">
                        <?php if ($current_step < $total_steps) : ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-setup-wizard&step=' . ($current_step + 1))); ?>" class="pexpress-btn pexpress-btn-primary">
                                <?php esc_html_e('Next →', 'pexpress'); ?>
                            </a>
                        <?php else : ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline;">
                                <?php wp_nonce_field('pexpress_complete_setup', 'pexpress_setup_nonce'); ?>
                                <input type="hidden" name="action" value="pexpress_complete_setup">
                                <button type="submit" class="pexpress-btn pexpress-btn-primary pexpress-btn-large">
                                    <?php esc_html_e('Complete Setup', 'pexpress'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if ($current_step < $total_steps) : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline;">
                            <?php wp_nonce_field('pexpress_skip_setup', 'pexpress_setup_nonce'); ?>
                            <input type="hidden" name="action" value="pexpress_skip_setup">
                            <button type="submit" class="pexpress-btn pexpress-btn-link">
                                <?php esc_html_e('Skip Setup', 'pexpress'); ?>
                            </button>
                        </form>
                    <?php else : ?>
                        <span></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Render Step 1: Welcome
     */
    private function render_step_welcome()
    {
    ?>
        <div class="pexpress-wizard-step">
            <div class="pexpress-wizard-header">
                <h1><?php esc_html_e('Welcome to Polar Express', 'pexpress'); ?></h1>
                <p class="pexpress-wizard-subtitle"><?php esc_html_e('Let\'s get your plugin configured in just a few steps', 'pexpress'); ?></p>
            </div>

            <div class="pexpress-wizard-body">
                <div class="pexpress-feature-grid">
                    <div class="pexpress-feature-card">
                        <div class="pexpress-feature-icon">👥</div>
                        <h3><?php esc_html_e('Role Management', 'pexpress'); ?></h3>
                        <p><?php esc_html_e('Assign users to specific roles for streamlined workflow management', 'pexpress'); ?></p>
                    </div>
                    <div class="pexpress-feature-card">
                        <div class="pexpress-feature-icon">📊</div>
                        <h3><?php esc_html_e('Dashboard Pages', 'pexpress'); ?></h3>
                        <p><?php esc_html_e('Create custom dashboards for each role with simple shortcodes', 'pexpress'); ?></p>
                    </div>
                    <div class="pexpress-feature-card">
                        <div class="pexpress-feature-icon">📱</div>
                        <h3><?php esc_html_e('SMS Notifications', 'pexpress'); ?></h3>
                        <p><?php esc_html_e('Optional SMS alerts to keep your team informed in real-time', 'pexpress'); ?></p>
                    </div>
                </div>

                <div class="pexpress-info-box">
                    <h3><?php esc_html_e('What you\'ll set up:', 'pexpress'); ?></h3>
                    <ul>
                        <li><?php esc_html_e('Assign team members to Polar Express roles', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Create dashboard pages with shortcodes', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Configure SMS notifications (optional)', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Review and complete setup', 'pexpress'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Render Step 2: Roles
     */
    private function render_step_roles($polar_roles, $role_users, $all_users, $roles)
    {
    ?>
        <div class="pexpress-wizard-step">
            <div class="pexpress-wizard-header">
                <h1><?php esc_html_e('Assign Users to Roles', 'pexpress'); ?></h1>
                <p class="pexpress-wizard-subtitle"><?php esc_html_e('Assign team members to their respective Polar Express roles', 'pexpress'); ?></p>
            </div>

            <div class="pexpress-wizard-body">
                <div class="pexpress-role-assignment">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <p><?php esc_html_e('Click "Add User to Role" to assign users. You can also manage roles later from Settings.', 'pexpress'); ?></p>
                        <button type="button" id="pexpress-wizard-add-user-btn" class="pexpress-btn pexpress-btn-primary">
                            <?php esc_html_e('+ Add User to Role', 'pexpress'); ?>
                        </button>
                    </div>

                    <div class="pexpress-roles-grid">
                        <?php foreach ($polar_roles as $role_key => $role_name) : ?>
                            <?php
                            $users = $role_users[$role_key];
                            $descriptions = array(
                                'polar_hr' => __('Full access to assign orders and manage operations (Agency)', 'pexpress'),
                                'polar_delivery' => __('Can view and update delivery status for assigned orders (HR)', 'pexpress'),
                                'polar_fridge' => __('Can view and mark fridge collection for assigned orders', 'pexpress'),
                                'polar_distributor' => __('Can view and mark fulfillment for assigned orders', 'pexpress'),
                                'polar_support' => __('Can view all orders and provide customer support', 'pexpress'),
                            );
                            ?>
                            <div class="pexpress-role-card">
                                <h3><?php echo esc_html($role_name); ?></h3>
                                <p class="pexpress-role-description"><?php echo esc_html($descriptions[$role_key] ?? ''); ?></p>
                                <div class="pexpress-role-users">
                                    <?php if (!empty($users)) : ?>
                                        <?php foreach ($users as $user) : ?>
                                            <div class="pexpress-user-badge">
                                                <strong><?php echo esc_html($user->display_name); ?></strong>
                                                <span><?php echo esc_html($user->user_email); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                        <p class="pexpress-user-count"><?php echo sprintf(esc_html__('%d user(s)', 'pexpress'), count($users)); ?></p>
                                    <?php else : ?>
                                        <p class="pexpress-no-users"><?php esc_html_e('No users assigned', 'pexpress'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pexpress-info-box">
                    <p><strong><?php esc_html_e('Tip:', 'pexpress'); ?></strong> <?php esc_html_e('You can always add or remove users from roles later in Settings → Role Management.', 'pexpress'); ?></p>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <?php $roles->render_add_user_modal($polar_roles, $all_users); ?>
        <?php $roles->render_settings_scripts(); ?>
        <script>
            jQuery(document).ready(function($) {
                // Open modal directly when wizard button is clicked
                $('#pexpress-wizard-add-user-btn').on('click', function() {
                    $('#polar-add-user-modal').css('display', 'flex');
                    // Reset form
                    $('#polar-add-user-form')[0].reset();
                    $('#polar-selected-count').text('<?php echo esc_js(__('0 users selected', 'pexpress')); ?>');
                    $('#polar_add_user').prop('disabled', true);
                    $('#polar-user-help-text').text('<?php echo esc_js(__('Please select a role first to see available users. Users already assigned to the selected role will be hidden.', 'pexpress')); ?>');
                });
            });
        </script>
    <?php
    }

    /**
     * Render Step 3: Dashboards
     */
    private function render_step_dashboards($shortcodes)
    {
    ?>
        <div class="pexpress-wizard-step">
            <div class="pexpress-wizard-header">
                <h1><?php esc_html_e('Create Dashboard Pages', 'pexpress'); ?></h1>
                <p class="pexpress-wizard-subtitle"><?php esc_html_e('Create pages for each dashboard type using shortcodes', 'pexpress'); ?></p>
            </div>

            <div class="pexpress-wizard-body">
                <div class="pexpress-dashboard-instructions">
                    <ol class="pexpress-instructions-list">
                        <li><?php esc_html_e('Go to Pages → Add New in WordPress admin', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Create a new page for each dashboard type', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Paste the corresponding shortcode in the page content', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Publish the page', 'pexpress'); ?></li>
                    </ol>
                </div>

                <div class="pexpress-shortcodes-grid">
                    <?php foreach ($shortcodes as $key => $shortcode) : ?>
                        <div class="pexpress-shortcode-card">
                            <h4><?php echo esc_html($shortcode['name']); ?></h4>
                            <p><?php echo esc_html($shortcode['description']); ?></p>
                            <div class="pexpress-shortcode-box">
                                <code><?php echo esc_html($shortcode['shortcode']); ?></code>
                                <button class="pexpress-copy-btn" data-shortcode="<?php echo esc_attr($shortcode['shortcode']); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p class="pexpress-page-suggestion">
                                <strong><?php esc_html_e('Suggested slug:', 'pexpress'); ?></strong>
                                <code><?php echo esc_html($shortcode['page_suggestion']); ?></code>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="pexpress-info-box">
                    <p><strong><?php esc_html_e('Note:', 'pexpress'); ?></strong> <?php esc_html_e('Each user will only see their own dashboard based on their assigned role. Make sure users are logged in when accessing these pages.', 'pexpress'); ?></p>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('.pexpress-copy-btn').on('click', function() {
                    var $btn = $(this);
                    var shortcode = $btn.data('shortcode');
                    var $temp = $('<textarea>');
                    $('body').append($temp);
                    $temp.val(shortcode).select();
                    document.execCommand('copy');
                    $temp.remove();

                    $btn.html('<span class="dashicons dashicons-yes"></span>').addClass('copied');
                    setTimeout(function() {
                        $btn.html('<span class="dashicons dashicons-clipboard"></span>').removeClass('copied');
                    }, 2000);
                });
            });
        </script>
    <?php
    }

    /**
     * Render Step 4: SMS
     */
    private function render_step_sms($sms_enabled)
    {
    ?>
        <div class="pexpress-wizard-step">
            <div class="pexpress-wizard-header">
                <h1><?php esc_html_e('SMS Configuration', 'pexpress'); ?></h1>
                <p class="pexpress-wizard-subtitle"><?php esc_html_e('Configure SMS notifications (optional)', 'pexpress'); ?></p>
            </div>

            <div class="pexpress-wizard-body">
                <div class="pexpress-sms-info">
                    <p><?php esc_html_e('SMS notifications automatically alert team members when orders are assigned to them. This step is optional - you can configure SMS settings later from Settings.', 'pexpress'); ?></p>
                </div>

                <div class="pexpress-sms-status">
                    <?php if ($sms_enabled) : ?>
                        <div class="pexpress-info-box pexpress-success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p><strong><?php esc_html_e('SMS notifications are enabled', 'pexpress'); ?></strong></p>
                            <p><?php esc_html_e('Your SMS settings are already configured.', 'pexpress'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="pexpress-info-box">
                            <span class="dashicons dashicons-info"></span>
                            <p><strong><?php esc_html_e('SMS notifications are not configured', 'pexpress'); ?></strong></p>
                            <p><?php esc_html_e('You can configure SMS settings later from Polar Express → Settings.', 'pexpress'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="pexpress-wizard-actions-center">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-settings')); ?>" class="pexpress-btn pexpress-btn-primary" target="_blank">
                        <?php esc_html_e('Configure SMS Settings →', 'pexpress'); ?>
                    </a>
                </div>

                <div class="pexpress-info-box">
                    <h4><?php esc_html_e('What SMS notifications do:', 'pexpress'); ?></h4>
                    <ul>
                        <li><?php esc_html_e('Notify delivery personnel when orders are assigned', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Alert fridge providers about collection tasks', 'pexpress'); ?></li>
                        <li><?php esc_html_e('Inform distributors about fulfillment requirements', 'pexpress'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Render Step 5: Complete
     */
    private function render_step_complete()
    {
    ?>
        <div class="pexpress-wizard-step">
            <div class="pexpress-wizard-header">
                <div class="pexpress-success-icon">✓</div>
                <h1><?php esc_html_e('Setup Complete!', 'pexpress'); ?></h1>
                <p class="pexpress-wizard-subtitle"><?php esc_html_e('Your Polar Express plugin is ready to use', 'pexpress'); ?></p>
            </div>

            <div class="pexpress-wizard-body">
                <div class="pexpress-completion-summary">
                    <h3><?php esc_html_e('What\'s Next?', 'pexpress'); ?></h3>
                    <div class="pexpress-next-steps">
                        <div class="pexpress-next-step-card">
                            <span class="dashicons dashicons-clipboard"></span>
                            <h4><?php esc_html_e('Start Managing Orders', 'pexpress'); ?></h4>
                            <p><?php esc_html_e('Go to Agency Dashboard to start assigning orders to your team', 'pexpress'); ?></p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express')); ?>" class="pexpress-btn pexpress-btn-primary">
                                <?php esc_html_e('Open Agency Dashboard', 'pexpress'); ?>
                            </a>
                        </div>
                        <div class="pexpress-next-step-card">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <h4><?php esc_html_e('Configure Settings', 'pexpress'); ?></h4>
                            <p><?php esc_html_e('Review and adjust plugin settings, manage roles, and configure SMS', 'pexpress'); ?></p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-settings')); ?>" class="pexpress-btn pexpress-btn-secondary">
                                <?php esc_html_e('Go to Settings', 'pexpress'); ?>
                            </a>
                        </div>
                        <div class="pexpress-next-step-card">
                            <span class="dashicons dashicons-book-alt"></span>
                            <h4><?php esc_html_e('Read Documentation', 'pexpress'); ?></h4>
                            <p><?php esc_html_e('Check the setup guideline for detailed instructions and best practices', 'pexpress'); ?></p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-setup')); ?>" class="pexpress-btn pexpress-btn-secondary">
                                <?php esc_html_e('View Guide', 'pexpress'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
}
