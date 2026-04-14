/**
 * Panth CheckoutExtended — Get Payment Information Mixin
 *
 * Prevents "The shipping address is missing" error on page load.
 * In one-page checkout the payment step is forced visible immediately,
 * which triggers getPaymentInformation() before any address is entered.
 * Payment methods are already pre-loaded from checkoutConfig.paymentMethods
 * by our PaymentMethodsConfigProvider plugin, so the API call is redundant
 * until the user has actually saved a shipping address.
 */
define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function (wrapper, quote) {
    'use strict';

    return function (action) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return action;
        }

        return wrapper.wrap(action, function (original, deferred, fromData) {
            var address = quote.shippingAddress();

            // Skip if no address yet (guest filling in form)
            if (!address || !address.countryId || !address.firstname) {
                if (deferred && typeof deferred.resolve === 'function') {
                    deferred.resolve();
                }
                return;
            }

            // Skip if setShippingInformation hasn't been called yet.
            // For logged-in users, checkout_init.phtml calls it once on load;
            // until that completes, the server will reject getPaymentInformation
            // with "The shipping address is missing."
            if (typeof window.panthShippingInfoSaved === 'function' &&
                !window.panthShippingInfoSaved()) {
                if (deferred && typeof deferred.resolve === 'function') {
                    deferred.resolve();
                }
                return;
            }

            return original(deferred, fromData);
        });
    };
});
