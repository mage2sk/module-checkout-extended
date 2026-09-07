define([
    'jquery',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Customer/js/customer-data'
], function (
    $,
    resourceUrlManager,
    quote,
    storage,
    errorProcessor,
    getTotalsAction,
    getPaymentInformationAction,
    rateRegistry,
    customerData
) {
    'use strict';

    function refreshTotals() {
        var done = $.Deferred(),
            paymentDeferred = $.Deferred(),
            address = quote.shippingAddress();

        if (address && typeof address.getCacheKey === 'function') {
            rateRegistry.set(address.getCacheKey(), null);
        }

        paymentDeferred.done(function () {
            done.resolve();
        }).fail(function () {
            var totalsDeferred = $.Deferred();

            totalsDeferred.always(function () {
                done.resolve();
            });

            try {
                getTotalsAction([], totalsDeferred);
            } catch (e) {
                totalsDeferred.reject();
            }
        });

        try {
            getPaymentInformationAction(paymentDeferred);
        } catch (e) {
            paymentDeferred.reject();
        }

        customerData.invalidate(['cart']);

        return done.promise();
    }

    return function (itemId, qty) {
        var result = $.Deferred(),
            url = resourceUrlManager.getUrlForUpdateCartItem(itemId),
            payload = {
                cartItem: {
                    item_id: itemId,
                    qty: qty,
                    quote_id: quote.getQuoteId()
                }
            };

        storage.put(
            url,
            JSON.stringify(payload),
            false
        ).done(function (response) {
            refreshTotals().always(function () {
                result.resolve(response);
            });
        }).fail(function (response) {
            if (response && String(response.status) === '401') {
                errorProcessor.process(response);
            }

            result.reject(response);
        });

        return result.promise();
    };
});
