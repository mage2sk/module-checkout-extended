define([
    'Magento_Checkout/js/model/quote'
], function (quote) {
    'use strict';

    return function (Component) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return Component;
        }

        return Component.extend({
            getItemsQty: function () {
                var totals = quote.totals();

                if (totals && totals['items_qty'] !== undefined && totals['items_qty'] !== null) {
                    return parseFloat(totals['items_qty']);
                }

                return this._super();
            },

            getCartLineItemsCount: function () {
                var totals = quote.totals();

                if (totals && Array.isArray(totals.items)) {
                    return totals.items.length;
                }

                return this._super();
            }
        });
    };
});
