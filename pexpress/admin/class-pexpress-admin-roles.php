<?php

/**
 * Admin role management
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin roles handler
 */
class PExpress_Admin_Roles
{

    /**
     * Handle role assignment
     */
    public function handle_role_assignment()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to perform this action.', 'pexpress'));
        }

        if (!isset($_POST['user_id']) || !isset($_POST['polar_role']) || !isset($_POST['pexpress_role_nonce'])) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=missing_fields'));
            exit;
        }

        $user_id = intval($_POST['user_id']);
        $polar_role = sanitize_text_field($_POST['polar_role']);

        if (!wp_verify_nonce($_POST['pexpress_role_nonce'], 'pexpress_assign_role_' . $user_id)) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=nonce_failed'));
            exit;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=user_not_found'));
            exit;
        }

        // Polar Express roles
        $polar_roles = array('polar_hr', 'polar_delivery', 'polar_fridge', 'polar_distributor', 'polar_support');

        // Remove existing Polar roles
        foreach ($polar_roles as $role) {
            $user->remove_role($role);
        }

        // Add new role if not removing
        if ($polar_role !== 'remove' && in_array($polar_role, $polar_roles)) {
            $user->add_role($polar_role);
        }

        wp_redirect(admin_url('admin.php?page=polar-express-settings&role_assigned=1'));
        exit;
    }

    /**
     * Handle adding user to role
     */
    public function handle_add_user_to_role()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to perform this action.', 'pexpress'));
        }

        if (!isset($_POST['user_id']) || !isset($_POST['polar_role']) || !isset($_POST['pexpress_add_user_nonce'])) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=missing_fields'));
            exit;
        }

        if (!wp_verify_nonce($_POST['pexpress_add_user_nonce'], 'pexpress_add_user_to_role')) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=nonce_failed'));
            exit;
        }

        // Handle multiple user IDs
        $user_ids = isset($_POST['user_id']) ? $_POST['user_id'] : array();
        if (!is_array($user_ids)) {
            $user_ids = array($user_ids);
        }
        $user_ids = array_map('intval', $user_ids);
        $user_ids = array_filter($user_ids); // Remove empty values

        if (empty($user_ids)) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=missing_fields'));
            exit;
        }

        $polar_role = sanitize_text_field($_POST['polar_role']);

        $polar_roles = array('polar_hr', 'polar_delivery', 'polar_fridge', 'polar_distributor', 'polar_support');
        if (!in_array($polar_role, $polar_roles)) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=invalid_role'));
            exit;
        }

        $success_count = 0;
        $failed_count = 0;

        // Process each user
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) {
                $failed_count++;
                continue;
            }

            // Check if user already has this role
            if (in_array($polar_role, $user->roles)) {
                // User already has this role, skip
                continue;
            }

            // Add the new role (don't remove existing polar roles - allow multiple roles)
            $user->add_role($polar_role);
            $success_count++;
        }

        // Redirect with success message
        if ($success_count > 0) {
            $message = $success_count === 1
                ? 'user_added=1'
                : 'users_added=' . $success_count;
            if ($failed_count > 0) {
                $message .= '&some_failed=' . $failed_count;
            }
            wp_redirect(admin_url('admin.php?page=polar-express-settings&' . $message));
        } else {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=user_not_found'));
        }
        exit;
    }

    /**
     * Handle removing user from role
     */
    public function handle_remove_user_from_role()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to perform this action.', 'pexpress'));
        }

        if (!isset($_POST['user_id']) || !isset($_POST['polar_role']) || !isset($_POST['pexpress_remove_user_nonce'])) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=missing_fields'));
            exit;
        }

        if (!wp_verify_nonce($_POST['pexpress_remove_user_nonce'], 'pexpress_remove_user_from_role')) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=nonce_failed'));
            exit;
        }

        $user_id = intval($_POST['user_id']);
        $polar_role = sanitize_text_field($_POST['polar_role']);

        $user = get_userdata($user_id);
        if (!$user) {
            wp_redirect(admin_url('admin.php?page=polar-express-settings&error=user_not_found'));
            exit;
        }

        // Remove the role
        $user->remove_role($polar_role);

        wp_redirect(admin_url('admin.php?page=polar-express-settings&user_removed=1'));
        exit;
    }

    /**
     * AJAX handler to get users for a role
     */
    public function ajax_get_users_for_role()
    {
        check_ajax_referer('polar_express_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'pexpress')));
        }

        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $polar_roles = array('polar_hr', 'polar_delivery', 'polar_fridge', 'polar_distributor', 'polar_support');

        if (!in_array($role, $polar_roles)) {
            wp_send_json_error(array('message' => __('Invalid role.', 'pexpress')));
        }

        $users = get_users(array('role' => $role));
        $users_data = array();

        foreach ($users as $user) {
            $users_data[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
            );
        }

        wp_send_json_success(array('users' => $users_data));
    }

    /**
     * Render add user modal
     */
    public function render_add_user_modal($polar_roles, $all_users)
    {
        echo '<div id="polar-add-user-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; align-items: center; justify-content: center;">';
        echo '<div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 800px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">';
        echo '<h2 style="margin-top: 0;">' . esc_html__('Add User to Role', 'pexpress') . '</h2>';
        echo '<form id="polar-add-user-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('pexpress_add_user_to_role', 'pexpress_add_user_nonce');
        echo '<input type="hidden" name="action" value="pexpress_add_user_to_role">';

        echo '<div style="margin-bottom: 20px; width: 100%; display: block;">';
        echo '<label for="polar_add_role" style="display: block; margin-bottom: 8px; font-weight: 600;">' . esc_html__('Select Role:', 'pexpress') . '</label>';
        echo '<select name="polar_role" id="polar_add_role" required style="width: 100%; max-width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; box-sizing: border-box; display: block;">';
        echo '<option value="">' . esc_html__('Select a role...', 'pexpress') . '</option>';
        foreach ($polar_roles as $role_key => $role_name) {
            echo '<option value="' . esc_attr($role_key) . '">' . esc_html($role_name) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div style="margin-bottom: 20px; width: 100%; display: block;">';
        echo '<label for="polar_add_user" style="display: block; margin-bottom: 8px; font-weight: 600;">' . esc_html__('Select Users (Multiple Selection):', 'pexpress') . '</label>';
        echo '<select name="user_id[]" id="polar_add_user" multiple required style="width: 100%; max-width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; min-height: 200px; font-size: 14px; box-sizing: border-box; display: block;" disabled>';
        echo '<option value="">' . esc_html__('Please select a role first...', 'pexpress') . '</option>';
        // Store all users with their roles as data attributes
        foreach ($all_users as $user) {
            $user_roles = array_intersect($user->roles, array_keys($polar_roles));
            $user_roles_str = implode(',', $user_roles);
            $current_roles_text = '';
            if (!empty($user_roles)) {
                $role_names = array();
                foreach ($user_roles as $ur) {
                    $role_names[] = $polar_roles[$ur];
                }
                $current_roles_text = ' - ' . esc_html__('Current:', 'pexpress') . ' ' . implode(', ', $role_names);
            }
            echo '<option value="' . esc_attr($user->ID) . '" data-roles="' . esc_attr($user_roles_str) . '" class="polar-user-option">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')' . $current_roles_text . '</option>';
        }
        echo '</select>';
        echo '<p style="margin: 5px 0 0 0; font-size: 12px; color: #646970;" id="polar-user-help-text">';
        echo esc_html__('Please select a role first to see available users. Users already assigned to the selected role will be hidden.', 'pexpress');
        echo '</p>';
        echo '<p style="margin: 5px 0 0 0; font-size: 12px; color: #2271b1; font-weight: 600;" id="polar-selected-count">' . esc_html__('0 users selected', 'pexpress') . '</p>';
        echo '</div>';

        echo '<div style="display: flex; gap: 10px; justify-content: flex-end;">';
        echo '<button type="button" id="polar-cancel-add-user" class="button">' . esc_html__('Cancel', 'pexpress') . '</button>';
        echo '<button type="submit" class="button button-primary">' . esc_html__('Add Users', 'pexpress') . '</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render settings scripts
     */
    public function render_settings_scripts()
    {
?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Open modal
                $('#polar-add-user-btn').on('click', function() {
                    $('#polar-add-user-modal').css('display', 'flex');
                    // Reset form
                    $('#polar-add-user-form')[0].reset();
                    $('#polar-selected-count').text('<?php echo esc_js(__('0 users selected', 'pexpress')); ?>');
                    $('#polar_add_user').prop('disabled', true);
                    $('#polar-user-help-text').text('<?php echo esc_js(__('Please select a role first to see available users. Users already assigned to the selected role will be hidden.', 'pexpress')); ?>');
                });

                // Close modal
                $('#polar-cancel-add-user, #polar-add-user-modal').on('click', function(e) {
                    if (e.target === this) {
                        $('#polar-add-user-modal').hide();
                    }
                });

                // Filter users when role is selected
                $('#polar_add_role').on('change', function() {
                    var selectedRole = $(this).val();
                    var $userSelect = $('#polar_add_user');

                    if (!selectedRole) {
                        $userSelect.prop('disabled', true).val(null);
                        $userSelect.find('option').not(':first').hide();
                        $('#polar-selected-count').text('<?php echo esc_js(__('0 users selected', 'pexpress')); ?>');
                        $('#polar-user-help-text').text('<?php echo esc_js(__('Please select a role first to see available users. Users already assigned to the selected role will be hidden.', 'pexpress')); ?>');
                        return;
                    }

                    // Enable user select
                    $userSelect.prop('disabled', false);

                    // Show/hide users based on selected role
                    var visibleCount = 0;
                    $userSelect.find('.polar-user-option').each(function() {
                        var $option = $(this);
                        var userRoles = $option.data('roles') || '';
                        var userRolesArray = userRoles ? userRoles.split(',') : [];

                        // Hide if user already has this role
                        if (userRolesArray.indexOf(selectedRole) !== -1) {
                            $option.hide().prop('selected', false);
                        } else {
                            $option.show();
                            visibleCount++;
                        }
                    });

                    // Update help text
                    if (visibleCount === 0) {
                        $('#polar-user-help-text').html('<span style="color: #d63638;"><?php echo esc_js(__('All users are already assigned to this role.', 'pexpress')); ?></span>');
                    } else {
                        $('#polar-user-help-text').text('<?php echo esc_js(__('Hold Ctrl (Windows) or Cmd (Mac) to select multiple users. Users already assigned to the selected role are hidden.', 'pexpress')); ?>');
                    }

                    // Reset selected count
                    $('#polar-selected-count').text('<?php echo esc_js(__('0 users selected', 'pexpress')); ?>');
                });

                // Update selected count for multi-select
                $('#polar_add_user').on('change', function() {
                    var selectedCount = $(this).val() ? $(this).val().length : 0;
                    if (selectedCount === 0) {
                        $('#polar-selected-count').text('<?php echo esc_js(__('0 users selected', 'pexpress')); ?>');
                    } else if (selectedCount === 1) {
                        $('#polar-selected-count').text('<?php echo esc_js(__('1 user selected', 'pexpress')); ?>');
                    } else {
                        $('#polar-selected-count').text(selectedCount + ' <?php echo esc_js(__('users selected', 'pexpress')); ?>');
                    }
                });

                // Validate form before submit
                $('#polar-add-user-form').on('submit', function(e) {
                    var role = $('#polar_add_role').val();
                    var users = $('#polar_add_user').val();

                    if (!role) {
                        alert('<?php echo esc_js(__('Please select a role.', 'pexpress')); ?>');
                        e.preventDefault();
                        return false;
                    }

                    if (!users || users.length === 0) {
                        alert('<?php echo esc_js(__('Please select at least one user.', 'pexpress')); ?>');
                        e.preventDefault();
                        return false;
                    }

                    return true;
                });

                // Remove user from role
                $('.polar-remove-user-btn').on('click', function() {
                    var $btn = $(this);
                    var role = $btn.data('role');
                    var userId = $btn.data('user-id');
                    var userName = $btn.data('user-name');

                    if (!userId) {
                        alert('<?php echo esc_js(__('Invalid user selected.', 'pexpress')); ?>');
                        return;
                    }

                    var confirmMessage = '<?php echo esc_js(__('Are you sure you want to remove', 'pexpress')); ?>' + ' "' + userName + '" ' + '<?php echo esc_js(__('from this role?', 'pexpress')); ?>';

                    if (!confirm(confirmMessage)) {
                        return;
                    }

                    // Disable button and show loading state
                    $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> <?php echo esc_js(__('Removing...', 'pexpress')); ?>');

                    var form = $('<form>', {
                        method: 'post',
                        action: '<?php echo esc_url(admin_url('admin-post.php')); ?>'
                    });

                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'action',
                        value: 'pexpress_remove_user_from_role'
                    }));
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'user_id',
                        value: userId
                    }));
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'polar_role',
                        value: role
                    }));
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'pexpress_remove_user_nonce',
                        value: '<?php echo wp_create_nonce('pexpress_remove_user_from_role'); ?>'
                    }));

                    $('body').append(form);
                    form.submit();
                });
            });
        </script>
        <style>
            #polar-add-user-form {
                width: 100%;
                display: block;
            }

            #polar-add-user-form>div {
                width: 100%;
                display: block;
            }

            #polar_add_role,
            #polar_add_user {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                display: block !important;
            }

            #polar_add_user {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right 8px center;
                background-size: 16px 12px;
                padding-right: 30px;
            }

            #polar_add_user option {
                padding: 5px;
            }

            #polar_add_user option:checked {
                background: #2271b1 linear-gradient(0deg, #2271b1 0%, #2271b1 100%);
                color: #fff;
            }

            #polar_add_user:disabled {
                background-color: #f0f0f1;
                cursor: not-allowed;
                opacity: 0.6;
            }

            #polar_add_user option[hidden] {
                display: none;
            }

            .polar-user-item {
                transition: all 0.2s ease;
            }

            .polar-user-item:hover {
                background: #f0f0f1 !important;
                border-color: #c3c4c7 !important;
            }

            .polar-remove-user-btn {
                transition: all 0.2s ease;
            }

            .polar-remove-user-btn:hover {
                background: #b32d2e !important;
                color: #fff !important;
                border-color: #8a2424 !important;
            }

            .polar-remove-user-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
        </style>
