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
