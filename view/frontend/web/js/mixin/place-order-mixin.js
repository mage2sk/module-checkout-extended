/**
 * Panth CheckoutExtended — Place Order Mixin
 *
 * Injects newsletter extension attribute into payment data before the order is placed.
 */
define([
    'mage/utils/wrapper'
], function (wrapper) {
    'use strict';

    return function (placeOrderAction) {
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            try {
                if (!paymentData) {
                    paymentData = {};
                }
                if (!paymentData.extension_attributes) {
                    paymentData.extension_attributes = {};
                }

                // Newsletter subscription
                if (window.panthCheckoutNewsletter && typeof window.panthCheckoutNewsletter === 'function') {
                    paymentData.extension_attributes.panth_subscribe_newsletter = window.panthCheckoutNewsletter();
                }
            } catch (e) {
                // Non-blocking
            }

            return originalAction(paymentData, messageContainer);
        });
    };
});
