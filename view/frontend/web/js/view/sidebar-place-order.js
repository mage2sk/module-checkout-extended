/**
 * Panth CheckoutExtended — Sidebar Place Order Button
 *
 * Triggers the active payment method's own Place Order button so that
 * payment-specific validation (e.g. PO Number) runs correctly.
 */
define([
    'uiComponent',
    'ko',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment-service'
], function (Component, ko, $, quote, paymentService) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Panth_CheckoutExtended/sidebar-place-order'
        },

        isPlacingOrder: ko.observable(false),

        isReady: ko.pureComputed(function () {
            return !!(quote.shippingMethod() &&
                      quote.paymentMethod() &&
                      paymentService.getAvailablePaymentMethods().length > 0);
        }),

        placeOrder: function () {
            if (this.isPlacingOrder()) {
                return;
            }

            var self = this;
            var $activeMethod = $('.payment-method._active');

            // Find the payment method's own Place Order button (hidden via CSS).
            // Clicking it lets the payment renderer run its own validate() logic.
            var $btn = $activeMethod.find('.action.primary.checkout, .action.checkout').first();

            if (!$btn.length) {
                var paymentEl = document.getElementById('payment');
                if (paymentEl) {
                    paymentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            self.isPlacingOrder(true);

            // Centralised reset — idempotent, cleans up all watchers
            var done = false;
            var pollTimer = null;
            var safetyTimer = null;
            var observer = null;

            function reset() {
                if (done) { return; }
                done = true;
                self.isPlacingOrder(false);
                $(document).off('.panthOrder');
                clearInterval(pollTimer);
                clearTimeout(safetyTimer);
                if (observer) {
                    try { observer.disconnect(); } catch (e) {}
                }
            }

            // Click the real button — payment renderer runs its own validate() internally
            $btn.trigger('click');

            // 1. Immediate check: pre-existing field validation errors
            setTimeout(function () {
                if ($activeMethod.find('.field-error:visible, .mage-error:visible').length > 0) {
                    reset();
                }
            }, 300);

            // 2. MutationObserver: detect error messages added to the DOM asynchronously
            //    (covers postcode API responses, server-side validation, etc.)
            if (window.MutationObserver) {
                observer = new MutationObserver(function () {
                    if ($('.message.error:visible, .message-error:visible, ' +
                            '[data-ui-id="checkout-cart-validationmessages-message-error"]:visible').length > 0) {
                        reset();
                    }
                });
                var checkoutEl = document.getElementById('checkout') || document.body;
                observer.observe(checkoutEl, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['style', 'class']
                });
            }

            // 3. jQuery AJAX error events (server 4xx / 5xx)
            $(document).on('ajax:error.panthOrder', reset);

            // 4. Polling fallback — catches async errors the observer may miss
            //    (e.g. errors shown via KO visibility binding rather than DOM insertion)
            pollTimer = setInterval(function () {
                if (done) { clearInterval(pollTimer); return; }
                if ($('.message.error:visible, .message-error:visible, ' +
                        '.field-error:visible, .mage-error:visible').length > 0) {
                    clearInterval(pollTimer);
                    reset();
                }
            }, 400);

            // 5. Safety reset after 8 s — never leave button stuck
            safetyTimer = setTimeout(reset, 8000);
        }
    });
});
