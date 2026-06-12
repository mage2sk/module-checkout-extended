/**
 * Panth CheckoutExtended — Place Order Mixin
 *
 * (1) Injects the newsletter extension attribute into the payment data before
 *     the order is placed.
 * (2) On a successful order, marks the `cart` customer-data section stale so the
 *     minicart reflects the now-empty server cart after the redirect to the
 *     success page. The order itself is placed by core (which deactivates the
 *     quote server-side), but the standard ajaxComplete-driven invalidation can
 *     be missed for this single-page flow (and for guest carts merged on login),
 *     leaving a stale "1 Item in Cart". Hooking the place-order action's own
 *     promise is the reliable in-flow place to invalidate.
 */
define([
    'mage/utils/wrapper',
    'Magento_Customer/js/customer-data'
], function (wrapper, customerData) {
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

            var result = originalAction(paymentData, messageContainer);

            // Invalidate the cart section the moment the order succeeds. invalidate()
            // bumps the section version synchronously (cookie/localStorage), so the
            // success page that loads right after the redirect sees it as stale and
            // re-fetches the empty server cart. Fully guarded so it can never break
            // order placement.
            try {
                if (result && typeof result.done === 'function') {
                    result.done(function () {
                        try {
                            customerData.invalidate(['cart']);
                        } catch (e) { /* noop */ }
                    });
                }
            } catch (e) {
                // Non-blocking
            }

            return result;
        });
    };
});
