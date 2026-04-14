/**
 * Panth CheckoutExtended — Cart Details Mixin
 *
 * Adds qty increment/decrement and item removal to the checkout sidebar.
 * Handles both guest and customer cart REST API endpoints.
 */
define([
    'ko',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Panth_CheckoutExtended/js/action/update-cart-item',
    'Panth_CheckoutExtended/js/action/remove-cart-item',
    'mage/translate'
], function (ko, urlBuilder, quote, updateCartItemAction, removeCartItemAction, $t) {
    'use strict';

    return function (Component) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return Component;
        }

        return Component.extend({
            defaults: {
                panth_qty_increment: true,
                panth_show_sku: false,
                panth_show_link: false
            },

            /**
             * Get product URL for the item.
             *
             * @param {Object} item
             * @returns {String|Boolean}
             */
            getProductUrl: function (item) {
                if (!this.panth_show_link || !item.product || !item.product.request_path) {
                    return false;
                }
                return urlBuilder.build(item.product.request_path);
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
                return item.sku || false;
            },

            /**
             * Whether qty increment/decrement controls should be shown.
             *
             * @returns {Boolean}
             */
            canChangeQty: function () {
                return this.panth_qty_increment;
            },

            /**
             * Get the qty increment step for an item.
             * Falls back to 1 if not set.
             *
             * @param {Object} item
             * @returns {Number}
             */
            getQtyStep: function (item) {
                var step = 1;

                if (item && item.qty_increments) {
                    step = parseFloat(item.qty_increments);
                }

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
             * If the resulting qty is 0 or below, remove the item instead.
             *
             * @param {Object} item
             */
            decQty: function (item) {
                var step = this.getQtyStep(item),
                    currentQty = parseFloat(item.qty),
                    newQty = currentQty - step;

                if (newQty <= 0) {
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
                if (!confirm($t('Are you sure you want to remove this item from your cart?'))) {
                    return;
                }

                removeCartItemAction(item.item_id);
            }
        });
    };
});
