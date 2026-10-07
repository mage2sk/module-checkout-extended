define([
    'jquery',
    'ko',
    'mage/utils/wrapper'
], function ($, ko, wrapper) {
    'use strict';

    var processed = [],
        SCROLL_OFFSET = 20;

    function isCheckoutPage() {
        return document.body.classList.contains('panth-checkout-extended');
    }

    function setActiveStepBodyClass(code) {
        var body  = document.body,
            classes = body.className.match(/panth-step-\S+/g);

        if (classes) {
            $.each(classes, function (_, cls) {
                if (cls !== 'panth-step-indicators') {
                    body.classList.remove(cls);
                }
            });
        }

        if (code) {
            body.classList.add('panth-step-' + code);
        }
    }

    return function (stepNavigator) {
        if (!isCheckoutPage()) {
            return stepNavigator;
        }

        stepNavigator.registerStep = wrapper.wrap(
            stepNavigator.registerStep,
            function (original, code, alias, title, isVisible, navigate, sortOrder) {
                original(code, alias, title, isVisible, navigate, sortOrder);

                if (ko.isObservable(isVisible)) {
                    isVisible(true);
                }
            }
        );

        stepNavigator.steps.subscribe(function (steps) {
            $.each(steps, function (_, step) {
                if (processed.indexOf(step.code) !== -1) {
                    return;
                }

                processed.push(step.code);

                var origVisible = step.isVisible;

                step.isVisible = ko.pureComputed({
                    read: function () {
                        origVisible();
                        return true;
                    },
                    write: function (value) {
                        origVisible(value);

                        if (value) {
                            setActiveStepBodyClass(step.code);
                        }
                    }
                });

                step.isVisible(true);
            });
        });

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

        stepNavigator.setHash = function () {
        };

        if (typeof stepNavigator.handleHash === 'function') {
            stepNavigator.handleHash = wrapper.wrap(
                stepNavigator.handleHash,
                function (original) {
                    original();

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
