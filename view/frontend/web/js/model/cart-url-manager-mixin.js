/**
 * Panth CheckoutExtended — Cart URL Manager Mixin
 *
 * Extends Magento_Checkout/js/model/resource-url-manager with methods
 * for updating and removing cart items via REST API.
 */
define([
    'mage/utils/wrapper',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote'
], function (wrapper, customer, quote) {
    'use strict';

    return function (resourceUrlManager) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return resourceUrlManager;
        }

        /**
         * Build the URL for updating a cart item qty.
         * Guest:    /V1/guest-carts/:cartId/items/:itemId
         * Customer: /V1/carts/mine/items/:itemId
         *
         * @param {Number|String} itemId
         * @returns {String}
         */
        resourceUrlManager.getUrlForUpdateCartItem = function (itemId) {
            var params = {},
                urls = {
                    'guest': '/V1/guest-carts/' + quote.getQuoteId() + '/items/' + itemId,
                    'customer': '/V1/carts/mine/items/' + itemId
                };

            return customer.isLoggedIn() ? urls.customer : urls.guest;
        };

        /**
         * Build the URL for removing a cart item.
         * Guest:    /V1/guest-carts/:cartId/items/:itemId
         * Customer: /V1/carts/mine/items/:itemId
         *
         * @param {Number|String} itemId
         * @returns {String}
         */
        resourceUrlManager.getUrlForRemoveCartItem = function (itemId) {
            var urls = {
                'guest': '/V1/guest-carts/' + quote.getQuoteId() + '/items/' + itemId,
                'customer': '/V1/carts/mine/items/' + itemId
            };

            return customer.isLoggedIn() ? urls.customer : urls.guest;
        };

        return resourceUrlManager;
    };
});
