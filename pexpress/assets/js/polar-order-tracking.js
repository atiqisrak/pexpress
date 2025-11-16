/**
 * Order Tracking JavaScript
 *
 * @package PExpress
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function () {
        if ($('.polar-order-tracking').length > 0) {
            initOrderTracking();
        }
    });

    /**
     * Initialize order tracking
     */
    function initOrderTracking() {
        var $tracking = $('.polar-order-tracking');
        var orderId = $tracking.data('order-id');

        if (!orderId) {
            return;
        }

        // Initialize heartbeat for real-time updates
        initHeartbeat(orderId);

        // Initialize AJAX polling as fallback
        initAjaxPolling(orderId);
    }

    /**
     * Initialize WordPress Heartbeat for real-time updates
     */
    function initHeartbeat(orderId) {
        if (typeof wp === 'undefined' || typeof wp.heartbeat === 'undefined') {
            return;
        }

        // Listen for heartbeat tick
        $(document).on('heartbeat-send', function (e, data) {
            data.polar_order_tracking = {
                order_id: orderId,
            };
        });

        $(document).on('heartbeat-tick', function (e, data) {
            if (data.polar_order_tracking) {
                // Heartbeat returns simpler format, need to enrich it
                enrichTrackingData(data.polar_order_tracking, function(enrichedData) {
                    updateTrackingStatus(enrichedData);
                });
            }
        });

        // Trigger heartbeat every 15 seconds
        setInterval(function () {
            wp.heartbeat.connectNow();
        }, 15000);
    }

    /**
     * Initialize AJAX polling as fallback
     */
    function initAjaxPolling(orderId) {
        var pollInterval = 30000; // 30 seconds
        var pollTimer;

        function pollStatus() {
            $.ajax({
                url: polarOrderTracking.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'polar_get_order_tracking',
                    order_id: orderId,
                    nonce: polarOrderTracking.nonce,
                },
                success: function (response) {
                    if (response.success && response.data) {
                        updateTrackingStatus(response.data);
                    }
                },
                error: function () {
                    // Silently fail, will retry on next poll
                },
                complete: function () {
                    // Schedule next poll
                    pollTimer = setTimeout(pollStatus, pollInterval);
                },
            });
        }

        // Start polling after initial delay
        pollTimer = setTimeout(pollStatus, pollInterval);

        // Clear timer on page unload
        $(window).on('beforeunload', function () {
            if (pollTimer) {
                clearTimeout(pollTimer);
            }
        });
    }

    /**
     * Enrich tracking data from heartbeat with labels and classes
     */
    function enrichTrackingData(data, callback) {
        // Status labels mapping
        var statusLabels = {
            agency: {
                pending: 'Pending',
                assigned: 'Assigned',
            },
            delivery: {
                pending: 'Pending',
                meet_point_arrived: 'Reached Meet Point',
                delivery_location_arrived: 'Reached Delivery Location',
                service_in_progress: 'Service In Progress',
                service_complete: 'Service Completed',
                customer_served: 'Ice-cream Delivered',
            },
            fridge: {
                pending: 'Pending',
                fridge_drop: 'Fridge Delivered On-site',
                fridge_collected: 'Fridge Collected On-site',
                fridge_returned: 'Fridge Returned to Base',
            },
            distributor: {
                pending: 'Pending',
                distributor_prep: 'Product Provider Preparing',
                out_for_delivery: 'Out for Delivery',
                handoff_complete: 'Product Provider Handoff Complete',
            },
        };

        function getStatusLabel(role, status) {
            if (statusLabels[role] && statusLabels[role][status]) {
                return statusLabels[role][status];
            }
            return status.replace(/_/g, ' ').replace(/\b\w/g, function(l) {
                return l.toUpperCase();
            });
        }

        function getStatusClass(status) {
            var completedStatuses = ['customer_served', 'fridge_returned', 'handoff_complete', 'service_complete'];
            var inProgressStatuses = ['meet_point_arrived', 'delivery_location_arrived', 'service_in_progress', 'fridge_drop', 'fridge_collected', 'distributor_prep', 'out_for_delivery', 'assigned'];
            
            if (completedStatuses.indexOf(status) !== -1) {
                return 'completed';
            } else if (inProgressStatuses.indexOf(status) !== -1) {
                return 'in-progress';
            }
            return 'pending';
        }

        var enriched = {
            order_id: data.order_id,
            statuses: {
                hr: {
                    status: data.statuses.hr.status,
                    label: getStatusLabel('agency', data.statuses.hr.status),
                    class: getStatusClass(data.statuses.hr.status),
                },
                delivery: {
                    status: data.statuses.delivery.status,
                    label: getStatusLabel('delivery', data.statuses.delivery.status),
                    class: getStatusClass(data.statuses.delivery.status),
                    user_name: data.statuses.delivery.user_name || '',
                },
                fridge: {
                    status: data.statuses.fridge.status,
                    label: getStatusLabel('fridge', data.statuses.fridge.status),
                    class: getStatusClass(data.statuses.fridge.status),
                    user_name: data.statuses.fridge.user_name || '',
                },
                distributor: {
                    status: data.statuses.distributor.status,
                    label: getStatusLabel('distributor', data.statuses.distributor.status),
                    class: getStatusClass(data.statuses.distributor.status),
                    user_name: data.statuses.distributor.user_name || '',
                },
            },
            timestamp: data.timestamp,
        };

        callback(enriched);
    }

    /**
     * Update tracking status display
     */
    function updateTrackingStatus(data) {
        if (!data || !data.statuses) {
            return;
        }

        var statuses = data.statuses;

        // Update Agency status
        if (statuses.hr) {
            updateStatusCard('agency', statuses.hr);
        }

        // Update Delivery status
        if (statuses.delivery) {
            updateStatusCard('delivery', statuses.delivery);
        }

        // Update Fridge status
        if (statuses.fridge) {
            updateStatusCard('fridge', statuses.fridge);
        }

        // Update Distributor status
        if (statuses.distributor) {
            updateStatusCard('distributor', statuses.distributor);
        }

        // Update last update time
        if (data.timestamp) {
            var $updateTime = $('#polar-last-update-time');
            if ($updateTime.length) {
                var date = new Date(data.timestamp);
                var formatted = date.toLocaleString();
                $updateTime.text(formatted);
            }
        }

        // Add visual indicator for update
        var $tracking = $('.polar-order-tracking');
        $tracking.addClass('polar-updated');
        setTimeout(function () {
            $tracking.removeClass('polar-updated');
        }, 1000);
    }

    /**
     * Update individual status card
     */
    function updateStatusCard(role, statusData) {
        var $card = $('.polar-status-' + role);
        if (!$card.length) {
            return;
        }

        // Update badge
        var $badge = $card.find('.polar-status-badge');
        if ($badge.length && statusData.label) {
            $badge
                .attr('data-status', statusData.status)
                .removeClass('polar-status-pending polar-status-in-progress polar-status-completed')
                .addClass('polar-status-' + statusData.class)
                .text(statusData.label);
        }

        // Update progress bar
        var $progressFill = $card.find('.polar-progress-fill');
        if ($progressFill.length) {
            var progress = calculateProgress(role, statusData.status);
            $progressFill
                .removeClass('polar-progress-pending polar-progress-in-progress polar-progress-completed')
                .addClass('polar-progress-' + statusData.class)
                .css('width', progress + '%');
        }

        // Update assigned user name if provided
        if (statusData.user_name) {
            var $userName = $card.find('.polar-user-name');
            if ($userName.length) {
                $userName.text(statusData.user_name);
            }
            var $assignedUser = $card.find('.polar-assigned-user');
            if ($assignedUser.length && !$assignedUser.is(':visible')) {
                $assignedUser.show();
            }
        }
    }

    /**
     * Calculate progress percentage based on status
     */
    function calculateProgress(role, status) {
        var progress = 0;

        switch (role) {
            case 'agency':
                if (status === 'assigned') {
                    progress = 100;
                }
                break;

            case 'distributor':
                if (status === 'distributor_prep') {
                    progress = 33;
                } else if (status === 'out_for_delivery') {
                    progress = 66;
                } else if (status === 'handoff_complete') {
                    progress = 100;
                }
                break;

            case 'delivery':
                if (status === 'meet_point_arrived') {
                    progress = 20;
                } else if (status === 'delivery_location_arrived') {
                    progress = 40;
                } else if (status === 'service_in_progress') {
                    progress = 60;
                } else if (status === 'service_complete') {
                    progress = 80;
                } else if (status === 'customer_served') {
                    progress = 100;
                }
                break;

            case 'fridge':
                if (status === 'fridge_drop') {
                    progress = 33;
                } else if (status === 'fridge_collected') {
                    progress = 66;
                } else if (status === 'fridge_returned') {
                    progress = 100;
                }
                break;
        }

        return progress;
    }

})(jQuery);

