define([
    'uiComponent',
    'ko',
    'uiRegistry'
], function (Component, ko, registry) {
    'use strict';

    var sharedIsSubscribed = ko.observable(false);
    var emailAlreadyRegistered = ko.observable(false);

    return Component.extend({
        defaults: {
            template: 'Panth_CheckoutExtended/newsletter',
            isSubscribed: sharedIsSubscribed,
            alreadySubscribed: ko.observable(false),
            enabled: false,
            label: 'Subscribe to our newsletter',
            defaultChecked: false
        },

        initialize: function () {
            this._super();

            var self = this;

            // Logged-in user already subscribed — pre-set true and hide the checkbox
            var loggedIn  = window.checkoutConfig && window.checkoutConfig.isCustomerLoggedIn;
            var subStatus = window.checkoutConfig && window.checkoutConfig.panthNewsletterSubscribed;

            if (loggedIn && subStatus) {
                this.alreadySubscribed(true);
                sharedIsSubscribed(true);
            } else {
                this.isSubscribed(this.defaultChecked);
            }

            // Expose for newsletter-assigner via window
            window.panthCheckoutNewsletter = this.isSubscribed;

            // Track whether the entered email belongs to a registered account
            registry.get(
                'checkout.steps.shipping-step.shippingAddress.customer-email',
                function (emailComponent) {
                    emailAlreadyRegistered(!!emailComponent.isPasswordVisible());
                    emailComponent.isPasswordVisible.subscribe(function (visible) {
                        emailAlreadyRegistered(!!visible);
                    });
                }
            );

            return this;
        },

        isVisible: function () {
            if (!this.enabled) return false;
            if (this.alreadySubscribed()) return false;
            if (emailAlreadyRegistered()) return false;
            return true;
        },

        toggleSubscription: function () {
            this.isSubscribed(!this.isSubscribed());
        }
    });
});
