define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/get-totals'
], function (wrapper, quote, getTotalsAction) {
    'use strict';

    function skip(deferred) {
        if (deferred && typeof deferred.resolve === 'function') {
            getTotalsAction([], deferred);
        }
    }

    return function (action) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return action;
        }

        return wrapper.wrap(action, function (original, deferred, fromData) {
            var address = quote.shippingAddress();

            if (quote.isVirtual()) {
                return original(deferred, fromData);
            }

            if (!address || !address.countryId || !address.firstname) {
                skip(deferred);

                return;
            }

            if (typeof window.panthShippingInfoSaved === 'function' &&
                !window.panthShippingInfoSaved()) {
                skip(deferred);

                return;
            }

            return original(deferred, fromData);
        });
    };
});
