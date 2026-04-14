/**
 * Panth CheckoutExtended — Checkout Initializer
 *
 * Pre-loads payment methods from window.checkoutConfig on page load
 * so they're available immediately in one-page checkout mode
 * (without requiring shipping info to be saved first).
 */
define([
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-converter'
], function (paymentService, methodConverter) {
    'use strict';

    return function () {
        var checkoutConfig = window.checkoutConfig;

        if (!checkoutConfig) {
            return;
        }

        // Pre-populate payment methods from initial checkout config
        if (checkoutConfig.paymentMethods && checkoutConfig.paymentMethods.length) {
            var currentMethods = paymentService.getAvailablePaymentMethods();

            // Only set if not already populated (avoid overwriting API response)
            if (!currentMethods || !currentMethods.length) {
                paymentService.setPaymentMethods(
                    methodConverter(checkoutConfig.paymentMethods)
                );
            }
        }
    };
});
