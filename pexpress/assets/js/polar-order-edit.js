(function ($) {
    'use strict';

    let orderEdit = {
        init: function () {
            this.initItemActions();
            this.initProductSearch();
            this.initModificationHistory();
            this.initAddItemModal();
            this.initForwardToHR();
        },

        initProductSearch: function () {
            this.initSelect2($('.polar-product-select, .wc-product-search'));
        },

        entityDecoder: (function () {
            const textarea = document.createElement('textarea');
            return textarea;
        })(),

        decodeHtmlEntities: function (value) {
            if (typeof value !== 'string') {
                return value || '';
            }
            this.entityDecoder.innerHTML = value;
            return this.entityDecoder.value || value;
        },

        normalizeCurrencyOutput: function (value) {
            if (typeof value !== 'string') {
                return value;
            }
            return value.replace(/\u00a0/g, ' ').trim();
        },

        serializeFormData: function ($form) {
            const result = {};
            if (!$form || !$form.length) {
                return result;
            }

            const fields = $form.serializeArray();
            fields.forEach(function (field) {
                if (Object.prototype.hasOwnProperty.call(result, field.name)) {
                    if (!Array.isArray(result[field.name])) {
                        result[field.name] = [result[field.name]];
                    }
                    result[field.name].push(field.value);
                } else {
                    result[field.name] = field.value;
                }
            });

            $form.find('input[type="checkbox"]:not(:checked)').each(function () {
                const name = this.name;
                if (!name || Object.prototype.hasOwnProperty.call(result, name)) {
                    return;
                }
                result[name] = '';
            });

            return result;
        },

        initSelect2: function (target) {
            if (typeof $.fn.select2 === 'undefined') {
                return;
            }

            const $elements = target && target.jquery ? target : $(target);

            if (!$elements.length) {
                return;
            }

            $elements.each(function () {
                const $select = $(this);

                if (!$select.length || $select.data('select2')) {
                    return;
                }

                let $dropdownParent = $select.closest('.wc-backbone-modal-content');
                if (!$dropdownParent.length) {
                    $dropdownParent = $select.closest('.polar-add-item-section');
                }
                if (!$dropdownParent.length) {
                    $dropdownParent = $select.closest('.polar-order-item');
                }
                if (!$dropdownParent.length) {
                    $dropdownParent = $(document.body);
                }

                const placeholderText = $select.data('placeholder') || (polarOrderEdit.i18n.searchProducts || 'Search for a product...');

                $select.select2({
                    width: '100%',
                    dropdownParent: $dropdownParent,
                    ajax: {
                        url: polarOrderEdit.ajaxUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                action: 'polar_search_products',
                                term: params.term || '',
                                security: polarOrderEdit.wcSearchNonce || '',
                            };
                        },
                        processResults: function (data) {
                            const results = [];
                            if (data && Array.isArray(data)) {
                                data.forEach(function (item) {
                                    if (item && item.id && item.text) {
                                        results.push({ id: item.id, text: item.text });
                                    }
                                });
                            } else if (data && data.success && Array.isArray(data.data)) {
                                data.data.forEach(function (item) {
                                    if (item && item.id && item.text) {
                                        results.push({ id: item.id, text: item.text });
                                    }
                                });
                            } else if (data && typeof data === 'object') {
                                $.each(data, function (id, text) {
                                    if (id && text) {
                                        results.push({ id: id, text: text });
                                    }
                                });
                            }
                            return { results: results };
                        },
                        cache: true,
                    },
                    minimumInputLength: 2,
                    placeholder: placeholderText,
                    allowClear: true,
                });
            });
        },

        initItemActions: function () {
            // Inject action buttons into order items table
            this.injectItemButtons();

            // Handle inline editing buttons on order items - use event delegation
            $(document).off('click', '.polar-edit-item').on('click', '.polar-edit-item', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $row = $(this).closest('tr');
                orderEdit.showItemEditor($row);
            });

            $(document).off('click', '.polar-remove-item').on('click', '.polar-remove-item', function (e) {
                e.preventDefault();
                e.stopPropagation();
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
                const $row = $(this).closest('tr');
                orderEdit.saveItem($row);
            });

            $(document).off('click', '.polar-cancel-edit').on('click', '.polar-cancel-edit', function (e) {
                e.preventDefault();
                e.stopPropagation();
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

        initAddItemModal: function () {
            const self = this;

            $(document)
                .off('click', '.polar-open-add-item')
                .on('click', '.polar-open-add-item', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.openAddItemModal();
                });
        },

        initForwardToHR: function () {
            $(document)
                .off('click', '.polar-forward-to-hr')
                .on('click', '.polar-forward-to-hr', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const $button = $(this);
                    if ($button.prop('disabled')) {
                        return;
                    }

                    const orderId = parseInt($button.data('orderId'), 10);
                    if (!orderId) {
                        return;
                    }

                    const $note = $('#polar-forward-note');
                    const note = $note.length ? $note.val() : '';
                    const $feedback = $('.polar-forward-feedback');
                    const $statusBadge = $('.forward-status-badge');
                    const $forwardMeta = $('.forward-meta').length ? $('.forward-meta') : $('<p class="forward-meta" />').insertBefore($('.forward-label').first());

                    $button.prop('disabled', true).addClass('is-loading');
                    $feedback.removeClass('is-error is-success').text(polarOrderEdit.i18n.forwarding || 'Forwarding to HR...');

                    $.ajax({
                        url: polarOrderEdit.ajaxUrl,
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'polar_forward_order_to_hr',
                            nonce: polarOrderEdit.nonce,
                            order_id: orderId,
                            note: note,
                        },
                    })
                        .done(function (response) {
                            if (!response || !response.success) {
                                const message = response && response.data && response.data.message
                                    ? response.data.message
                                    : (polarOrderEdit.i18n.forwardError || 'Unable to forward order. Please try again.');
                                $feedback.addClass('is-error').text(message);
                                return;
                            }

                            const data = response.data || {};
                            const forwardedMessage = polarOrderEdit.i18n.forwardSuccess || 'Order forwarded to HR.';
                            $feedback.addClass('is-success').text(data.message || forwardedMessage);

                            if ($statusBadge.length) {
                                $statusBadge.removeClass('is-idle').addClass('is-pending').text(polarOrderEdit.i18n.awaitingAssignment || 'Awaiting HR Assignment');
                            }

                            const summary = data.summary || '';
                            if (summary) {
                                $forwardMeta.text(summary);
                            } else {
                                const forwardedBy = data.forwarded_by || '';
                                const forwardedAt = data.forwarded_at || '';
                                let metaText = '';
                                if (forwardedBy && forwardedAt) {
                                    metaText = `${forwardedAt} · ${forwardedBy}`;
                                } else if (forwardedBy) {
                                    metaText = forwardedBy;
                                } else if (forwardedAt) {
                                    metaText = forwardedAt;
                                }
                                if (metaText) {
                                    $forwardMeta.text(metaText);
                                }
                            }

                            const noteValue = typeof data.note === 'string' ? data.note : note;
                            if ($note.length) {
                                $note.val(noteValue);
                            }

                            $button.text(polarOrderEdit.i18n.updateForwarding || 'Update Forwarding');
                        })
                        .fail(function () {
                            $feedback.addClass('is-error').text(polarOrderEdit.i18n.forwardError || 'Unable to forward order. Please try again.');
                        })
                        .always(function () {
                            $button.prop('disabled', false).removeClass('is-loading');
                        });
                });
        },

        openAddItemModal: function () {
            const self = this;

            if (typeof $.fn.WCBackboneModal !== 'function') {
                console.error('WCBackboneModal is unavailable.');
                return;
            }

            $(document.body).off('.polarAddModal');

            const onModalLoaded = function (event, target) {
                if (target !== 'wc-modal-add-products') {
                    return;
                }

                const $modal = $('.wc-backbone-modal');
                $modal.addClass('polar-order-modal');
                $modal.find('.wc-backbone-modal-content').addClass('polar-order-modal__content');
                const $table = $modal.find('table.widefat');
                const $tbody = $table.find('tbody');
                const rowTemplate = $tbody.data('row');

                self.initSelect2($modal.find('.wc-product-search'));
                $(document.body).trigger('wc-enhanced-select-init');

                $modal.find('.quantity').attr({
                    min: 1,
                    step: 1,
                }).each(function () {
                    if (!$(this).val()) {
                        $(this).val(1);
                    }
                });

                $modal.off('click.polarAddModal', '.polar-modal-submit').on('click.polarAddModal', '.polar-modal-submit', function (event) {
                    event.preventDefault();
                    const $form = $modal.find('.polar-modal-add-product-form');
                    const formData = orderEdit.serializeFormData($form);
                    $(document.body).trigger('wc_backbone_modal_response', ['wc-modal-add-products', formData]);
                    $modal.find('.modal-close').first().trigger('click');
                });

                if (rowTemplate) {
                    $modal.off('change.polarAddModal', '.wc-product-search').on('change.polarAddModal', '.wc-product-search', function () {
                        const $row = $(this).closest('tr');
                        if (!$row.is(':last-child')) {
                            return;
                        }
                        const index = $tbody.find('tr').length;
                        const newRow = rowTemplate.replace(/\[0\]/g, '[' + index + ']');
                        $tbody.append('<tr>' + newRow + '</tr>');
                        const $newSelect = $tbody.find('tr:last .wc-product-search');
                        self.initSelect2($newSelect);
                        $(document.body).trigger('wc-enhanced-select-init');
                        $tbody.find('tr:last .quantity').attr({ min: 1, step: 1 }).val(1);
                    });
                }
            };

            const onModalResponse = function (event, target, data) {
                if (target !== 'wc-modal-add-products') {
                    return;
                }
                self.handleAddProductsData(data || {});
            };

            const onModalRemoved = function (event, target) {
                if (target !== 'wc-modal-add-products') {
                    return;
                }
                $(document.body).off('.polarAddModal');
            };

            $(document.body)
                .on('wc_backbone_modal_loaded.polarAddModal', onModalLoaded)
                .on('wc_backbone_modal_response.polarAddModal', onModalResponse)
                .on('wc_backbone_modal_removed.polarAddModal', onModalRemoved);

            $(document.body).WCBackboneModal({ template: 'wc-modal-add-products' });
        },

        handleAddProductsData: function (data) {
            $(document.body).off('.polarAddModal');
            $('.wc-backbone-modal').off('change.polarAddModal', '.wc-product-search');

            const ids = Array.isArray(data.item_id) ? data.item_id : (data.item_id ? [data.item_id] : []);
            const qtyInput = Array.isArray(data.item_qty) ? data.item_qty : (data.item_qty ? [data.item_qty] : []);
            const items = [];

            ids.forEach(function (id, index) {
                const productId = parseInt(id, 10);
                if (!productId) {
                    return;
                }
                const rawQty = qtyInput[index] !== undefined ? qtyInput[index] : qtyInput[0];
                const parsedQty = parseInt(rawQty, 10);
                const quantity = Number.isFinite(parsedQty) && parsedQty > 0 ? parsedQty : 1;
                items.push({ id: productId, qty: quantity });
            });

            if (!items.length) {
                alert(polarOrderEdit.i18n.selectProduct || 'Please select a product.');
                return;
            }

            this.addItemsSequential(items);
        },

        addItemsSequential: function (items) {
            const self = this;
            const queue = Array.isArray(items) ? items.slice() : [];
            let hadError = false;

            const processNext = function () {
                if (!queue.length) {
                    if (!hadError) {
                        location.reload();
                    }
                    return;
                }

                const current = queue.shift();
                self.addItem(current.id, current.qty, {
                    onSuccess: function () {
                        processNext();
                    },
                    onError: function (response) {
                        hadError = true;
                        const message = response && response.data && response.data.message
                            ? response.data.message
                            : (polarOrderEdit.i18n.addProductError || 'Error adding item.');
                        alert(message);
                    },
                    onComplete: function () {
                        if (hadError) {
                            queue.length = 0;
                        }
                    },
                });
            };

            processNext();
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
            const rawSymbol = currency.symbol || '$';
            const symbol = this.decodeHtmlEntities(rawSymbol);

            const formatted = priceFormat.replace('%1$s', symbol).replace('%2$s', formattedNumber);
            return this.normalizeCurrencyOutput(this.decodeHtmlEntities(formatted));
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

        addItem: function (productId, quantity, callbacks) {
            const options = callbacks || {};
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
                beforeSend: function () {
                    if (typeof options.beforeSend === 'function') {
                        options.beforeSend();
                    }
                },
                success: function (response) {
                    if (response && response.success) {
                        if (typeof options.onSuccess === 'function') {
                            options.onSuccess(response);
                        } else {
                            location.reload();
                        }
                    } else {
                        if (typeof options.onError === 'function') {
                            options.onError(response);
                        } else {
                            const message = response && response.data && response.data.message
                                ? response.data.message
                                : (polarOrderEdit.i18n.addProductError || 'Error adding item.');
                            alert(message);
                        }
                    }
                },
                error: function () {
                    if (typeof options.onError === 'function') {
                        options.onError();
                    } else {
                        alert(polarOrderEdit.i18n.genericError || 'An error occurred. Please try again.');
                    }
                },
                complete: function () {
                    if (typeof options.onComplete === 'function') {
                        options.onComplete();
                    }
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
        $('#polar-order-total').text(orderEdit.formatCurrency(total));
    }

})(jQuery);