<?php
    }

    /**
     * Show role assignment notices
     */
    public function show_role_assignment_notices()
    {
        if (isset($_GET['role_assigned']) && $_GET['role_assigned'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('User role assigned successfully!', 'pexpress') . '</p></div>';
        }

        if (isset($_GET['user_added']) && $_GET['user_added'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('User added to role successfully!', 'pexpress') . '</p></div>';
        }

        if (isset($_GET['users_added']) && is_numeric($_GET['users_added'])) {
            $count = intval($_GET['users_added']);
            $message = $count === 1
                ? __('User added to role successfully!', 'pexpress')
                : sprintf(__('%d users added to role successfully!', 'pexpress'), $count);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';

            if (isset($_GET['some_failed']) && intval($_GET['some_failed']) > 0) {
                $failed_count = intval($_GET['some_failed']);
                echo '<div class="notice notice-warning is-dismissible"><p>' .
                    sprintf(esc_html__('Warning: %d user(s) could not be added. Please check if the user IDs are valid.', 'pexpress'), $failed_count) .
                    '</p></div>';
            }
        }

        if (isset($_GET['user_removed']) && $_GET['user_removed'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('User removed from role successfully!', 'pexpress') . '</p></div>';
        }

        if (isset($_GET['error'])) {
            $error_messages = array(
                'missing_fields' => __('Missing required fields.', 'pexpress'),
                'nonce_failed' => __('Security check failed.', 'pexpress'),
                'user_not_found' => __('User not found.', 'pexpress'),
                'invalid_role' => __('Invalid role selected.', 'pexpress'),
            );
            $error = isset($error_messages[$_GET['error']]) ? $error_messages[$_GET['error']] : __('An error occurred.', 'pexpress');
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
        }
    }
}
