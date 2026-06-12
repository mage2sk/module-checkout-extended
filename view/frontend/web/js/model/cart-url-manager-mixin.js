/**
 * Panth CheckoutExtended — Cart URL Manager Mixin
 *
 * Extends Magento_Checkout/js/model/resource-url-manager with methods
 * for updating and removing cart items via REST API.
 *
 * URLs are built through Magento_Checkout/js/model/url-builder so they
 * resolve to the proper `rest/<storeCode>/V1/...` service path. Building
 * the raw `/V1/...` path (without the rest/<storeCode> prefix) results in
 * a 404 when mage/storage POSTs/DELETEs against it.
 */
define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer'
], function (wrapper, urlBuilder, customer) {
    'use strict';

    // Do NOT require Magento_Checkout/js/model/quote at define time:
    // resource-url-manager (the mixin target) loads on EVERY page including
    // catalog (minicart), where window.checkoutConfig is undefined and quote.js
    // throws on load. We only need the quote id when building a GUEST cart URL
    // during a checkout/cart qty change, so resolve it lazily.
    function getQuoteId() {
        try {
            return require('Magento_Checkout/js/model/quote').getQuoteId();
        } catch (e) {
            return null;
        }
    }

    return function (resourceUrlManager) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return resourceUrlManager;
        }

        /**
         * Build a cart-item REST URL for the current quote.
         * Guest:    rest/<storeCode>/V1/guest-carts/:cartId/items/:itemId
         * Customer: rest/<storeCode>/V1/carts/mine/items/:itemId
         *
         * @param {Number|String} itemId
         * @returns {String}
         */
        function buildCartItemUrl(itemId) {
            if (customer.isLoggedIn()) {
                return urlBuilder.createUrl('/carts/mine/items/:itemId', {
                    itemId: itemId
                });
            }

            return urlBuilder.createUrl('/guest-carts/:cartId/items/:itemId', {
                cartId: getQuoteId(),
                itemId: itemId
            });
        }

        /**
         * Build the URL for updating a cart item qty.
         *
         * @param {Number|String} itemId
         * @returns {String}
         */
        resourceUrlManager.getUrlForUpdateCartItem = function (itemId) {
            return buildCartItemUrl(itemId);
        };

        /**
         * Build the URL for removing a cart item.
         *
         * @param {Number|String} itemId
         * @returns {String}
         */
        resourceUrlManager.getUrlForRemoveCartItem = function (itemId) {
            return buildCartItemUrl(itemId);
        };

        return resourceUrlManager;
    };
});
