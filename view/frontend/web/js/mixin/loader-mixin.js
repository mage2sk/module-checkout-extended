/**
 * Panth CheckoutExtended — Full Screen Loader Mixin
 *
 * In one-page checkout, non-critical operations (address updates,
 * payment-info saves) use a section-level loading indicator instead
 * of a full-page blocking overlay.
 */
define([
    'jquery',
    'Magento_Checkout/js/model/quote'
], function ($, quote) {
    'use strict';

    var SECTION_LOADING_CLASS = '_block-content-loading',
        sectionSelectors      = {
            shipping: '#checkout-step-shipping',
            payment:  '#checkout-step-payment',
            summary:  '.opc-block-summary'
        };

    /**
     * Ensure the panthCheckout state namespace exists on quote.
     */
    function ensureState() {
        if (!quote.panthCheckout) {
            quote.panthCheckout = {};
        }

        if (!quote.panthCheckout.state) {
            quote.panthCheckout.state = {};
        }

        return quote.panthCheckout.state;
    }

    /**
     * Find the checkout section element that triggered the loader and
     * add the section-level loading class to it.
     *
     * @return {jQuery|null}
     */
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

        // Fallback: tag the whole checkout container
        $el = $('#checkout');

        if ($el.length) {
            $el.addClass(SECTION_LOADING_CLASS);
        }

        return $el;
    }

    /**
     * Remove the section-level loading class from every known section.
     */
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

        /**
         * startLoader — check whether the current operation should be
         * blocked from showing a full-screen loader.
         *
         * Set `quote.panthCheckout.state.preventLoader = true` before
         * triggering any non-blocking operation (address save, payment
         * info update, etc.) to keep the UX smooth.
         */
        loader.startLoader = function () {
            var state = ensureState();

            if (state.preventLoader) {
                // Show a section-level indicator instead
                addSectionLoading();

                // Reset the flag so subsequent calls behave normally
                state.preventLoader = false;

                // Do NOT call the original full-screen loader
                return;
            }

            return origStartLoader.apply(this, arguments);
        };

        /**
         * stopLoader — always clean up both the full-screen overlay
         * AND any section-level loading classes.
         */
        loader.stopLoader = function () {
            clearSectionLoading();

            return origStopLoader.apply(this, arguments);
        };

        return loader;
    };
});
