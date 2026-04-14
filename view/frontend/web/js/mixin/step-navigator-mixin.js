/**
 * Panth CheckoutExtended — Step Navigator Mixin
 *
 * Converts Magento's multi-step checkout into a one-page checkout
 * by ensuring all steps remain visible without breaking the original
 * observable subscription chain.
 */
define([
    'jquery',
    'ko',
    'mage/utils/wrapper'
], function ($, ko, wrapper) {
    'use strict';

    var processed = [],
        SCROLL_OFFSET = 20;

    /**
     * Check whether we are on a checkout page.
     *
     * @return {boolean}
     */
    function isCheckoutPage() {
        return document.body.classList.contains('panth-checkout-extended');
    }

    /**
     * Toggle a body class that reflects the currently-active step.
     *
     * @param {string} code - step code, e.g. "shipping" or "payment"
     */
    function setActiveStepBodyClass(code) {
        var body  = document.body,
            classes = body.className.match(/panth-step-\S+/g);

        // Remove any previous panth-step-* class
        if (classes) {
            $.each(classes, function (_, cls) {
                body.classList.remove(cls);
            });
        }

        if (code) {
            body.classList.add('panth-step-' + code);
        }
    }

    return function (stepNavigator) {
        // Guard clause: only apply on checkout pages
        if (!isCheckoutPage()) {
            return stepNavigator;
        }

        // ------------------------------------------------------------------
        // 1. Override registerStep — force each step visible on registration
        //    while preserving the original observable and its subscriptions.
        // ------------------------------------------------------------------
        stepNavigator.registerStep = wrapper.wrap(
            stepNavigator.registerStep,
            function (original, code, alias, title, isVisible, navigate, sortOrder) {
                original(code, alias, title, isVisible, navigate, sortOrder);

                // Force visible right away; the original observable is kept.
                if (ko.isObservable(isVisible)) {
                    isVisible(true);
                }
            }
        );

        // ------------------------------------------------------------------
        // 2. Watch the steps array and wrap each step's isVisible with a
        //    pureComputed that always reads true but still forwards writes
        //    to the original observable so subscribers keep working.
        // ------------------------------------------------------------------
        stepNavigator.steps.subscribe(function (steps) {
            $.each(steps, function (_, step) {
                if (processed.indexOf(step.code) !== -1) {
                    return; // already processed
                }

                processed.push(step.code);

                var origVisible = step.isVisible;

                step.isVisible = ko.pureComputed({
                    read: function () {
                        // Always report visible in one-page mode, but also
                        // peek at the original so KO tracks the dependency.
                        origVisible(); // keep dependency chain alive
                        return true;
                    },
                    write: function (value) {
                        // Forward the write to the original observable so
                        // any third-party subscriptions still fire.
                        origVisible(value);

                        // Toggle body class for the active step
                        if (value) {
                            setActiveStepBodyClass(step.code);
                        }
                    }
                });

                // Ensure visible immediately
                step.isVisible(true);
            });
        });

        // ------------------------------------------------------------------
        // 3. Override navigateTo — smooth-scroll to the step section instead
        //    of hiding / showing panels.
        // ------------------------------------------------------------------
        stepNavigator.navigateTo = wrapper.wrap(
            stepNavigator.navigateTo,
            function (original, code, scrollToElementId) {
                var targetId = scrollToElementId || code,
                    target   = document.getElementById(targetId);

                if (target) {
                    var rect = target.getBoundingClientRect(),
                        top  = rect.top + window.pageYOffset - SCROLL_OFFSET;

                    window.scrollTo({
                        top: top,
                        behavior: 'smooth'
                    });
                }

                setActiveStepBodyClass(code);

                return original(code, scrollToElementId);
            }
        );

        // ------------------------------------------------------------------
        // 4. Override setHash — prevent URL hash changes so the browser's
        //    back button is not polluted with step hashes.
        // ------------------------------------------------------------------
        stepNavigator.setHash = function (/* hash */) {
            // intentionally empty — no hash changes in one-page mode
        };

        // ------------------------------------------------------------------
        // 5. Override handleHash — after the initial hash processing, force
        //    all steps visible again in case the hash handler hid some.
        // ------------------------------------------------------------------
        if (typeof stepNavigator.handleHash === 'function') {
            stepNavigator.handleHash = wrapper.wrap(
                stepNavigator.handleHash,
                function (original) {
                    original();

                    // Give Knockout time to process bindings, then force all visible
                    setTimeout(function () {
                        $.each(stepNavigator.steps(), function (_, step) {
                            if (ko.isWritableObservable(step.isVisible)) {
                                step.isVisible(true);
                            }
                        });
                    }, 50);
                }
            );
        }

        return stepNavigator;
    };
});
