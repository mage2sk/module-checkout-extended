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

    function currentItems() {
        var totals = quote.totals();

        if (totals && Array.isArray(totals.items)) {
            return totals.items;
        }

        return quote.getItems() || [];
    }

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

        return done.promise();
    }

    return function (itemId) {
        var result = $.Deferred(),
            url = resourceUrlManager.getUrlForRemoveCartItem(itemId);

        storage.delete(
            url,
            false
        ).done(function (response) {
            var remaining = currentItems().filter(function (item) {
                return String(item.item_id) !== String(itemId);
            });

            customerData.invalidate(['cart']);

            if (remaining.length === 0) {
                window.location.reload();
                result.resolve(response);

                return;
            }

            refreshTotals().always(function () {
                result.resolve(response);
            });
        }).fail(function (response) {
            errorProcessor.process(response);
            result.reject(response);
        });

        return result.promise();
    };
});
