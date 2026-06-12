/**
 * Panth CheckoutExtended — Sidebar Place Order Button
 *
 * In one-page mode the sidebar "Place Order" button is ALWAYS reachable, so a
 * click must first run full client-side validation (email + shipping address +
 * shipping method + payment method). Only when everything is valid do we trigger
 * the active payment method's own Place Order button so that payment-specific
 * validation (e.g. PO Number) and agreements still run.
 *
 * If validation fails we reveal the inline field errors, smooth-scroll to the
 * first one, and return WITHOUT showing the "Placing Your Order" overlay and
 * WITHOUT submitting.
 */
define([
    'uiComponent',
    'ko',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Customer/js/model/customer',
    'Magento_Ui/js/model/messageList',
    'uiRegistry',
    'Panth_CheckoutExtended/js/utils/scroll-to-error',
    'mage/translate',
    'mage/validation'
], function (
    Component,
    ko,
    $,
    quote,
    paymentService,
    customer,
    messageList,
    registry,
    scrollToError,
    $t
) {
    'use strict';

    // Registry path of Magento's shipping uiComponent (Magento_Checkout/js/view/shipping).
    var SHIPPING_COMPONENT = 'checkout.steps.shipping-step.shippingAddress',
        EMAIL_COMPONENT    = 'checkout.steps.shipping-step.shippingAddress.customer-email',
        SHIPPING_FORM      = '#co-shipping-form';

    /**
     * Force the customer-data 'cart' section to refresh so the minicart count
     * drops to 0 immediately after an order is placed.
     *
     * Why this is needed: Magento normally invalidates the 'cart' section from
     * customer-data.js's `ajaxComplete` handler when the place-order REST POST
     * completes. That only happens when the active payment method places the
     * order through jQuery ajax (mage/storage). Payment methods that redirect to
     * an off-site PSP, or otherwise navigate away without a jQuery ajaxComplete,
     * never trigger that invalidation — so the success page reloads the still
     * cached 'cart' and shows the ordered items (e.g. "10 Items in Cart").
     *
     * invalidate() bumps the 'cart' version in the persistent `section_data_ids`
     * cookie (and drops the cached copy); the freshly loaded success page reads
     * that bumped version and re-fetches 'cart' from the server (now empty → 0).
     * reload() additionally refreshes it in-place for flows that stay on the
     * checkout page. Both are best-effort and never block the order flow.
     */
    function refreshCartSection() {
        try {
            require(['Magento_Customer/js/customer-data'], function (customerData) {
                try {
                    customerData.invalidate(['cart']);
                    customerData.reload(['cart'], true);
                } catch (e) { /* best-effort: never break the success flow */ }
            });
        } catch (e) { /* best-effort */ }
    }

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

        /**
         * Validate the guest email field. Logged-in customers have no email
         * field, so they always pass. Uses Magento's email uiComponent
         * (validateEmail) when available; falls back to validating the
         * email-with-possible-login form directly via mage/validation.
         *
         * Reveals inline errors as a side-effect.
         *
         * @return {Boolean}
         */
        validateEmail: function () {
            if (customer.isLoggedIn()) {
                return true;
            }

            // Primary path: the email uiComponent's own validateEmail(false)
            // runs mage/validation on the username field and returns the result.
            try {
                var emailComponent = registry.get(EMAIL_COMPONENT);

                if (emailComponent && typeof emailComponent.validateEmail === 'function') {
                    return !!emailComponent.validateEmail(false);
                }
            } catch (e) { /* fall through to DOM fallback */ }

            // Fallback: validate the login form directly. This both shows the
            // inline "required / invalid email" messages and returns validity.
            try {
                var $form = $('form[data-role=email-with-possible-login]'),
                    $username = $form.find('input[name=username]');

                if ($form.length) {
                    $form.validation();

                    if ($username.length) {
                        return !!$username.valid();
                    }
                }
            } catch (e2) { /* uncertainty — block below */ }

            // Could not run email validation; prefer blocking an invalid submit.
            return false;
        },

        /**
         * Validate email + shipping address form + shipping method together via
         * Magento's shipping uiComponent. validateShippingInformation() reveals
         * inline field errors, focuses the first invalid input, persists the
         * address when valid, and returns false when anything is invalid.
         *
         * Falls back to a manual address-form validation when the component is
         * not resolvable.
         *
         * @return {Boolean}
         */
        validateShippingAddressAndMethod: function () {
            try {
                var shippingComponent = registry.get(SHIPPING_COMPONENT);

                if (shippingComponent &&
                    typeof shippingComponent.validateShippingInformation === 'function') {
                    // Native validation: email + address + shipping method,
                    // with inline errors and focus on the first invalid field.
                    return !!shippingComponent.validateShippingInformation();
                }
            } catch (e) { /* fall through to manual fallback */ }

            // ---- Fallback path (component not available) ----
            var ok = true;

            // (a) Email (guest only).
            if (!this.validateEmail()) {
                ok = false;
            }

            // (b) Address form via the uiComponent source, then mage/validation
            //     on the raw form as a last resort.
            try {
                var source = registry.get('checkoutProvider');

                if (source && typeof source.set === 'function') {
                    source.set('params.invalid', false);
                    source.trigger('shippingAddress.data.validate');

                    if (source.get('params.invalid')) {
                        ok = false;
                    }
                }
            } catch (e2) {
                ok = false;
            }

            try {
                var $form = $(SHIPPING_FORM);

                if ($form.length) {
                    $form.validation();

                    if (!$form.validation('isValid')) {
                        ok = false;
                    }
                }
            } catch (e3) {
                ok = false;
            }

            // (c) Shipping method must be selected.
            if (!quote.shippingMethod()) {
                ok = false;
            }

            return ok;
        },

        /**
         * Validate that a payment method is selected. Shows a banner error when
         * none is chosen.
         *
         * @return {Boolean}
         */
        validatePaymentMethod: function () {
            if (!quote.paymentMethod()) {
                try {
                    messageList.addErrorMessage({
                        message: $t('Please select a payment method.')
                    });
                } catch (e) { /* messaging is best-effort */ }

                return false;
            }

            return true;
        },

        /**
         * Run the full client-side validation sequence in order:
         *   (a) guest email, (b) shipping address form, (c) shipping method,
         *   (d) payment method.
         *
         * (a)–(c) are covered by validateShippingAddressAndMethod(). Inline
         * errors are revealed as a side-effect. Wrapped so a validation
         * framework hiccup blocks (never silently submits) an invalid order.
         *
         * @return {Boolean} true only when everything is valid.
         */
        validateBeforePlaceOrder: function () {
            var valid = true;

            try {
                // Email + address + shipping method (native, shows inline errors).
                if (!this.validateShippingAddressAndMethod()) {
                    valid = false;
                }

                // Payment method selected.
                if (!this.validatePaymentMethod()) {
                    valid = false;
                }
            } catch (e) {
                // On uncertainty, block + scroll rather than risk a bad submit.
                valid = false;
            }

            return valid;
        },

        placeOrder: function () {
            if (this.isPlacingOrder()) {
                return;
            }

            var self = this;

            // ---- 1. FULL CLIENT-SIDE VALIDATION FIRST ----
            // The overlay (isPlacingOrder) is NOT shown and NO submit happens
            // unless every check passes.
            if (!this.validateBeforePlaceOrder()) {
                // Reveal/scroll to the first inline error. Errors may be added
                // asynchronously (region dropdowns, KO bindings), so scroll on
                // the next tick to catch them once rendered.
                scrollToError.now();
                setTimeout(function () { scrollToError.now(); }, 250);

                return;
            }

            // ---- 2. EVERYTHING VALID — proceed with the existing flow ----
            var $activeMethod = $('.payment-method._active');

            // Find the payment method's own Place Order button (hidden via CSS).
            // Clicking it lets the payment renderer run its own validate() logic
            // (payment-specific fields + agreements).
            var $btn = $activeMethod.find('.action.primary.checkout, .action.checkout').first();

            if (!$btn.length) {
                var paymentEl = document.getElementById('payment');
                if (paymentEl) {
                    paymentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            // Overlay shown ONLY now that validation has passed.
            self.isPlacingOrder(true);

            // Centralised reset — idempotent, cleans up all watchers.
            var done = false;
            var pollTimer = null;
            var safetyTimer = null;
            var observer = null;

            function reset() {
                if (done) { return; }
                done = true;
                self.isPlacingOrder(false);
                $(document).off('.panthOrder');
                $(window).off('.panthOrder');
                clearInterval(pollTimer);
                clearTimeout(safetyTimer);
                if (observer) {
                    try { observer.disconnect(); } catch (e) {}
                }
            }

            // Click the real button — payment renderer runs its own validate()
            // internally (payment-specific fields + agreements).
            $btn.trigger('click');

            // 1. Immediate check: pre-existing field validation errors
            //    (e.g. payment-specific / agreement errors). Scroll to them too.
            setTimeout(function () {
                if ($activeMethod.find('.field-error:visible, .mage-error:visible').length > 0) {
                    reset();
                    scrollToError();
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

            // 3b. SUCCESS detection — empty the cart/minicart after placement.
            //     Two complementary signals cover every payment type:
            //       (a) ajaxComplete on a 2xx place-order REST response, for
            //           methods that place the order via jQuery ajax; and
            //       (b) navigation to the success page (beforeunload), for
            //           redirect-based methods that bypass jQuery ajax entirely.
            //     refreshCartSection() persists the 'cart' invalidation flag to
            //     localStorage so the freshly loaded success page shows 0.
            $(document).on('ajaxComplete.panthOrder', function (event, xhr, settings) {
                var url = (settings && settings.url) || '';

                if (/payment-information/i.test(url) &&
                    xhr && xhr.status >= 200 && xhr.status < 300) {
                    refreshCartSection();
                }
            });

            $(window).on('beforeunload.panthOrder', function () {
                // We only attach watchers once an order submit is in flight, so a
                // navigation away at this point is the success-page redirect.
                refreshCartSection();
            });

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
