(function ($) {
    'use strict';

    let orderEdit = {
        init: function () {
            this.initProductSearch();
            this.initItemActions();
            this.initModificationHistory();
            this.initAddItem();
        },

        initProductSearch: function () {
            // Initialize product search for main form
            if ($('#polar-product-search').length) {
                this.initSelect2('#polar-product-search');
            }
            
            // Initialize product search for quick form
            if ($('#polar-product-search-quick').length) {
                this.initSelect2('#polar-product-search-quick');
            }
        },

        initSelect2: function (selector) {
            if (typeof $(selector).select2 === 'undefined') {
                console.error('Select2 is not available');
                return;
            }
            
            $(selector).select2({
                ajax: {
                    url: polarOrderEdit.ajaxUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            action: 'woocommerce_json_search_products',
                            term: params.term || '',
                            security: polarOrderEdit.wcSearchNonce || '',
                        };
                    },
                    processResults: function (data) {
                        const results = [];
                        if (data && typeof data === 'object') {
                            $.each(data, function (id, text) {
                                if (id && text) {
                                    results.push({
                                        id: id,
                                        text: text,
                                    });
                                }
                            });
                        }
                        return {
                            results: results,
                        };
                    },
                    error: function(xhr, status, error) {
                        console.error('Product search error:', error);
                        return {
                            results: []
                        };
                    },
                    cache: true,
                },
                minimumInputLength: 2,
                placeholder: 'Search for a product...',
                allowClear: true,
            });
        },

        initItemActions: function () {
            // Inject action buttons into order items table
            this.injectItemButtons();

            // Handle inline editing buttons on order items - use event delegation
            $(document).off('click', '.polar-edit-item').on('click', '.polar-edit-item', function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Edit button clicked');
                const $row = $(this).closest('tr');
                orderEdit.showItemEditor($row);
            });

            $(document).off('click', '.polar-remove-item').on('click', '.polar-remove-item', function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Remove button clicked');
                if (!confirm(polarOrderEdit.i18n.confirmRemove || 'Are you sure you want to remove this item?')) {
                    return;
                }
                const itemId = $(this).data('item-id');
                if (itemId) {
                    orderEdit.removeItem(itemId);
                } else {
                    console.error('Item ID not found');
                }
            });

            $(document).off('click', '.polar-replace-item').on('click', '.polar-replace-item', function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Replace button clicked');
                const itemId = $(this).data('item-id');
                if (itemId) {
                    orderEdit.showReplaceModal(itemId);
                } else {
                    console.error('Item ID not found');
                }
            });

            $(document).off('click', '.polar-save-item').on('click', '.polar-save-item', function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Save button clicked');
                const $row = $(this).closest('tr');
                orderEdit.saveItem($row);
            });

            $(document).off('click', '.polar-cancel-edit').on('click', '.polar-cancel-edit', function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Cancel button clicked');
                const $row = $(this).closest('tr');
                orderEdit.cancelEdit($row);
            });
        },

        injectItemButtons: function () {
            // Buttons are already in the template, just ensure they're initialized
            // This method is kept for compatibility but buttons are rendered server-side
        },

        initModificationHistory: function () {
            $('.polar-toggle-history').on('click', function () {
                const $content = $('.polar-history-content');
                $content.slideToggle();
                $(this).toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
            });
        },

        initAddItem: function () {
            // Main form button - use event delegation
            $(document).off('click', '.polar-add-item-btn').on('click', '.polar-add-item-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Add item button clicked');
                const productId = $('#polar-product-search').val();
                const quantity = parseInt($('#polar-item-quantity').val()) || 1;

                if (!productId) {
                    alert(polarOrderEdit.i18n.selectProduct || 'Please select a product.');
                    return;
                }

                console.log('Adding item:', productId, quantity);
                orderEdit.addItem(productId, quantity);
            });

            // Quick form button
            $(document).off('click', '.polar-add-item-btn-quick').on('click', '.polar-add-item-btn-quick', function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Add item quick button clicked');
                const productId = $('#polar-product-search-quick').val();
                const quantity = parseInt($('#polar-item-quantity-quick').val()) || 1;

                if (!productId) {
                    alert(polarOrderEdit.i18n.selectProduct || 'Please select a product.');
                    return;
                }

                console.log('Adding item:', productId, quantity);
                orderEdit.addItem(productId, quantity);
            });
        },

        showItemEditor: function ($row) {
            // Store original values
            $row.data('original-html', $row.html());

            const itemId = $row.find('.polar-edit-item').data('item-id');
            const quantity = $row.find('.item-quantity').text().trim();
            const priceText = $row.find('.item-price').text().trim().replace(/[^0-9.]/g, '');
            const totalText = $row.find('.item-total').text().trim().replace(/[^0-9.]/g, '');
            const price = parseFloat(priceText) || (parseFloat(totalText) / parseFloat(quantity));

            const editorHtml = `
                <td class="column-quantity">
                    <input type="number" class="polar-edit-quantity" value="${quantity}" min="1" style="width: 80px;" />
                </td>
                <td class="column-price">
                    <input type="number" class="polar-edit-price" value="${price}" min="0" step="0.01" style="width: 100px;" />
                </td>
                <td class="column-total">
                    <span class="item-total-preview"></span>
                </td>
                <td class="column-actions">
                    <button type="button" class="button button-small polar-save-item" data-item-id="${itemId}">Save</button>
                    <button type="button" class="button button-small polar-cancel-edit">Cancel</button>
                </td>
            `;

            $row.find('td.column-quantity, td.column-price, td.column-total, td.column-actions').replaceWith(editorHtml);
            
            // Calculate total on change
            $row.find('.polar-edit-quantity, .polar-edit-price').on('input', function() {
                const qty = parseFloat($row.find('.polar-edit-quantity').val()) || 0;
                const prc = parseFloat($row.find('.polar-edit-price').val()) || 0;
                $row.find('.item-total-preview').text('$' + (qty * prc).toFixed(2));
            });
        },

        cancelEdit: function ($row) {
            const originalHtml = $row.data('original-html');
            if (originalHtml) {
                $row.html(originalHtml);
                orderEdit.initItemActions();
            }
        },

        saveItem: function ($row) {
            const itemId = $row.find('.polar-save-item').data('item-id');
            const quantity = parseFloat($row.find('.polar-edit-quantity').val());
            const price = parseFloat($row.find('.polar-edit-price').val());

            if (!quantity || quantity < 1) {
                alert(polarOrderEdit.i18n.invalidQuantity || 'Please enter a valid quantity.');
                return;
            }

            orderEdit.updateItem(itemId, quantity, price);
        },

        addItem: function (productId, quantity) {
            $.ajax({
                url: polarOrderEdit.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'polar_add_order_item',
                    nonce: polarOrderEdit.nonce,
                    order_id: polarOrderEdit.orderId,
                    product_id: productId,
                    quantity: quantity,
                },
                success: function (response) {
                    if (response.success) {
                        // Reload page to show updated order
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error adding item.');
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                },
            });
        },

        removeItem: function (itemId) {
            $.ajax({
                url: polarOrderEdit.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'polar_remove_order_item',
                    nonce: polarOrderEdit.nonce,
                    order_id: polarOrderEdit.orderId,
                    item_id: itemId,
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error removing item.');
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                },
            });
        },

        updateItem: function (itemId, quantity, price) {
            $.ajax({
                url: polarOrderEdit.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'polar_update_order_item',
                    nonce: polarOrderEdit.nonce,
                    order_id: polarOrderEdit.orderId,
                    item_id: itemId,
                    quantity: quantity,
                    price: price,
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error updating item.');
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                },
            });
        },

        showReplaceModal: function (itemId) {
            // Simple prompt for now - can be enhanced with a proper modal
            const newProductId = prompt('Enter new product ID:');
            if (newProductId) {
                const quantity = prompt('Enter quantity:', '1') || 1;
                orderEdit.replaceItem(itemId, newProductId, quantity);
            }
        },

        replaceItem: function (itemId, newProductId, quantity) {
            $.ajax({
                url: polarOrderEdit.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'polar_replace_order_item',
                    nonce: polarOrderEdit.nonce,
                    order_id: polarOrderEdit.orderId,
                    item_id: itemId,
                    new_product_id: newProductId,
                    quantity: quantity,
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error replacing item.');
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                },
            });
        },
    };

    // Initialize on document ready
    $(document).ready(function () {
        // Check if polarOrderEdit is defined
        if (typeof polarOrderEdit === 'undefined') {
            console.error('polarOrderEdit is not defined. Make sure the script is properly localized.');
            return;
        }
        
        // Check if jQuery and Select2 are available
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            console.error('jQuery or Select2 is not loaded.');
            // Try to load Select2 if available
            if (typeof $ !== 'undefined' && $.fn.select2) {
                // Select2 is available, continue
            } else {
                return;
            }
        }
        
        orderEdit.init();
        
        // Re-inject buttons after a short delay to ensure items are rendered
        setTimeout(function () {
            orderEdit.injectItemButtons();
        }, 500);
    });

    // Update order total after item changes
    function updateOrderTotal() {
        let total = 0;
        $('.polar-order-item-row').each(function() {
            const totalText = $(this).find('.item-total').text().replace(/[^0-9.]/g, '');
            total += parseFloat(totalText) || 0;
        });
        $('#polar-order-total').text('$' + total.toFixed(2));
    }

})(jQuery);

