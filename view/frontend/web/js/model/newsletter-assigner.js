/**
 * Panth CheckoutExtended - Newsletter Assigner
 *
 * Reads the newsletter checkbox state and injects it into
 * the payment extension attributes before order placement.
 */
define([
    'ko'
], function (ko) {
    'use strict';

    return {
        /**
         * Check whether the customer opted in to the newsletter.
         *
         * @returns {Boolean}
         */
        isSubscribed: function () {
            if (window.panthCheckoutNewsletter &&
                ko.isObservable(window.panthCheckoutNewsletter)
            ) {
                return !!ko.unwrap(window.panthCheckoutNewsletter);
            }

            return false;
        },

        /**
         * Inject the newsletter flag into payment extension attributes.
         *
         * @param {Object} paymentData
         * @returns {Object}
         */
        assignToPaymentPayload: function (paymentData) {
            if (!paymentData.extension_attributes) {
                paymentData.extension_attributes = {};
            }

            paymentData.extension_attributes.panth_subscribe_newsletter = this.isSubscribed();

            return paymentData;
        }
    };
});
