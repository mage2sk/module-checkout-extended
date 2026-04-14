/**
 * Panth CheckoutExtended — Shipping View Mixin
 *
 * Overrides setShippingInformation to prevent navigation to payment step
 * in one-page checkout mode. Auto-save logic is in checkout_init.phtml.
 */
define([
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/step-navigator'
], function (_, quote, setShippingInformationAction, stepNavigator) {
    'use strict';

    function keepAllStepsVisible() {
        _.each(stepNavigator.steps(), function (step) {
            step.isVisible(true);
        });
    }

    return function (Component) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return Component;
        }

        return Component.extend({
            /**
             * Override setShippingInformation — save without navigating
             * to payment step. In one-page mode both steps are always visible.
             */
            setShippingInformation: function () {
                try {
                    if (!this.validateShippingInformation()) {
                        return;
                    }
                } catch (e) {
                    return;
                }

                setShippingInformationAction().done(function () {
                    keepAllStepsVisible();
                });
            }
        });
    };
});
