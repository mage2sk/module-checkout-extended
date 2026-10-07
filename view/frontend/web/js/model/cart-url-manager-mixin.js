define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer'
], function (wrapper, urlBuilder, customer) {
    'use strict';

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

        resourceUrlManager.getUrlForUpdateCartItem = function (itemId) {
            return buildCartItemUrl(itemId);
        };

        resourceUrlManager.getUrlForRemoveCartItem = function (itemId) {
            return buildCartItemUrl(itemId);
        };

        return resourceUrlManager;
    };
});
