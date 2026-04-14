/**
 * Panth CheckoutExtended — Update Cart Item Action
 *
 * POSTs to REST API to update cart item quantity, then refreshes
 * shipping rates, payment methods, and cart totals.
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
     * Update a cart item's quantity.
     *
     * @param {Number|String} itemId
     * @param {Number} qty
     * @returns {jQuery.Deferred}
     */
    return function (itemId, qty) {
        var url = resourceUrlManager.getUrlForUpdateCartItem(itemId),
            payload = {
                cartItem: {
                    item_id: itemId,
                    qty: qty,
                    quote_id: quote.getQuoteId()
                }
            };

        fullScreenLoader.startLoader();

        return storage.put(
            url,
            JSON.stringify(payload)
        ).done(function () {
            // Invalidate cached shipping rates so they are re-fetched
            var address = quote.shippingAddress();

            if (address) {
                rateRegistry.set(address.getCacheKey(), null);
            }

            // Refresh totals and payment information
            getPaymentInformationAction();
            getTotalsAction([]);

            // Invalidate local storage customer data sections
            customerData.invalidate(['cart']);
        }).fail(function (response) {
            errorProcessor.process(response);
        }).always(function () {
            fullScreenLoader.stopLoader();
        });
    };
});
