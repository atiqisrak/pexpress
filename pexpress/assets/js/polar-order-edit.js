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

            const $select = $(selector);
            const $dropdownParent = $select.closest('.polar-add-item-section').length
                ? $select.closest('.polar-add-item-section')
                : ($select.closest('.polar-order-item').length ? $select.closest('.polar-order-item') : $(document.body));

            $select.select2({
                width: '100%',
                dropdownParent: $dropdownParent,
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
                    error: function (xhr, status, error) {
                        console.error('Product search error:', error);
                        return {
                            results: []
                        };
                    },
                    cache: true,
                },
                minimumInputLength: 2,
                placeholder: polarOrderEdit.i18n.searchProducts || 'Search for a product...',
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
            $(document)
                .off('click', '.polar-toggle-history')
                .on('click', '.polar-toggle-history', function (e) {
                    e.preventDefault();
                    const $toggle = $(this);
                    const $container = $toggle.closest('.polar-order-item');
                    let $content = $container.find('.polar-history-content').first();

                    if (!$content.length) {
                        $content = $toggle.closest('.polar-order-edit-dashboard').find('.polar-history-content').first();
                    }

                    if ($content.length) {
                        if ($content.hasClass('is-hidden')) {
                            $content.removeClass('is-hidden').hide();
                        }
                        $content.slideToggle(200, function () {
                            $content.toggleClass('is-hidden', !$content.is(':visible'));
                        });
                        $toggle.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
                    }
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

        formatCurrency: function (amount) {
            if (!isFinite(amount)) {
                return '';
            }

            const currency = polarOrderEdit.currency || {};
            const decimals = typeof currency.decimals === 'number' ? currency.decimals : 2;
            const locale = currency.locale ? currency.locale.replace(/_/g, '-') : undefined;
            const formattedNumber = amount.toLocaleString(locale, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });
            const priceFormat = currency.price_format || '%1$s%2$s';
            const symbol = currency.symbol || '$';

            return priceFormat.replace('%1$s', symbol).replace('%2$s', formattedNumber);
        },

        showItemEditor: function ($row) {
            if ($row.hasClass('is-editing')) {
                return;
            }

            $('.polar-order-item-row.is-editing').not($row).each(function () {
                orderEdit.cancelEdit($(this));
            });

            if (!$row.data('original-html')) {
                $row.data('original-html', $row.html());
            }

            $row.addClass('is-editing');

            const itemId = $row.data('item-id');
            const decimals = polarOrderEdit.currency && typeof polarOrderEdit.currency.decimals === 'number'
                ? polarOrderEdit.currency.decimals
                : 2;
            const quantityAttr = parseFloat($row.data('quantity'));
            const priceAttr = parseFloat($row.data('unitPrice'));
            const totalAttr = parseFloat($row.data('lineTotal'));

            const fallbackQuantity = parseFloat($row.find('.item-quantity').first().text()) || 1;
            const quantity = Number.isFinite(quantityAttr) && quantityAttr > 0 ? quantityAttr : fallbackQuantity;

            const fallbackTotal = Number.isFinite(totalAttr) ? totalAttr : quantity * (parseFloat($row.find('.item-price').first().text().replace(/[^0-9.\-]/g, '')) || 0);
            const fallbackPrice = quantity ? fallbackTotal / quantity : 0;
            const unitPrice = Number.isFinite(priceAttr) ? priceAttr : fallbackPrice;
            const priceStep = decimals > 0 ? Math.pow(10, -decimals) : 1;

            const $quantityCells = $row.find('td.column-quantity');
            const $priceCells = $row.find('td.column-price');
            const $totalCells = $row.find('td.column-total');
            const $actionCells = $row.find('td.column-actions');

            const $quantityCell = $quantityCells.first();
            const $priceCell = $priceCells.first();
            const $totalCell = $totalCells.first();
            const $actionCell = $actionCells.first();

            $quantityCells.not($quantityCell).remove();
            $priceCells.not($priceCell).remove();
            $totalCells.not($totalCell).remove();
            $actionCells.not($actionCell).remove();

            $quantityCell.empty().append(
                $('<label>', {
                    class: 'screen-reader-text',
                    for: `polar-edit-quantity-${itemId}`,
                    text: polarOrderEdit.i18n.quantityLabel || 'Quantity',
                }),
                $('<input>', {
                    type: 'number',
                    id: `polar-edit-quantity-${itemId}`,
                    class: 'polar-edit-quantity',
                    value: quantity,
                    min: 1,
                    step: 1,
                    css: { width: '100px' },
                })
            );

            $priceCell.empty().append(
                $('<label>', {
                    class: 'screen-reader-text',
                    for: `polar-edit-price-${itemId}`,
                    text: polarOrderEdit.i18n.priceLabel || 'Price',
                }),
                $('<input>', {
                    type: 'number',
                    id: `polar-edit-price-${itemId}`,
                    class: 'polar-edit-price',
                    value: Number.isFinite(unitPrice) ? unitPrice.toFixed(decimals) : (0).toFixed(decimals),
                    min: 0,
                    step: priceStep,
                    css: { width: '120px' },
                })
            );

            const $totalPreview = $('<span>', { class: 'item-total-preview' });
            $totalCell.empty().append($totalPreview);

            $actionCell.empty().append(
                $('<div>', { class: 'polar-item-actions' }).append(
                    $('<button>', {
                        type: 'button',
                        class: 'button button-small button-primary polar-save-item',
                        'data-item-id': itemId,
                        text: polarOrderEdit.i18n.saveItem || 'Save',
                    }),
                    $('<button>', {
                        type: 'button',
                        class: 'button button-small polar-cancel-edit',
                        text: polarOrderEdit.i18n.cancelEdit || 'Cancel',
                    })
                )
            );

            const updatePreview = function () {
                const qty = parseFloat($quantityCell.find('.polar-edit-quantity').val()) || 0;
                const price = parseFloat($priceCell.find('.polar-edit-price').val()) || 0;
                $totalPreview.text(orderEdit.formatCurrency(qty * price));
            };

            $quantityCell.find('.polar-edit-quantity').on('input', updatePreview);
            $priceCell.find('.polar-edit-price').on('input', updatePreview);
            updatePreview();
        },

        cancelEdit: function ($row) {
            const originalHtml = $row.data('original-html');
            if (originalHtml) {
                $row.html(originalHtml);
                orderEdit.initItemActions();
            }
            $row.removeClass('is-editing');
            $row.removeData('original-html');
        },

        saveItem: function ($row) {
            const itemId = $row.find('.polar-save-item').data('item-id') || $row.data('item-id');
            const quantityValue = $row.find('.polar-edit-quantity').val();
            const priceValue = $row.find('.polar-edit-price').val();

            const quantity = parseInt(quantityValue, 10);
            const price = priceValue === '' ? null : parseFloat(priceValue);

            if (!Number.isInteger(quantity) || quantity < 1) {
                alert(polarOrderEdit.i18n.invalidQuantity || 'Please enter a valid quantity.');
                return;
            }

            if (price !== null && (!isFinite(price) || price < 0)) {
                alert(polarOrderEdit.i18n.invalidPrice || 'Please enter a valid price.');
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
            const data = {
                action: 'polar_update_order_item',
                nonce: polarOrderEdit.nonce,
                order_id: polarOrderEdit.orderId,
                item_id: itemId,
            };

            if (Number.isInteger(quantity)) {
                data.quantity = quantity;
            }

            if (price !== null && price !== undefined && isFinite(price)) {
                const decimals = polarOrderEdit.currency && typeof polarOrderEdit.currency.decimals === 'number'
                    ? polarOrderEdit.currency.decimals
                    : 2;
                data.price = parseFloat(price.toFixed(decimals));
            }

            $.ajax({
                url: polarOrderEdit.ajaxUrl,
                type: 'POST',
                data: data,
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
        $('.polar-order-item-row').each(function () {
            const totalText = $(this).find('.item-total').text().replace(/[^0-9.]/g, '');
            total += parseFloat(totalText) || 0;
        });
        $('#polar-order-total').text('$' + total.toFixed(2));
    }

})(jQuery);

