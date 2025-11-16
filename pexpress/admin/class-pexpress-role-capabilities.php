<?php

/**
 * Role Capabilities Management
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Role Capabilities Manager class
 */
class PExpress_Role_Capabilities
{

    /**
     * Polar Express roles
     *
     * @var array
     */
    private $polar_roles = array(
        'polar_hr' => 'Polar Agency',
        'polar_delivery' => 'Polar HR',
        'polar_fridge' => 'Polar Fridge Provider',
        'polar_distributor' => 'Polar Distributor',
        'polar_support' => 'Polar Support',
    );

    /**
     * Render role capabilities page
     */
    public function render_role_capabilities_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'pexpress'));
        }

        // Handle capability save
        if (isset($_POST['save_capabilities']) && isset($_POST['role']) && isset($_POST['pexpress_capabilities_nonce'])) {
            $this->save_role_capabilities();
        }

        // Get selected role
        $selected_role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
        if (!empty($selected_role) && !isset($this->polar_roles[$selected_role])) {
            $selected_role = '';
        }

        // Get all WooCommerce capabilities
        $wc_caps = PExpress_WooCommerce_Capabilities::get_grouped_caps();
        
        // Get current role capabilities if role is selected
        $current_role_caps = array();
        if (!empty($selected_role)) {
            $role_obj = get_role($selected_role);
            if ($role_obj) {
                $current_role_caps = $role_obj->capabilities;
            }
        }

        // Show success/error messages
        if (isset($_GET['updated']) && $_GET['updated'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Capabilities updated successfully!', 'pexpress') . '</p></div>';
        }
        if (isset($_GET['error'])) {
            $error_msg = __('An error occurred.', 'pexpress');
            if ($_GET['error'] === 'invalid_role') {
                $error_msg = __('Invalid role selected.', 'pexpress');
            } elseif ($_GET['error'] === 'role_not_found') {
                $error_msg = __('Role not found.', 'pexpress');
            }
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_msg) . '</p></div>';
        }
        ?>
        <div class="wrap pexpress-role-capabilities">
            <h1><?php esc_html_e('Role Capabilities', 'pexpress'); ?></h1>
            <p class="description"><?php esc_html_e('Manage WooCommerce capabilities for Polar Express roles.', 'pexpress'); ?></p>

            <form method="get" action="" style="margin: 20px 0;">
                <input type="hidden" name="page" value="polar-express-role-capabilities">
                <label for="role-select">
                    <strong><?php esc_html_e('Select Role:', 'pexpress'); ?></strong>
                    <select name="role" id="role-select" style="margin-left: 10px; min-width: 200px;">
                        <option value=""><?php esc_html_e('-- Select a Role --', 'pexpress'); ?></option>
                        <?php foreach ($this->polar_roles as $role_key => $role_name) : ?>
                            <option value="<?php echo esc_attr($role_key); ?>" <?php selected($selected_role, $role_key); ?>>
                                <?php echo esc_html($role_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if (!empty($selected_role)) : ?>
                    <button type="submit" class="button" style="margin-left: 10px;"><?php esc_html_e('Change Role', 'pexpress'); ?></button>
                <?php else : ?>
                    <button type="submit" class="button button-primary" style="margin-left: 10px;"><?php esc_html_e('Load Capabilities', 'pexpress'); ?></button>
                <?php endif; ?>
            </form>

            <?php if (!empty($selected_role)) : ?>
                <form method="post" action="" id="capabilities-form">
                    <?php wp_nonce_field('pexpress_save_capabilities', 'pexpress_capabilities_nonce'); ?>
                    <input type="hidden" name="role" value="<?php echo esc_attr($selected_role); ?>">
                    
                    <div class="capabilities-editor" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                        <h2>
                            <?php printf(esc_html__('Capabilities for: %s', 'pexpress'), esc_html($this->polar_roles[$selected_role])); ?>
                        </h2>

                        <div style="margin: 20px 0;">
                            <button type="button" class="button" id="select-all-caps"><?php esc_html_e('Select All', 'pexpress'); ?></button>
                            <button type="button" class="button" id="deselect-all-caps"><?php esc_html_e('Deselect All', 'pexpress'); ?></button>
                            <button type="button" class="button" id="select-by-group"><?php esc_html_e('Select by Group', 'pexpress'); ?></button>
                        </div>

                        <div class="capabilities-groups">
                            <?php foreach ($wc_caps as $group => $group_data) : ?>
                                <div class="capability-group" style="margin-bottom: 30px; padding: 15px; border: 1px solid #e5e5e5; border-radius: 4px;">
                                    <h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1;">
                                        <input type="checkbox" class="group-checkbox" data-group="<?php echo esc_attr($group); ?>" style="margin-right: 8px;">
                                        <?php echo esc_html($group_data['label']); ?>
                                    </h3>
                                    
                                    <div class="capabilities-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 10px; margin-top: 15px;">
                                        <?php foreach ($group_data['caps'] as $cap => $cap_info) : ?>
                                            <?php
                                            $checked = isset($current_role_caps[$cap]) && $current_role_caps[$cap] ? 'checked' : '';
                                            $cap_label = !empty($cap_info['label']) ? $cap_info['label'] : $cap;
                                            ?>
                                            <label style="display: flex; align-items: center; padding: 8px; background: #f9f9f9; border-radius: 3px; cursor: pointer;">
                                                <input 
                                                    type="checkbox" 
                                                    name="capabilities[<?php echo esc_attr($cap); ?>]" 
                                                    value="1" 
                                                    <?php echo $checked; ?>
                                                    class="cap-checkbox group-<?php echo esc_attr($group); ?>"
                                                    style="margin-right: 8px;"
                                                >
                                                <span style="font-size: 13px;">
                                                    <strong><?php echo esc_html($cap_label); ?></strong>
                                                    <?php if (!empty($cap_info['description'])) : ?>
                                                        <br><small style="color: #666;"><?php echo esc_html($cap_info['description']); ?></small>
                                                    <?php endif; ?>
                                                    <br><code style="font-size: 11px; color: #999;"><?php echo esc_html($cap); ?></code>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e5e5e5;">
                            <button type="submit" name="save_capabilities" class="button button-primary button-large">
                                <?php esc_html_e('Save Capabilities', 'pexpress'); ?>
                            </button>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=polar-express-role-capabilities')); ?>" class="button" style="margin-left: 10px;">
                                <?php esc_html_e('Cancel', 'pexpress'); ?>
                            </a>
                        </div>
                    </div>
                </form>

                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Group checkbox toggle
                    $('.group-checkbox').on('change', function() {
                        var group = $(this).data('group');
                        var checked = $(this).is(':checked');
                        $('.group-' + group).prop('checked', checked);
                    });

                    // Update group checkbox when individual checkboxes change
                    $('.cap-checkbox').on('change', function() {
                        var classAttr = $(this).attr('class') || '';
                        var match = classAttr.match(/group-(\w+)/);
                        if (match && match[1]) {
                            var group = match[1];
                            var total = $('.group-' + group).length;
                            var checked = $('.group-' + group + ':checked').length;
                            $('.group-checkbox[data-group="' + group + '"]').prop('checked', total === checked);
                        }
                    });

                    // Select all
                    $('#select-all-caps').on('click', function() {
                        $('.cap-checkbox').prop('checked', true);
                        $('.group-checkbox').prop('checked', true);
                    });

                    // Deselect all
                    $('#deselect-all-caps').on('click', function() {
                        $('.cap-checkbox').prop('checked', false);
                        $('.group-checkbox').prop('checked', false);
                    });

                    // Select by group - show modal/prompt
                    $('#select-by-group').on('click', function() {
                        var groups = [];
                        $('.capability-group').each(function() {
                            groups.push($(this).find('h3').text().trim());
                        });
                        // For simplicity, just select all for now
                        // Can be enhanced with a modal to select specific groups
                        $('.cap-checkbox').prop('checked', true);
                        $('.group-checkbox').prop('checked', true);
                    });
                });
                </script>
            <?php else : ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('Please select a role to view and edit its capabilities.', 'pexpress'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save role capabilities
     */
    private function save_role_capabilities()
    {
        if (!wp_verify_nonce($_POST['pexpress_capabilities_nonce'], 'pexpress_save_capabilities')) {
            wp_die(__('Security check failed.', 'pexpress'));
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to perform this action.', 'pexpress'));
        }

        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        if (empty($role) || !isset($this->polar_roles[$role])) {
            wp_redirect(admin_url('admin.php?page=polar-express-role-capabilities&error=invalid_role'));
            exit;
        }

        $capabilities = isset($_POST['capabilities']) ? array_map('absint', $_POST['capabilities']) : array();
        
        // Get role object
        $role_obj = get_role($role);
        if (!$role_obj) {
            wp_redirect(admin_url('admin.php?page=polar-express-role-capabilities&role=' . $role . '&error=role_not_found'));
            exit;
        }

        // Get all WooCommerce capabilities
        $all_wc_caps = PExpress_WooCommerce_Capabilities::get_all_caps();

        // Remove all WooCommerce capabilities first
        foreach ($all_wc_caps as $cap) {
            $role_obj->remove_cap($cap);
        }

        // Add selected capabilities
        foreach ($capabilities as $cap => $value) {
            if ($value && in_array($cap, $all_wc_caps, true)) {
                $role_obj->add_cap($cap);
            }
        }

        // Always ensure read capability
        if (!$role_obj->has_cap('read')) {
            $role_obj->add_cap('read');
        }

        wp_redirect(admin_url('admin.php?page=polar-express-role-capabilities&role=' . $role . '&updated=1'));
        exit;
    }
}

