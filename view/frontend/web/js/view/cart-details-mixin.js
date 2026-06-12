/**
 * Panth CheckoutExtended — Cart Details Mixin
 *
 * Adds qty increment/decrement and item removal to the checkout sidebar.
 * Handles both guest and customer cart REST API endpoints.
 *
 * Display flags are read from window.checkoutConfig.panthCheckout.cart
 * (injected by Plugin/Cart/ConfigProvider.php). Per-item data (sku,
 * product_url, qty_increments) is read from window.checkoutConfig.quoteItemData,
 * keyed by item_id (injected by Plugin/Cart/QuoteItemPlugin.php).
 */
define([
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Panth_CheckoutExtended/js/action/update-cart-item',
    'Panth_CheckoutExtended/js/action/remove-cart-item',
    'mage/translate'
], function (ko, quote, priceUtils, updateCartItemAction, removeCartItemAction, $t) {
    'use strict';

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

            /**
             * Read display flags from checkoutConfig on init.
             *
             * @returns {Object}
             */
            initialize: function () {
                this._super();

                var cfg = (window.checkoutConfig &&
                    window.checkoutConfig.panthCheckout &&
                    window.checkoutConfig.panthCheckout.cart) || {};

                this.panth_qty_increment = !!cfg.qtyIncrement;
                this.panth_show_sku = !!cfg.showSku;
                this.panth_show_link = !!cfg.showLink;

                return this;
            },

            /**
             * Look up the quoteItemData entry for a totals item by item_id.
             *
             * @param {Object} item
             * @returns {Object}
             */
            getQuoteItem: function (item) {
                var id = item.item_id || item['item_id'];

                return (window.checkoutConfig.quoteItemData || []).find(function (q) {
                    return q.item_id == id; //eslint-disable-line eqeqeq
                }) || {};
            },

            /**
             * Get product URL for the item if link display is enabled.
             *
             * @param {Object} item
             * @returns {String|Boolean}
             */
            getProductUrl: function (item) {
                if (!this.panth_show_link) {
                    return false;
                }

                return this.getQuoteItem(item).product_url || false;
            },

            /**
             * Get item SKU if display is enabled.
             *
             * @param {Object} item
             * @returns {String|Boolean}
             */
            getItemSku: function (item) {
                if (!this.panth_show_sku) {
                    return false;
                }

                return this.getQuoteItem(item).sku || false;
            },

            /**
             * Format the per-unit (single item) price for display.
             *
             * Uses the tax-inclusive unit price when available, otherwise the
             * base unit price. This is intentionally the UNIT price, NOT the
             * row total (qty x unit), so it stays stable when the qty changes —
             * the qty drives the cart subtotal / order total in the footer.
             *
             * @param {Object} item
             * @returns {String}
             */
            getUnitPrice: function (item) {
                var format = (window.checkoutConfig && window.checkoutConfig.priceFormat) || {},
                    raw = (item.price_incl_tax !== undefined && item.price_incl_tax !== null && item.price_incl_tax !== '')
                        ? item.price_incl_tax
                        : item.price,
                    price = parseFloat(raw);

                if (isNaN(price)) {
                    price = 0;
                }

                return priceUtils.formatPrice(price, format);
            },

            /**
             * Whether qty increment/decrement controls should be shown.
             *
             * @returns {Boolean}
             */
            canChangeQty: function () {
                return this.panth_qty_increment === true;
            },

            /**
             * Get the qty increment step for an item.
             * Falls back to 1 if not set.
             *
             * @param {Object} item
             * @returns {Number}
             */
            getQtyStep: function (item) {
                var step = parseFloat(this.getQuoteItem(item).qty_increments);

                return step > 0 ? step : 1;
            },

            /**
             * Validate that the new qty is acceptable.
             *
             * @param {Number} oldQty
             * @param {Number} newQty
             * @returns {Boolean}
             */
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

            /**
             * Increment item quantity by one step.
             *
             * @param {Object} item
             */
            incQty: function (item) {
                var step = this.getQtyStep(item),
                    currentQty = parseFloat(item.qty),
                    newQty = currentQty + step;

                this.applyQty(item, newQty);
            },

            /**
             * Decrement item quantity by one step.
             * Will not go below a single step; if the resulting qty would be
             * 0 or below, the item is removed instead.
             *
             * @param {Object} item
             */
            decQty: function (item) {
                var step = this.getQtyStep(item),
                    currentQty = parseFloat(item.qty),
                    newQty = currentQty - step;

                if (newQty <= 0 || newQty < step) {
                    this.removeItem(item);
                    return;
                }

                this.applyQty(item, newQty);
            },

            /**
             * Apply a new quantity to the cart item via REST API.
             *
             * @param {Object} item
             * @param {Number} newQty
             */
            applyQty: function (item, newQty) {
                var oldQty = parseFloat(item.qty);

                if (!this.isValidQty(oldQty, newQty)) {
                    return;
                }

                updateCartItemAction(item.item_id, newQty);
            },

            /**
             * Remove item from cart with confirmation dialog.
             *
             * @param {Object} item
             */
            removeItem: function (item) {
                if (!confirm($t('Are you sure you want to remove this item from your cart?'))) { //eslint-disable-line no-alert
                    return;
                }

                removeCartItemAction(item.item_id);
            }
        });
    };
});
