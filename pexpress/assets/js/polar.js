/**
 * Polar Express Dashboard JavaScript
 *
 * @package PExpress
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function () {
        initHeartbeat();
        initAssignmentForms();
        initStatusUpdateForms();
    });

    /**
     * Initialize WordPress Heartbeat for real-time updates
     */
    function initHeartbeat() {
        // Ensure heartbeat is enabled
        if (typeof wp === 'undefined' || typeof wp.heartbeat === 'undefined') {
            return;
        }

        // Trigger heartbeat every 15 seconds
        setInterval(function () {
            wp.heartbeat.connectNow();
        }, 15000);

        // Listen for heartbeat tick
        $(document).on('heartbeat-tick', function (e, data) {
            if (data.polar_tasks) {
                updateTaskList(data.polar_tasks);
            }
        });
    }

    /**
     * Update task list with new data
     */
    function updateTaskList(tasks) {
        // Update visual indicator
        $('.polar-dashboard').each(function () {
            var $dashboard = $(this);
            var $indicator = $dashboard.find('.polar-update-indicator');

            if ($indicator.length === 0) {
                $dashboard.find('h2').append('<span class="polar-update-indicator"></span>');
            }
        });

        // Reload the page after a short delay to show updated data
        // In a production environment, you might want to do AJAX updates instead
        setTimeout(function () {
            // Optional: Use AJAX to refresh specific sections instead of full page reload
            // For now, we'll just show the indicator
        }, 500);
    }

    /**
     * Initialize assignment forms (HR Dashboard)
     */
    function initAssignmentForms() {
        $('.polar-assign-form').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var orderId = $form.data('order-id');
            var $button = $form.find('button[type="submit"]');

            // Disable button
            $button.prop('disabled', true).text('Assigning...');

            // Prepare form data
            var formData = $form.serialize();
            formData += '&action=polar_assign_order';
            formData += '&order_id=' + orderId;

            // Submit via AJAX
            $.ajax({
                url: polarExpress.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        $form.closest('.polar-order-item').fadeOut(300, function () {
                            $(this).remove();
                        });
                        showNotice('Order assigned successfully!', 'success');
                    } else {
                        showNotice(response.data.message || 'Failed to assign order.', 'error');
                        $button.prop('disabled', false).text('Assign');
                    }
                },
                error: function () {
                    showNotice('An error occurred. Please try again.', 'error');
                    $button.prop('disabled', false).text('Assign');
                }
            });
        });
    }

    /**
     * Initialize status update forms
     */
    function initStatusUpdateForms() {
        $('.polar-status-update-form, .polar-fridge-status-form, .polar-distributor-status-form').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var orderId = $form.data('order-id');
            var $button = $form.find('button[type="submit"]');
            var newStatus = $form.find('button[type="submit"]').val();

            // Disable button
            $button.prop('disabled', true).text('Updating...');

            // Prepare form data
            var formData = $form.serialize();
            formData += '&action=polar_update_order_status';
            formData += '&order_id=' + orderId;
            formData += '&status=' + newStatus;

            // Submit via AJAX
            $.ajax({
                url: polarExpress.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        showNotice('Order status updated successfully!', 'success');
                        // Reload page to show updated status
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotice(response.data.message || 'Failed to update status.', 'error');
                        $button.prop('disabled', false).text($button.data('original-text') || 'Update');
                    }
                },
                error: function () {
                    showNotice('An error occurred. Please try again.', 'error');
                    $button.prop('disabled', false).text($button.data('original-text') || 'Update');
                }
            });
        });
    }

    /**
     * Show notice message
     */
    function showNotice(message, type) {
        type = type || 'info';

        var $notice = $('<div class="polar-notice polar-notice-' + type + '">' + message + '</div>');
        $notice.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 20px',
            background: type === 'success' ? '#00a32a' : '#d63638',
            color: '#fff',
            borderRadius: '4px',
            zIndex: 9999,
            boxShadow: '0 2px 8px rgba(0,0,0,0.2)'
        });

        $('body').append($notice);

        // Remove after 3 seconds
        setTimeout(function () {
            $notice.fadeOut(300, function () {
                $(this).remove();
            });
        }, 3000);
    }

})(jQuery);

