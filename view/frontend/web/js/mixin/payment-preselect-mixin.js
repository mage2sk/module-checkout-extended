/**
 * Panth CheckoutExtended — Payment Preselect Mixin
 *
 * Preselects the admin-configured default payment method
 * (window.checkoutConfig.panthCheckout.payment.defaultMethod) when the
 * customer has not already chosen one and the method is available.
 *
 * Wraps Magento_Checkout/js/model/checkout-data-resolver::resolvePaymentMethod.
 */
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

        // Run-once guard shared across the wrapped resolver and the late-load
        // subscription, so the preselect fires at most once.
        var preselected = false;

        /**
         * Attempt the preselect. Bails (gracefully, no errors) when:
         *   - no default is configured,
         *   - the customer already has a selection,
         *   - the configured method is not in the available list yet.
         *
         * @return {Boolean} true once a selection has been made or is no
         *                   longer needed (so callers can stop retrying).
         */
        function tryPreselect() {
            if (preselected) {
                return true;
            }

            var cfg = (window.checkoutConfig.panthCheckout &&
                    window.checkoutConfig.panthCheckout.payment) || {},
                code = cfg.defaultMethod;

            // No config, or the customer already chose: never override.
            if (!code || checkoutData.getSelectedPaymentMethod()) {
                preselected = true;
                return true;
            }

            // Only preselect if the configured method is actually available.
            // Methods can load late — if absent, signal "keep waiting".
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
                // Let Magento run its normal resolution first.
                original();
                tryPreselect();
            }
        );

        // Payment methods often resolve after the first resolvePaymentMethod
        // pass (async loading). NOTE: paymentService.getAvailablePaymentMethods()
        // returns a PLAIN ARRAY (no .subscribe) — the KO observableArray that
        // backs it is Magento_Checkout/js/model/payment/method-list. Subscribe
        // to that so late-loaded methods still trigger the preselect, and guard
        // defensively so a preselect nicety can never break checkout bootstrap.
        if (methodList && typeof methodList.subscribe === 'function') {
            methodList.subscribe(function () {
                tryPreselect();
            });
        }

        return checkoutDataResolver;
    };
});
