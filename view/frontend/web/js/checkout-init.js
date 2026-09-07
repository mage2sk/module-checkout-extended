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

        if (checkoutConfig.paymentMethods && checkoutConfig.paymentMethods.length) {
            var currentMethods = paymentService.getAvailablePaymentMethods();

            if (!currentMethods || !currentMethods.length) {
                paymentService.setPaymentMethods(
                    methodConverter(checkoutConfig.paymentMethods)
                );
            }
        }
    };
});
