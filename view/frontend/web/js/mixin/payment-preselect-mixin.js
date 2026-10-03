define([
    'underscore',
    'mage/utils/wrapper',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-list',
    'Magento_Checkout/js/checkout-data'
], function (_, wrapper, selectPaymentMethodAction, paymentService, methodList, checkoutData) {
    'use strict';

    return function (checkoutDataResolver) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return checkoutDataResolver;
        }

        var preselected = false;

        function tryPreselect() {
            if (preselected) {
                return true;
            }

            var cfg = (window.checkoutConfig.panthCheckout &&
                    window.checkoutConfig.panthCheckout.payment) || {},
                code = cfg.defaultMethod;

            if (!code || checkoutData.getSelectedPaymentMethod()) {
                preselected = true;
                return true;
            }

            var available = _.find(
                paymentService.getAvailablePaymentMethods(),
                function (method) {
                    return method && method.method === code;
                }
            );

            if (!available) {
                return false;
            }

            selectPaymentMethodAction({
                method: code
            });
            checkoutData.setSelectedPaymentMethod(code);
            preselected = true;

            return true;
        }

        checkoutDataResolver.resolvePaymentMethod = wrapper.wrap(
            checkoutDataResolver.resolvePaymentMethod,
            function (original) {
                original();
                tryPreselect();
            }
        );

        if (methodList && typeof methodList.subscribe === 'function') {
            methodList.subscribe(function () {
                tryPreselect();
            });
        }

        return checkoutDataResolver;
    };
});
