/**
 * Panth CheckoutExtended — Abstract Total Mixin
 *
 * In one-page checkout, totals must always show (isFullMode = true)
 * because the user never navigates away from the shipping step.
 */
define([], function () {
    'use strict';

    return function (Component) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return Component;
        }

        return Component.extend({
            isFullMode: function () {
                return !!this.getTotals();
            }
        });
    };
});
