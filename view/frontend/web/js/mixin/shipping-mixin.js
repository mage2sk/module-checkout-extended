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

    function rateCode(rate) {
        if (!rate || rate.carrier_code == null || rate.method_code == null) {
            return '';
        }

        return rate.carrier_code + '_' + rate.method_code;
    }

    function applyRate(rate) {
        selectShippingMethodAction(rate);
        checkoutData.setSelectedShippingRate(rateCode(rate));
    }

    return function (Component) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                this._super();

                var cfg = (window.checkoutConfig.panthCheckout &&
                        window.checkoutConfig.panthCheckout.shipping) || {},
                    preselected = false;

                if (!cfg.defaultMethod && !cfg.hideSingleMethod) {
                    return this;
                }

                function syncSingleMethodClass(rates) {
                    var isSingle = !!cfg.hideSingleMethod &&
                        !!rates && rates.length === 1;

                    document.body.classList.toggle(
                        'panth-hide-single-shipping',
                        isSingle
                    );
                }

                shippingService.getShippingRates().subscribe(function (rates) {
                    syncSingleMethodClass(rates);

                    if (preselected || !rates || !rates.length) {
                        return;
                    }

                    if (quote.shippingMethod() ||
                        checkoutData.getSelectedShippingRate()) {
                        preselected = true;
                        return;
                    }

                    if (cfg.defaultMethod) {
                        var match = _.find(rates, function (rate) {
                            return rateCode(rate) === cfg.defaultMethod;
                        });

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
