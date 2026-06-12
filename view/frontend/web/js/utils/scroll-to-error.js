/**
 * Panth CheckoutExtended — Scroll-to-Error Utility
 *
 * Finds the first visible validation error within the checkout, smooth-scrolls
 * it into view, focuses the related input, and briefly applies a shake
 * animation class for visual feedback.
 *
 * Exposes a debounced default export (safe for rapid/repeated calls) plus a
 * `.now()` method for a deterministic, immediate scroll right after a failed
 * validation pass — used by sidebar-place-order.js when it blocks an invalid
 * submit.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    var DEBOUNCE_MS    = 200,
        SHAKE_CLASS    = 'panth-shake',
        SHAKE_DURATION = 500,
        SCROLL_OFFSET  = 40,
        // Cover Magento's error markup: inline field messages (.mage-error),
        // field-level wrappers (.field._error / .field-error), and message
        // banners. :visible filters out hidden/empty placeholders.
        ERROR_SELECTORS = [
            '.field._error:visible',
            '.field-error:visible',
            '.mage-error:visible',
            '.message-error:visible'
        ].join(', '),
        // Scope to the checkout container so we never scroll to unrelated
        // errors elsewhere on the page.
        SCOPE_SELECTOR = '#checkout',
        timer = null;

    /**
     * Resolve the checkout scope, falling back to the document if the
     * dedicated container is not present.
     *
     * @return {jQuery}
     */
    function scope() {
        var $checkout = $(SCOPE_SELECTOR);

        return $checkout.length ? $checkout : $(document);
    }

    /**
     * Core routine: locate the first visible error element, scroll to it,
     * focus the nearest invalid input, and add a brief shake animation.
     */
    function doScrollToError() {
        var $errors = scope().find(ERROR_SELECTORS),
            $first, $field, $input, rect, top;

        if (!$errors.length) {
            return;
        }

        $first = $errors.first();

        // Scroll into view with offset.
        rect = $first[0].getBoundingClientRect();
        top  = rect.top + window.pageYOffset - SCROLL_OFFSET;

        window.scrollTo({
            top: top,
            behavior: 'smooth'
        });

        // Focus the invalid input so keyboard users land on the right field.
        // The error message usually sits inside (or beside) the .field that
        // holds the control, so walk up to the field and grab its control.
        $field = $first.closest('.field');

        if (!$field.length) {
            $field = $first.parent();
        }

        $input = $field.find('input, select, textarea')
            .filter(':visible')
            .first();

        if ($input.length) {
            try { $input.trigger('focus'); } catch (e) { /* focus may throw on detached nodes */ }
        }

        // Apply shake animation class, then remove after duration.
        $first.addClass(SHAKE_CLASS);

        setTimeout(function () {
            $first.removeClass(SHAKE_CLASS);
        }, SHAKE_DURATION);
    }

    /**
     * Public API — debounced wrapper around the core routine. Multiple rapid
     * calls within the debounce window collapse to a single scroll + animation.
     */
    function scrollToError() {
        if (timer) {
            clearTimeout(timer);
        }

        timer = setTimeout(function () {
            timer = null;
            doScrollToError();
        }, DEBOUNCE_MS);
    }

    /**
     * Immediate, non-debounced scroll. Use right after a synchronous failed
     * validation so the user is taken to the first error without delay.
     */
    scrollToError.now = function () {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        doScrollToError();
    };

    return scrollToError;
});
