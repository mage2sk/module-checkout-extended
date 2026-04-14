/**
 * Panth CheckoutExtended — Remove Cart Item Action
 *
 * DELETEs a cart item via REST API. If the cart becomes empty
 * after removal, reloads the page to redirect away from checkout.
 */
define([
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Customer/js/customer-data'
], function (
    resourceUrlManager,
    quote,
    storage,
    errorProcessor,
    fullScreenLoader,
    getTotalsAction,
    getPaymentInformationAction,
    rateRegistry,
    customerData
) {
    'use strict';

    /**
     * Remove a cart item.
     *
     * @param {Number|String} itemId
     * @returns {jQuery.Deferred}
     */
    return function (itemId) {
        var url = resourceUrlManager.getUrlForRemoveCartItem(itemId);

        fullScreenLoader.startLoader();

        return storage.delete(
            url
        ).done(function (response) {
            // Invalidate local storage sections
            customerData.invalidate(['cart']);

            // Check if cart is now empty — totals items will update after refresh
            var items = quote.getItems(),
                remainingItems;

            if (items) {
                remainingItems = items.filter(function (item) {
                    return parseInt(item.item_id, 10) !== parseInt(itemId, 10);
                });

                if (remainingItems.length === 0) {
                    window.location.reload();
                    return;
                }
            }

            // Cart still has items — refresh rates, payments, totals
            var address = quote.shippingAddress();

            if (address) {
                rateRegistry.set(address.getCacheKey(), null);
            }

            getPaymentInformationAction();
            getTotalsAction([]);
        }).fail(function (response) {
            errorProcessor.process(response);
        }).always(function () {
            fullScreenLoader.stopLoader();
        });
    };
});
