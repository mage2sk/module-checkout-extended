/**
 * Panth CheckoutExtended — Shipping Service Mixin
 *
 * Sorts available shipping rates ascending by price before they are
 * published to the UI, when the admin setting
 * window.checkoutConfig.panthCheckout.shipping.sortByPrice is enabled.
 */
define([
    'mage/utils/wrapper'
], function (wrapper) {
    'use strict';

    /**
     * Resolve a comparable price for a rate, preferring the tax-inclusive
     * value and falling back to the raw amount.
     *
     * @param {Object} rate
     * @return {Number}
     */
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
                    // Sort a COPY so we never mutate the caller's array.
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
