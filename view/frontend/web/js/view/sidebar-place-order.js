define([
    'uiComponent',
    'ko',
    'jquery',
    'underscore',
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
    _,
    quote,
    paymentService,
    customer,
    messageList,
    registry,
    scrollToError,
    $t
) {
    'use strict';

    var SHIPPING_COMPONENT = 'checkout.steps.shipping-step.shippingAddress',
        EMAIL_COMPONENT    = 'checkout.steps.shipping-step.shippingAddress.customer-email',
        VIRTUAL_EMAIL_COMPONENT = 'checkout.steps.billing-step.payment.customer-email',
        BILLING_COMPONENT  = 'Magento_Checkout/js/view/billing-address',
        SHIPPING_FORM      = '#co-shipping-form';

    function refreshCartSection() {
        try {
            require(['Magento_Customer/js/customer-data'], function (customerData) {
                try {
                    customerData.invalidate(['cart']);
                    customerData.reload(['cart'], true);
                } catch (e) {  }
            });
        } catch (e) {  }
    }

    return Component.extend({
        defaults: {
            template: 'Panth_CheckoutExtended/sidebar-place-order'
        },

        isPlacingOrder: ko.observable(false),

        isReady: ko.pureComputed(function () {
            return !!((quote.isVirtual() || quote.shippingMethod()) &&
                      quote.paymentMethod() &&
                      paymentService.getAvailablePaymentMethods().length > 0);
        }),

        validateEmail: function () {
            if (customer.isLoggedIn()) {
                return true;
            }

            try {
                var emailComponent = registry.get(quote.isVirtual() ? VIRTUAL_EMAIL_COMPONENT : EMAIL_COMPONENT) ||
                    registry.get(EMAIL_COMPONENT);

                if (emailComponent && typeof emailComponent.validateEmail === 'function') {
                    return !!emailComponent.validateEmail(false);
                }
            } catch (e) {  }

            try {
                var $form = $('form[data-role=email-with-possible-login]'),
                    $username = $form.find('input[name=username]');

                if ($form.length) {
                    $form.validation();

                    if ($username.length) {
                        return !!$username.valid();
                    }
                }
            } catch (e2) {  }

            return false;
        },

        validateShippingAddressAndMethod: function () {
            try {
                var shippingComponent = registry.get(SHIPPING_COMPONENT);

                if (shippingComponent &&
                    typeof shippingComponent.validateShippingInformation === 'function') {
                    return !!shippingComponent.validateShippingInformation();
                }
            } catch (e) {  }

            var ok = true;

            if (!this.validateEmail()) {
                ok = false;
            }

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

            if (!quote.shippingMethod()) {
                ok = false;
            }

            return ok;
        },

        getActiveBillingComponent: function () {
            var method = quote.paymentMethod(),
                code = method && method.method ? String(method.method) : '',
                components = registry.filter(function (component) {
                    return !!component && component.component === BILLING_COMPONENT;
                }),
                matched = null;

            if (code) {
                matched = _.find(components, function (component) {
                    return typeof component.name === 'string' &&
                        component.name.indexOf('.' + code + '-form') !== -1;
                });
            }

            return matched || components[0] || null;
        },

        validateBillingAddress: function (commit) {
            var component = this.getActiveBillingComponent(),
                address = quote.billingAddress(),
                source;

            if (address && address.firstname && (!component || component.isAddressDetailsVisible())) {
                return true;
            }

            if (!component) {
                return false;
            }

            if (!(component.selectedAddress() && !component.isAddressFormVisible())) {
                source = component.source;
                source.set('params.invalid', false);
                source.trigger(component.dataScopePrefix + '.data.validate');

                if (source.get(component.dataScopePrefix + '.custom_attributes')) {
                    source.trigger(component.dataScopePrefix + '.custom_attributes.data.validate');
                }

                if (source.get('params.invalid')) {
                    return false;
                }
            }

            if (!commit) {
                return true;
            }

            component.updateAddress();

            return !!quote.billingAddress() && component.isAddressDetailsVisible();
        },

        clickWhenIdle: function ($btn, attempts) {
            var self = this;

            if ($.active > 0 && attempts < 40) {
                setTimeout(function () {
                    self.clickWhenIdle($btn, attempts + 1);
                }, 100);

                return;
            }

            $btn.trigger('click');
        },

        validatePaymentMethod: function () {
            if (!quote.paymentMethod()) {
                try {
                    messageList.addErrorMessage({
                        message: $t('Please select a payment method.')
                    });
                } catch (e) {  }

                return false;
            }

            return true;
        },

        validateBeforePlaceOrder: function () {
            var valid = true;

            try {
                if (quote.isVirtual()) {
                    if (!this.validateEmail()) {
                        valid = false;
                    }

                    if (!this.validateBillingAddress(valid)) {
                        valid = false;
                    }
                } else {
                    if (!this.validateShippingAddressAndMethod()) {
                        valid = false;
                    }

                    if (!this.validateBillingAddress(valid)) {
                        valid = false;
                    }
                }

                if (!this.validatePaymentMethod()) {
                    valid = false;
                }
            } catch (e) {
                valid = false;
            }

            return valid;
        },

        placeOrder: function () {
            if (this.isPlacingOrder()) {
                return;
            }

            var self = this;

            if (!this.validateBeforePlaceOrder()) {
                scrollToError.now();
                setTimeout(function () { scrollToError.now(); }, 250);

                return;
            }

            var $activeMethod = $('.payment-method._active');

            var $btn = $activeMethod.find('.action.primary.checkout, .action.checkout').first();

            if (!$btn.length) {
                var paymentEl = document.getElementById('payment');
                if (paymentEl) {
                    paymentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            self.isPlacingOrder(true);

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

            if (quote.isVirtual()) {
                this.clickWhenIdle($btn, 0);
            } else {
                $btn.trigger('click');
            }

            setTimeout(function () {
                if ($activeMethod.find('.field-error:visible, .mage-error:visible').length > 0) {
                    reset();
                    scrollToError();
                }
            }, 300);

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

            $(document).on('ajax:error.panthOrder', reset);

            $(document).on('ajaxComplete.panthOrder', function (event, xhr, settings) {
                var url = (settings && settings.url) || '';

                if (/payment-information/i.test(url) &&
                    xhr && xhr.status >= 200 && xhr.status < 300) {
                    refreshCartSection();
                }
            });

            $(window).on('beforeunload.panthOrder', function () {
                refreshCartSection();
            });

            pollTimer = setInterval(function () {
                if (done) { clearInterval(pollTimer); return; }
                if ($('.message.error:visible, .message-error:visible, ' +
                        '.field-error:visible, .mage-error:visible').length > 0) {
                    clearInterval(pollTimer);
                    reset();
                }
            }, 400);

            safetyTimer = setTimeout(reset, 8000);
        }
    });
});
