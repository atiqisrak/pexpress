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
        initSupportDashboardFilters();
        initTabs();
        initHistoryRowExpansion();
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
        $('.polar-status-update-form, .polar-fridge-status-form, .polar-distributor-status-form').each(function () {
            var $form = $(this);
            $form.find('button[type="submit"]').on('click', function () {
                $form.data('clicked-button', $(this));
            });
        }).on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var orderId = $form.data('order-id');
            var $statusField = $form.find('[name="status"]').not('button');
            var $button = $form.data('clicked-button');

            if (!$button || $button.length === 0) {
                $button = $form.find('button[type="submit"]').first();
            }

            var newStatus = $statusField.length ? $statusField.val() : '';
            if (!newStatus) {
                newStatus = $button.val();
            }

            // Disable button
            if ($button && $button.length) {
                $button.prop('disabled', true);
                if (!$button.data('original-text')) {
                    $button.data('original-text', $.trim($button.text()) || '');
                }
                $button.text('Updating...');
            }

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
                        if ($button && $button.length) {
                            $button.prop('disabled', false).text($button.data('original-text') || 'Update');
                        }
                    }
                    $form.removeData('clicked-button');
                },
                error: function () {
                    showNotice('An error occurred. Please try again.', 'error');
                    if ($button && $button.length) {
                        $button.prop('disabled', false).text($button.data('original-text') || 'Update');
                    }
                    $form.removeData('clicked-button');
                }
            });
        });
    }

    /**
     * Initialize tabs functionality
     */
    function initTabs() {
        $('.polar-tab').on('click', function () {
            var $tab = $(this);
            var tabId = $tab.data('tab');
            var $tabs = $tab.closest('.polar-tabs').find('.polar-tab');
            var $contents = $tab.closest('.polar-tasks-section, .polar-orders-section').find('.polar-tab-content');

            // Remove active class from all tabs and contents
            $tabs.removeClass('active');
            $contents.removeClass('active');

            // Add active class to clicked tab and corresponding content
            $tab.addClass('active');
            $('#tab-' + tabId).addClass('active');
        });
    }

    /**
     * Initialize Support Dashboard filters and search
     */
    function initSupportDashboardFilters() {
        var $statusFilter = $('#polar-status-filter');
        var $searchInput = $('#polar-search');
        var $ordersList = $('#polar-support-orders');

        if ($statusFilter.length === 0 || $searchInput.length === 0 || $ordersList.length === 0) {
            return;
        }

        function filterOrders() {
            var statusValue = $statusFilter.val();
            var searchValue = $searchInput.val().toLowerCase().trim();
            var $orders = $ordersList.find('.polar-order-item');
            var visibleCount = 0;

            $orders.each(function () {
                var $order = $(this);
                var orderStatus = $order.data('status') || '';
                var orderId = $order.data('order-id') || '';
                var orderText = $order.text().toLowerCase();
                var customerName = $order.find('.customer-name').text().toLowerCase();
                var phoneNumber = $order.find('.phone-number').text().toLowerCase();

                // Normalize status values for comparison
                var normalizedOrderStatus = orderStatus.replace('wc-', '');
                var normalizedFilterStatus = statusValue.replace('wc-', '');

                var statusMatch = !statusValue ||
                    orderStatus === statusValue ||
                    orderStatus === 'wc-' + statusValue ||
                    normalizedOrderStatus === normalizedFilterStatus ||
                    orderStatus === normalizedFilterStatus ||
                    'wc-' + normalizedOrderStatus === statusValue;

                var searchMatch = !searchValue ||
                    orderText.indexOf(searchValue) !== -1 ||
                    orderId.toString().indexOf(searchValue) !== -1 ||
                    customerName.indexOf(searchValue) !== -1 ||
                    phoneNumber.indexOf(searchValue) !== -1;

                if (statusMatch && searchMatch) {
                    $order.fadeIn(200);
                    visibleCount++;
                } else {
                    $order.fadeOut(200);
                }
            });

            // Show empty state if no orders visible
            var $emptyState = $ordersList.find('.polar-empty-state');
            if (visibleCount === 0 && $orders.length > 0) {
                if ($emptyState.length === 0) {
                    $ordersList.append(
                        '<div class="polar-empty-state">' +
                        '<div class="empty-state-icon">' +
                        '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                        '<path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
                        '</svg>' +
                        '</div>' +
                        '<h3>No orders found</h3>' +
                        '<p>Try adjusting your filters or search terms.</p>' +
                        '</div>'
                    );
                }
                $emptyState.fadeIn(200);
            } else {
                $emptyState.fadeOut(200);
            }
        }

        // Bind events
        $statusFilter.on('change', filterOrders);
        $searchInput.on('input', function () {
            clearTimeout($searchInput.data('timeout'));
            var timeout = setTimeout(filterOrders, 300);
            $searchInput.data('timeout', timeout);
        });
    }

    /**
     * Initialize history row expansion
     */
    function initHistoryRowExpansion() {
        $(document).on('click', '.polar-toggle-details', function (e) {
            e.preventDefault();
            var $button = $(this);
            var orderId = $button.data('order-id');
            var $detailsRow = $('.polar-history-details[data-order-id="' + orderId + '"]');
            var $historyRow = $('.polar-history-row[data-order-id="' + orderId + '"]');

            if ($detailsRow.length === 0) {
                return;
            }

            if ($detailsRow.is(':visible')) {
                $detailsRow.slideUp(200);
                $button.text('View');
                $historyRow.removeClass('expanded');
            } else {
                $detailsRow.slideDown(200);
                $button.text('Hide');
                $historyRow.addClass('expanded');
            }
        });

        // Make entire row clickable for better UX
        $(document).on('click', '.polar-history-row', function (e) {
            // Don't trigger if clicking on a link or button
            if ($(e.target).is('a, button, .polar-toggle-details')) {
                return;
            }
            var $row = $(this);
            var orderId = $row.data('order-id');
            var $button = $row.find('.polar-toggle-details');
            if ($button.length) {
                $button.trigger('click');
            }
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

