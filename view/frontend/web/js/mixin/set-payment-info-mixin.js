/**
 * Panth CheckoutExtended - Set Payment Information Mixin
 *
 * Wraps set-payment-information-extended to inject newsletter
 * extension attributes and suppress the page-load 400 that fires
 * when Magento auto-calls this before the guest email has been entered.
 *
 * NOTE: quote.guestEmail is a PLAIN STRING property (not a KO observable).
 * It is set by email.js via plain assignment: quote.guestEmail = value.
 * Never call it as a function.
 */
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
                    // quote.guestEmail is a plain string property, NOT a KO observable
                    var guestEmail = (typeof quote.guestEmail === 'function')
                        ? quote.guestEmail()
                        : quote.guestEmail;

                    if (!guestEmail || String(guestEmail).indexOf('@') < 0) {
                        // Email not yet entered — bail silently
                        return $.Deferred().resolve().promise();
                    }
                }

                // Guard: shipping must have been saved to the server first.
                // checkout_init.phtml sets window.panthShippingInfoSaved once
                // setShippingInformation succeeds. Without this guard the SalesRule
                // mixin fires set-payment-information on page load before the quote
                // has a server-side shipping address, causing a 400.
                if (typeof window.panthShippingInfoSaved === 'function' &&
                    !window.panthShippingInfoSaved()) {
                    return $.Deferred().resolve().promise();
                }

                // Inject newsletter extension attribute if available
                try {
                    if (paymentData && paymentData.extension_attributes) {
                        if (window.panthCheckoutNewsletter && typeof window.panthCheckoutNewsletter === 'function') {
                            paymentData.extension_attributes.panth_subscribe_newsletter = window.panthCheckoutNewsletter();
                        }
                    }
                } catch (e) {
                    // Non-blocking
                }

                return originalAction(messageContainer, paymentData, skipBilling);
            }
        );
    };
});
