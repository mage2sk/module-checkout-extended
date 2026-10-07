define([
    'Magento_Checkout/js/model/quote',
    'mage/translate'
], function (quote, $t) {
    'use strict';

    return function (Component) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return Component;
        }

        return Component.extend({
            isShippingPending: function () {
                var totals = this.totals(),
                    method = quote.shippingMethod(),
                    methodAmount = method ? parseFloat(method.amount) : 0,
                    totalsAmount = totals ? parseFloat(totals.shipping_amount) : 0;

                return methodAmount > 0 && !(totalsAmount > 0);
            },

            isCalculated: function () {
                return !!this._super() && !this.isShippingPending();
            },

            getValue: function () {
                return this.isShippingPending() ? $t('Not yet calculated') : this._super();
            },

            getExcludingValue: function () {
                return this.isShippingPending() ? $t('Not yet calculated') : this._super();
            },

            getIncludingValue: function () {
                return this.isShippingPending() ? $t('Not yet calculated') : this._super();
            }
        });
    };
});
