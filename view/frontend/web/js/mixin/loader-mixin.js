define([
    'jquery'
], function ($) {
    'use strict';

    var SECTION_LOADING_CLASS = 'panth-section-loading',
        sectionSelectors = {
            shipping: '#checkout-step-shipping',
            payment: '#checkout-step-payment',
            summary: '.opc-block-summary'
        },
        sectionGroups = {
            shipping: ['shipping'],
            payment: ['payment', 'summary'],
            summary: ['summary']
        };

    function ensureState() {
        if (!window.panthCheckoutLoader) {
            window.panthCheckoutLoader = {};
        }

        return window.panthCheckoutLoader;
    }

    function addSectionLoading(section) {
        var names = sectionGroups[section] || sectionGroups.payment,
            marked = 0;

        $.each(names, function (_, name) {
            var $el = $(sectionSelectors[name]);

            if ($el.length) {
                $el.addClass(SECTION_LOADING_CLASS);
                marked++;
            }
        });

        return marked;
    }

    function clearSectionLoading() {
        $.each(sectionSelectors, function (_, sel) {
            $(sel).removeClass(SECTION_LOADING_CLASS);
        });
    }

    return function (loader) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return loader;
        }

        var origStartLoader = loader.startLoader,
            origStopLoader = loader.stopLoader;

        loader.startLoader = function () {
            var state = ensureState();

            if (state.preventLoader) {
                state.preventLoader = false;

                if (addSectionLoading(state.activeSection || 'payment') > 0) {
                    return;
                }
            }

            return origStartLoader.apply(this, arguments);
        };

        loader.stopLoader = function () {
            var state = ensureState();

            state.preventLoader = false;
            clearSectionLoading();

            return origStopLoader.apply(this, arguments);
        };

        return loader;
    };
});
