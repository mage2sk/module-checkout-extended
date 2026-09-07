define([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';

    var GRACE_MS = 700,
        started = false;

    function widgetOf(modalEl) {
        var content = modalEl.querySelector('[data-role="content"]'),
            candidates = content ? [content].concat(Array.prototype.slice.call(content.children)) : [],
            i,
            widget;

        for (i = 0; i < candidates.length; i++) {
            widget = $(candidates[i]).data('mage-modal');

            if (widget && widget.modal && widget.modal[0] === modalEl) {
                return widget;
            }
        }

        return null;
    }

    function settle(modalEl) {
        var widget = widgetOf(modalEl),
            data = $(modalEl).data();

        if (!widget || widget.options.isOpen || modalEl.classList.contains('_show')) {
            return;
        }

        if ((data.panthClosedAt || 0) >= (data.panthHiddenAt || 0)) {
            return;
        }

        if (typeof widget._close !== 'function') {
            return;
        }

        if (widget.options.transitionEvent) {
            $(modalEl).off(widget.options.transitionEvent);
        }

        widget._close();
    }

    function schedule(modalEl) {
        $(modalEl).data('panthHiddenAt', Date.now());
        window.clearTimeout($(modalEl).data('panthSettleTimer'));
        $(modalEl).data('panthSettleTimer', window.setTimeout(function () {
            settle(modalEl);
        }, GRACE_MS));
    }

    function observe() {
        var body = document.body,
            seen = new window.WeakMap();

        function attach(modalEl) {
            var wasShown;

            if (seen.has(modalEl)) {
                return;
            }

            wasShown = modalEl.classList.contains('_show');
            seen.set(modalEl, true);

            new window.MutationObserver(function () {
                var shown = modalEl.classList.contains('_show');

                if (shown === wasShown) {
                    return;
                }

                wasShown = shown;

                if (!shown) {
                    schedule(modalEl);
                }
            }).observe(modalEl, {attributes: true, attributeFilter: ['class']});
        }

        function scan() {
            _.each(body.querySelectorAll('.modal-popup'), attach);
        }

        $(document).on('modalclosed', function (e) {
            var modalEl = $(e.target).closest('.modal-popup')[0];

            if (modalEl) {
                $(modalEl).data('panthClosedAt', Date.now());
            }
        });

        scan();

        if (window.MutationObserver) {
            new window.MutationObserver(_.debounce(scan, 100)).observe(body, {childList: true, subtree: true});
        } else {
            window.setInterval(scan, 1000);
        }
    }

    return function () {
        if (started) {
            return;
        }

        started = true;

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', observe);
        } else {
            observe();
        }
    };
});
