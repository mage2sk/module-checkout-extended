define([
    'jquery'
], function ($) {
    'use strict';

    function getQuote() {
        try {
            return require('Magento_Checkout/js/model/quote');
        } catch (e) {
            return {};
        }
    }

    var SECTION_LOADING_CLASS = '_block-content-loading',
        sectionSelectors      = {
            shipping: '#checkout-step-shipping',
            payment:  '#checkout-step-payment',
            summary:  '.opc-block-summary'
        };

    function ensureState() {
        var quote = getQuote();

        if (!quote.panthCheckout) {
            quote.panthCheckout = {};
        }

        if (!quote.panthCheckout.state) {
            quote.panthCheckout.state = {};
        }

        return quote.panthCheckout.state;
    }

    function addSectionLoading() {
        var state   = ensureState(),
            section = state.activeSection || 'shipping',
            sel     = sectionSelectors[section],
            $el;

        if (sel) {
            $el = $(sel);

            if ($el.length) {
                $el.addClass(SECTION_LOADING_CLASS);
                return $el;
            }
        }

        $el = $('#checkout');

        if ($el.length) {
            $el.addClass(SECTION_LOADING_CLASS);
        }

        return $el;
    }

    function clearSectionLoading() {
        $.each(sectionSelectors, function (_, sel) {
            $(sel).removeClass(SECTION_LOADING_CLASS);
        });

        $('#checkout').removeClass(SECTION_LOADING_CLASS);
    }

    return function (loader) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return loader;
        }

        var origStartLoader = loader.startLoader,
            origStopLoader  = loader.stopLoader;

        loader.startLoader = function () {
            var state = ensureState();

            if (state.preventLoader) {
                addSectionLoading();

                state.preventLoader = false;

                return;
            }

            return origStartLoader.apply(this, arguments);
        };

        loader.stopLoader = function () {
            clearSectionLoading();

            return origStopLoader.apply(this, arguments);
        };

        return loader;
    };
});
