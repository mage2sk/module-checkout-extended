define([
    'mage/utils/wrapper'
], function (wrapper) {
    'use strict';

    function ratePrice(rate) {
        var price = rate.price_incl_tax != null ? rate.price_incl_tax : rate.amount;

        return price != null ? price : 0;
    }

    return function (shippingService) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return shippingService;
        }

        shippingService.setShippingRates = wrapper.wrap(
            shippingService.setShippingRates,
            function (original, ratesData) {
                var cfg = (window.checkoutConfig.panthCheckout &&
                    window.checkoutConfig.panthCheckout.shipping) || {};

                if (cfg.sortByPrice && Array.isArray(ratesData)) {
                    ratesData = ratesData.slice().sort(function (a, b) {
                        return ratePrice(a) - ratePrice(b);
                    });
                }

                return original(ratesData);
            }
        );

        return shippingService;
    };
});
