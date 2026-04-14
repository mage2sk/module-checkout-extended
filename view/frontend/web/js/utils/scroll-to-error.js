/**
 * Panth CheckoutExtended — Scroll-to-Error Utility
 *
 * Debounced helper that finds the first visible validation error on
 * the page, scrolls it into view with smooth behaviour, and briefly
 * applies a shake animation class for visual feedback.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    var DEBOUNCE_MS    = 200,
        SHAKE_CLASS    = 'panth-shake',
        SHAKE_DURATION = 500,
        SCROLL_OFFSET  = 40,
        ERROR_SELECTORS = [
            '.mage-error:visible',
            '.field-error:visible',
            '.message-error:visible'
        ].join(', '),
        timer = null;

    /**
     * Core routine: locate the first error element, scroll to it,
     * and add a brief shake animation.
     */
    function doScrollToError() {
        var $errors = $(ERROR_SELECTORS),
            $first, rect, top;

        if (!$errors.length) {
            return;
        }

        $first = $errors.first();

        // Scroll into view with offset
        rect = $first[0].getBoundingClientRect();
        top  = rect.top + window.pageYOffset - SCROLL_OFFSET;

        window.scrollTo({
            top: top,
            behavior: 'smooth'
        });

        // Apply shake animation class, then remove after duration
        $first.addClass(SHAKE_CLASS);

        setTimeout(function () {
            $first.removeClass(SHAKE_CLASS);
        }, SHAKE_DURATION);
    }

    /**
     * Public API — debounced wrapper around the core routine.
     * Multiple rapid calls within the debounce window will only
     * trigger a single scroll + animation.
     */
    return function scrollToError() {
        if (timer) {
            clearTimeout(timer);
        }

        timer = setTimeout(function () {
            timer = null;
            doScrollToError();
        }, DEBOUNCE_MS);
    };
});
