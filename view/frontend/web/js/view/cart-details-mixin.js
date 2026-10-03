define([
    'jquery',
    'ko',
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Panth_CheckoutExtended/js/action/update-cart-item',
    'Panth_CheckoutExtended/js/action/remove-cart-item',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function ($, ko, _, quote, priceUtils, updateCartItemAction, removeCartItemAction, confirm, $t) {
    'use strict';

    var BODY_SAVING_CLASS = 'panth-qty-saving',
        FALLBACK_ERROR = 'The requested quantity is not available.',
        qtyMap = {},
        busyMap = {},
        errorMap = {},
        stateMap = {},
        warnedPairs = {},
        inFlightCount = 0,
        totalsSubscribed = false;

    function toNumber(value) {
        var n = parseFloat(value);

        return isNaN(n) ? null : n;
    }

    function totalsItems() {
        var totals = quote.totals();

        if (totals && _.isArray(totals.items)) {
            return totals.items;
        }

        return [];
    }

    function configItems() {
        var cfg = window.checkoutConfig || {};

        if (cfg.totalsData && _.isArray(cfg.totalsData.items)) {
            return cfg.totalsData.items;
        }

        return [];
    }

    function findById(list, id) {
        return _.find(list, function (row) {
            return row && String(row.item_id) === String(id);
        });
    }

    function serverQty(id, fallbackItem) {
        var row = findById(totalsItems(), id) || findById(configItems(), id),
            qty = row ? toNumber(row.qty) : null;

        if (qty === null && fallbackItem) {
            qty = toNumber(fallbackItem.qty);
        }

        return qty === null ? 0 : qty;
    }

    function state(id) {
        if (!stateMap[id]) {
            stateMap[id] = {
                inFlight: false,
                target: null,
                confirmed: null
            };
        }

        return stateMap[id];
    }

    function setBodySaving(delta) {
        inFlightCount = Math.max(0, inFlightCount + delta);

        if (inFlightCount > 0) {
            document.body.classList.add(BODY_SAVING_CLASS);
        } else {
            document.body.classList.remove(BODY_SAVING_CLASS);
        }
    }

    function formatServerMessage(response) {
        var data = response && response.responseJSON,
            message,
            params;

        if (!data && response && response.responseText) {
            try {
                data = JSON.parse(response.responseText);
            } catch (e) {
                data = null;
            }
        }

        if (!data || !data.message) {
            return $t(FALLBACK_ERROR);
        }

        message = String(data.message);
        params = data.parameters;

        if (_.isArray(params)) {
            _.each(params, function (value, index) {
                message = message.split('%' + (index + 1)).join(String(value));
            });
        } else if (_.isObject(params)) {
            _.each(params, function (value, key) {
                message = message.split('%' + key).join(String(value));
            });
        }

        return message;
    }

    function resyncFromTotals() {
        _.each(totalsItems(), function (row) {
            var id = row && row.item_id,
                st,
                qty;

            if (!id || !qtyMap[id]) {
                return;
            }

            st = state(id);
            qty = toNumber(row.qty);

            if (qty === null || st.inFlight || st.target !== null) {
                return;
            }

            st.confirmed = qty;

            if (qtyMap[id]() !== qty) {
                qtyMap[id](qty);
            }
        });
    }

    function checkSubtotal() {
        var totals = quote.totals(),
            items,
            mode,
            inclTax,
            sum = 0,
            reported,
            tolerance,
            key;

        try {
            if (!totals || !_.isArray(totals.items) || !totals.items.length) {
                return;
            }

            items = totals.items;
            mode = window.checkoutConfig ? window.checkoutConfig.reviewTotalsDisplayMode : null;
            inclTax = mode === 'including';

            _.each(items, function (row) {
                var value = inclTax ? row.row_total_incl_tax : row.row_total;

                if (value === undefined || value === null || value === '') {
                    value = row.row_total;
                }

                sum += toNumber(value) || 0;
            });

            reported = toNumber(inclTax ? totals.subtotal_incl_tax : totals.subtotal);

            if (reported === null) {
                reported = toNumber(totals.subtotal);
            }

            if (reported === null) {
                return;
            }

            sum = Math.round(sum * 100) / 100;
            tolerance = 0.01 * items.length;

            if (Math.abs(sum - reported) <= tolerance + 0.000001) {
                return;
            }

            key = reported + '|' + sum;

            if (warnedPairs[key]) {
                return;
            }

            warnedPairs[key] = true;
            console.warn('[panth-checkout] order summary subtotal ' + reported +
                ' differs from the sum of the line items ' + sum);
        } catch (e) {
            return;
        }
    }

    function checkSegments() {
        var totals = quote.totals(),
            seen = {};

        try {
            if (!totals || !_.isArray(totals['total_segments'])) {
                return;
            }

            _.each(totals['total_segments'], function (segment) {
                var code = segment && segment.code ? String(segment.code) : '(no code)',
                    title = segment && segment.title !== undefined && segment.title !== null ?
                        String(segment.title).trim() : '',
                    value = segment ? toNumber(segment.value) : null,
                    rounded,
                    key;

                if (!segment || value === null || value === 0 || code === 'grand_total') {
                    return;
                }

                rounded = String(Math.round(value * 100) / 100);

                if (title === '') {
                    key = 'untitled|' + code + '|' + rounded;

                    if (!warnedPairs[key]) {
                        warnedPairs[key] = true;
                        console.warn('[panth-checkout] total segment "' + code + '" (' + rounded +
                            ') has no title and is not rendered');
                    }
                }

                if (seen[rounded] && seen[rounded] !== code) {
                    key = 'dup|' + seen[rounded] + '|' + code + '|' + rounded;

                    if (!warnedPairs[key]) {
                        warnedPairs[key] = true;
                        console.warn('[panth-checkout] total segments "' + seen[rounded] + '" and "' + code +
                            '" carry the same value ' + rounded);
                    }

                    return;
                }

                seen[rounded] = code;
            });
        } catch (e) {
            return;
        }
    }

    function subscribeOnce() {
        if (totalsSubscribed) {
            return;
        }

        totalsSubscribed = true;
        quote.totals.subscribe(function () {
            resyncFromTotals();
            checkSubtotal();
            checkSegments();
        });
        checkSubtotal();
        checkSegments();
    }

    return function (Component) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return Component;
        }

        return Component.extend({
            defaults: {
                panth_qty_increment: false,
                panth_show_sku: false,
                panth_show_link: false
            },

            initialize: function () {
                var cfg;

                this._super();

                cfg = (window.checkoutConfig &&
                    window.checkoutConfig.panthCheckout &&
                    window.checkoutConfig.panthCheckout.cart) || {};

                this.panth_qty_increment = !!cfg.qtyIncrement;
                this.panth_show_sku = !!cfg.showSku;
                this.panth_show_link = !!cfg.showLink;

                subscribeOnce();

                return this;
            },

            getQuoteItem: function (item) {
                var id = item.item_id || item['item_id'];

                return _.find(window.checkoutConfig.quoteItemData || [], function (q) {
                    return String(q.item_id) === String(id);
                }) || {};
            },

            getProductUrl: function (item) {
                if (!this.panth_show_link) {
                    return false;
                }

                return this.getQuoteItem(item).product_url || false;
            },

            getItemSku: function (item) {
                if (!this.panth_show_sku) {
                    return false;
                }

                return this.getQuoteItem(item).sku || false;
            },

            getUnitPrice: function (item) {
                var format = (window.checkoutConfig && window.checkoutConfig.priceFormat) || {},
                    raw = item.price_incl_tax !== undefined && item.price_incl_tax !== null && item.price_incl_tax !== '' ?
                        item.price_incl_tax :
                        item.price,
                    price = parseFloat(raw);

                if (isNaN(price)) {
                    price = 0;
                }

                return priceUtils.formatPrice(price, format);
            },

            canChangeQty: function () {
                return this.panth_qty_increment === true;
            },

            getQtyStep: function (item) {
                var step = parseFloat(this.getQuoteItem(item).qty_increments);

                return step > 0 ? step : 1;
            },

            isValidQty: function (oldQty, newQty) {
                newQty = parseFloat(newQty);

                if (isNaN(newQty) || newQty < 0) {
                    return false;
                }

                if (newQty === parseFloat(oldQty)) {
                    return false;
                }

                return true;
            },

            qtyFor: function (item) {
                var id = item.item_id,
                    st;

                if (!qtyMap[id]) {
                    st = state(id);
                    st.confirmed = serverQty(id, item);
                    qtyMap[id] = ko.observable(st.confirmed);
                }

                return qtyMap[id];
            },

            busyFor: function (item) {
                var id = item.item_id;

                if (!busyMap[id]) {
                    busyMap[id] = ko.observable(false);
                }

                return busyMap[id];
            },

            errorFor: function (item) {
                var id = item.item_id;

                if (!errorMap[id]) {
                    errorMap[id] = ko.observable('');
                }

                return errorMap[id];
            },

            incQty: function (item) {
                var step = this.getQtyStep(item),
                    obs = this.qtyFor(item);

                this.applyQty(item, obs() + step);
            },

            decQty: function (item, event) {
                var step = this.getQtyStep(item),
                    obs = this.qtyFor(item),
                    newQty = obs() - step;

                if (newQty <= 0 || newQty < step) {
                    this.removeItem(item, event);

                    return;
                }

                this.applyQty(item, newQty);
            },

            applyQty: function (item, newQty) {
                var id = item.item_id,
                    obs = this.qtyFor(item),
                    st = state(id);

                newQty = parseFloat(newQty);

                if (!this.isValidQty(obs(), newQty)) {
                    return;
                }

                obs(newQty);
                st.target = newQty;

                if (st.inFlight) {
                    return;
                }

                this.flushQty(item);
            },

            flushQty: function (item) {
                var self = this,
                    id = item.item_id,
                    obs = this.qtyFor(item),
                    busy = this.busyFor(item),
                    error = this.errorFor(item),
                    st = state(id),
                    target = st.target;

                st.target = null;

                if (target === null || target === st.confirmed) {
                    st.inFlight = false;
                    busy(false);
                    resyncFromTotals();

                    return;
                }

                st.inFlight = true;
                busy(true);
                setBodySaving(1);

                updateCartItemAction(id, target).done(function () {
                    st.confirmed = target;
                    error('');
                }).fail(function (response) {
                    st.target = null;
                    obs(st.confirmed);
                    error(formatServerMessage(response));
                }).always(function () {
                    st.inFlight = false;
                    setBodySaving(-1);

                    if (st.target !== null && st.target !== st.confirmed) {
                        self.flushQty(item);

                        return;
                    }

                    st.target = null;
                    busy(false);
                    resyncFromTotals();
                });
            },

            removeItem: function (item, event) {
                var opener = event && event.currentTarget ? event.currentTarget : document.activeElement;

                confirm({
                    title: $t('Remove item'),
                    content: $t('Are you sure you want to remove this item from your cart?'),
                    modalClass: 'confirm panth-confirm-modal',
                    buttons: [{
                        text: $t('Cancel'),
                        class: 'action-secondary action-dismiss',
                        click: function (e) {
                            this.closeModal(e);
                        }
                    }, {
                        text: $t('Remove'),
                        class: 'action-primary action-accept',
                        click: function (e) {
                            this.closeModal(e, true);
                        }
                    }],
                    actions: {
                        confirm: function () {
                            removeCartItemAction(item.item_id);
                        },
                        always: function () {
                            _.defer(function () {
                                if (opener && opener.isConnected && typeof opener.focus === 'function') {
                                    opener.focus();
                                }
                            });
                        }
                    }
                });
            }
        });
    };
});
