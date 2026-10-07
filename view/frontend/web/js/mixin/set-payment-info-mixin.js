define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (setPaymentInfoAction) {
        return wrapper.wrap(
            setPaymentInfoAction,
            function (originalAction, messageContainer, paymentData, skipBilling) {
                var isLoggedIn = !!(window.checkoutConfig && window.checkoutConfig.isCustomerLoggedIn);

                if (!isLoggedIn) {
                    var guestEmail = (typeof quote.guestEmail === 'function')
                        ? quote.guestEmail()
                        : quote.guestEmail;

                    if (!guestEmail || String(guestEmail).indexOf('@') < 0) {
                        return $.Deferred().resolve().promise();
                    }
                }

                if (!quote.isVirtual() &&
                    typeof window.panthShippingInfoSaved === 'function' &&
                    !window.panthShippingInfoSaved()) {
                    return $.Deferred().resolve().promise();
                }

                try {
                    if (paymentData && paymentData.extension_attributes) {
                        if (window.panthCheckoutNewsletter && typeof window.panthCheckoutNewsletter === 'function') {
                            paymentData.extension_attributes.panth_subscribe_newsletter = window.panthCheckoutNewsletter();
                        }
                    }
                } catch (e) {
                }

                return originalAction(messageContainer, paymentData, skipBilling);
            }
        );
    };
});
