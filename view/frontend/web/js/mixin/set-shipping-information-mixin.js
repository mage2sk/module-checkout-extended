define([
    'mage/utils/wrapper'
], function (wrapper) {
    'use strict';

    function scopeLoader() {
        if (!window.panthCheckoutLoader) {
            window.panthCheckoutLoader = {};
        }

        window.panthCheckoutLoader.preventLoader = true;
        window.panthCheckoutLoader.activeSection = 'payment';
    }

    return function (action) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return action;
        }

        return wrapper.wrap(action, function (original) {
            scopeLoader();

            return original();
        });
    };
});
