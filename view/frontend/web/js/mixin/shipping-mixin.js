/**
 * Panth CheckoutExtended — Shipping View Mixin
 *
 * Overrides setShippingInformation to prevent navigation to payment step
 * in one-page checkout mode. Auto-save logic is in checkout_init.phtml.
 *
 * Also consumes the admin-configured shipping settings exposed at
 * window.checkoutConfig.panthCheckout.shipping:
 *   - defaultMethod    : preselect a specific carrier_method on load
 *   - hideSingleMethod : auto-select + visually hide a lone shipping method
 */
define([
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/checkout-data'
], function (
    _,
    quote,
    setShippingInformationAction,
    stepNavigator,
    shippingService,
    selectShippingMethodAction,
    checkoutData
) {
    'use strict';

    function keepAllStepsVisible() {
        _.each(stepNavigator.steps(), function (step) {
            step.isVisible(true);
        });
    }

    /**
     * Build the composite rate code Magento uses everywhere
     * (carrier_code + '_' + method_code). Defensive against malformed
     * rates so a stray entry can never throw.
     *
     * @param {Object} rate
     * @return {String}
     */
    function rateCode(rate) {
        if (!rate || rate.carrier_code == null || rate.method_code == null) {
            return '';
        }

        return rate.carrier_code + '_' + rate.method_code;
    }

    /**
     * Select a shipping rate both in the quote and in checkout-data so the
     * choice survives reloads and is picked up by the radio bindings.
     *
     * @param {Object} rate
     */
    function applyRate(rate) {
        selectShippingMethodAction(rate);
        checkoutData.setSelectedShippingRate(rateCode(rate));
    }

    return function (Component) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return Component;
        }

        return Component.extend({
            /**
             * Override initialize — wire up default-method preselection and
             * single-method hiding once shipping rates become available.
             */
            initialize: function () {
                this._super();

                var cfg = (window.checkoutConfig.panthCheckout &&
                        window.checkoutConfig.panthCheckout.shipping) || {},
                    // Run-once guard for the preselect (default-method /
                    // single-method auto-select) only. The body class is
                    // managed on EVERY update so it can be removed again.
                    preselected = false;

                // Nothing to do if neither feature is configured.
                if (!cfg.defaultMethod && !cfg.hideSingleMethod) {
                    return this;
                }

                // CSS (owned elsewhere) hides the lone radio when this class
                // is present. Keep it in sync with the live rate count so it
                // is removed again if rates change back to more than one.
                function syncSingleMethodClass(rates) {
                    var isSingle = !!cfg.hideSingleMethod &&
                        !!rates && rates.length === 1;

                    document.body.classList.toggle(
                        'panth-hide-single-shipping',
                        isSingle
                    );
                }

                shippingService.getShippingRates().subscribe(function (rates) {
                    // Toggle the visual hide class on every change regardless
                    // of the run-once preselect state.
                    syncSingleMethodClass(rates);

                    // Act only when rates resolve to a non-empty set, and
                    // preselect at most once for the component's lifetime.
                    if (preselected || !rates || !rates.length) {
                        return;
                    }

                    // Never override a method the customer already chose.
                    if (quote.shippingMethod() ||
                        checkoutData.getSelectedShippingRate()) {
                        preselected = true;
                        return;
                    }

                    if (cfg.defaultMethod) {
                        var match = _.find(rates, function (rate) {
                            return rateCode(rate) === cfg.defaultMethod;
                        });

                        // Bad/unknown config falls through silently: no match
                        // means no selection is forced.
                        if (match) {
                            applyRate(match);
                            preselected = true;
                            return;
                        }
                    }

                    if (cfg.hideSingleMethod && rates.length === 1) {
                        applyRate(rates[0]);
                        preselected = true;
                    }
                });

                return this;
            },

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
