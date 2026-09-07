define([
    'ko'
], function (ko) {
    'use strict';

    return {
        isSubscribed: function () {
            if (window.panthCheckoutNewsletter &&
                ko.isObservable(window.panthCheckoutNewsletter)
            ) {
                return !!ko.unwrap(window.panthCheckoutNewsletter);
            }

            return false;
        },

        assignToPaymentPayload: function (paymentData) {
            if (!paymentData.extension_attributes) {
                paymentData.extension_attributes = {};
            }

            paymentData.extension_attributes.panth_subscribe_newsletter = this.isSubscribed();

            return paymentData;
        }
    };
});
