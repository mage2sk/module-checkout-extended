define([
    'mage/utils/wrapper',
    'Magento_Customer/js/customer-data',
    'Panth_CheckoutExtended/js/model/order-note'
], function (wrapper, customerData, orderNote) {
    'use strict';

    return function (placeOrderAction) {
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            var result;

            try {
                if (!paymentData) {
                    paymentData = {};
                }

                if (!paymentData.extension_attributes) {
                    paymentData.extension_attributes = {};
                }

                if (window.panthCheckoutNewsletter && typeof window.panthCheckoutNewsletter === 'function') {
                    paymentData.extension_attributes.panth_subscribe_newsletter = window.panthCheckoutNewsletter();
                }

                if (orderNote.enabled()) {
                    paymentData.extension_attributes.panth_order_note =
                        String(orderNote.value() || '').slice(0, orderNote.maxLength());
                }
            } catch (e) {
                paymentData = paymentData || {};
            }

            result = originalAction(paymentData, messageContainer);

            try {
                if (result && typeof result.done === 'function') {
                    result.done(function () {
                        try {
                            customerData.invalidate(['cart']);
                        } catch (e) {
                            return;
                        }
                    });
                }
            } catch (e) {
                return result;
            }

            return result;
        });
    };
});
