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

            // Feature disabled via admin config — never subscribe, never pre-check.
            if (this.enabled === false) {
                this.isSubscribed(false);
            } else {
                // Logged-in user already subscribed — pre-set true and hide the checkbox
                var loggedIn  = window.checkoutConfig && window.checkoutConfig.isCustomerLoggedIn;
                var subStatus = window.checkoutConfig && window.checkoutConfig.panthNewsletterSubscribed;

                if (loggedIn && subStatus) {
                    this.alreadySubscribed(true);
                    sharedIsSubscribed(true);
                } else {
                    this.isSubscribed(this.defaultChecked);
                }
            }

            // Whenever the box is not actually visible (feature disabled, already-subscribed
            // customer, or guest email belongs to a registered account), the published flag
            // must be false so the place-order mixin / assigner never send true for a hidden
            // box. This computed is the single source of truth for the gated value.
            this.gatedSubscription = ko.computed(function () {
                if (!self.isVisible()) {
                    return false;
                }
                return !!self.isSubscribed();
            });

            // Expose the GATED value (not the raw checkbox observable) so consumers always
            // read false when the box is hidden/disabled/already-subscribed. A ko.computed
            // is a function and passes ko.isObservable(), satisfying both consumers
            // (place-order-mixin calls it directly; newsletter-assigner ko.unwraps it).
            window.panthCheckoutNewsletter = this.gatedSubscription;

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
