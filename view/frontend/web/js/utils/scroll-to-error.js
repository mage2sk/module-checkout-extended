define([
    'jquery'
], function ($) {
    'use strict';

    var DEBOUNCE_MS    = 200,
        SHAKE_CLASS    = 'panth-shake',
        SHAKE_DURATION = 500,
        SCROLL_OFFSET  = 40,
        ERROR_SELECTORS = [
            '.field._error:visible',
            '.field-error:visible',
            '.mage-error:visible',
            '.message-error:visible'
        ].join(', '),
        SCOPE_SELECTOR = '#checkout',
        timer = null;

    function scope() {
        var $checkout = $(SCOPE_SELECTOR);

        return $checkout.length ? $checkout : $(document);
    }

    function doScrollToError() {
        var $errors = scope().find(ERROR_SELECTORS),
            $first, $field, $input, rect, top;

        if (!$errors.length) {
            return;
        }

        $first = $errors.first();

        rect = $first[0].getBoundingClientRect();
        top  = rect.top + window.pageYOffset - SCROLL_OFFSET;

        window.scrollTo({
            top: top,
            behavior: 'smooth'
        });

        $field = $first.closest('.field');

        if (!$field.length) {
            $field = $first.parent();
        }

        $input = $field.find('input, select, textarea')
            .filter(':visible')
            .first();

        if ($input.length) {
            try { $input.trigger('focus'); } catch (e) {  }
        }

        $first.addClass(SHAKE_CLASS);

        setTimeout(function () {
            $first.removeClass(SHAKE_CLASS);
        }, SHAKE_DURATION);
    }

    function scrollToError() {
        if (timer) {
            clearTimeout(timer);
        }

        timer = setTimeout(function () {
            timer = null;
            doScrollToError();
        }, DEBOUNCE_MS);
    }

    scrollToError.now = function () {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        doScrollToError();
    };

    return scrollToError;
});
