define([
    'jquery',
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'mage/translate',
    'jquery/ui-modules/keycode'
], function ($, _, quote, customer, $t) {
    'use strict';

    var COUNT_CLASS = 'panth-co-count',
        SIGNED_CLASS = 'panth-co-signedin',
        NOTE_CLASS = 'panth-co-method-note',
        NO_DETAILS_CLASS = 'panth-no-details',
        NOTE_METHOD = 'banktransfer',
        started = false;

    function config() {
        return (window.checkoutConfig && window.checkoutConfig.panthCheckout) || {};
    }

    function toNumber(value) {
        var n = parseFloat(value);

        return isNaN(n) ? null : n;
    }

    function itemsQty() {
        var totals = quote.totals(),
            cfg = window.checkoutConfig,
            qty = null;

        if (totals && totals['items_qty'] !== undefined && totals['items_qty'] !== null) {
            qty = toNumber(totals['items_qty']);
        }

        if (qty === null && cfg && cfg.totalsData) {
            qty = toNumber(cfg.totalsData['items_qty']);
        }

        qty = Math.round(qty || 0);

        return qty > 0 ? qty : 0;
    }

    function countText(qty) {
        if (qty === 1) {
            return $t('1 Item in Cart');
        }

        return $t('%1 Items in Cart').replace('%1', String(qty));
    }

    function heading() {
        var wrap = document.querySelector('.page-title-wrapper'),
            qty,
            span,
            text;

        if (!wrap) {
            return;
        }

        qty = itemsQty();
        span = wrap.querySelector('.' + COUNT_CLASS);

        if (qty <= 0) {
            if (span) {
                span.parentNode.removeChild(span);
            }

            return;
        }

        if (!span) {
            span = document.createElement('span');
            span.className = COUNT_CLASS;
            wrap.appendChild(span);
        }

        text = countText(qty);

        if (span.textContent !== text) {
            span.textContent = text;
        }
    }

    function logoutUrl() {
        var cfg = config();

        if (cfg.logoutUrl) {
            return cfg.logoutUrl;
        }

        return window.location.origin + '/customer/account/logout/';
    }

    function signedIn() {
        var step = document.querySelector('li.checkout-shipping-address .step-content'),
            existing,
            email,
            row,
            who,
            out;

        if (!step) {
            return;
        }

        existing = step.querySelector('.' + SIGNED_CLASS);

        if (!customer.isLoggedIn()) {
            if (existing) {
                existing.parentNode.removeChild(existing);
            }

            return;
        }

        email = customer.customerData && customer.customerData.email;

        if (!email || existing) {
            return;
        }

        row = document.createElement('div');
        row.className = SIGNED_CLASS;

        who = document.createElement('span');
        who.className = SIGNED_CLASS + '__as';
        who.textContent = $t('Signed in as %1').replace('%1', email);

        out = document.createElement('a');
        out.className = SIGNED_CLASS + '__out';
        out.href = logoutUrl();
        out.textContent = $t('Sign out');

        row.appendChild(who);
        row.appendChild(out);
        step.insertBefore(row, step.firstChild);
    }

    function methodNote() {
        var inputs = document.querySelectorAll(
            '.payment-method-title input[type="radio"][value="' + NOTE_METHOD + '"]'
        );

        _.each(inputs, function (input) {
            var title = input.closest('.payment-method-title'),
                label = title ? title.querySelector('label.label') : null,
                note;

            if (!label || label.querySelector('.' + NOTE_CLASS)) {
                return;
            }

            note = document.createElement('span');
            note.className = NOTE_CLASS;
            note.textContent = $t('Payment details are sent with your order confirmation.');
            label.appendChild(note);
        });
    }

    function taxGuard() {
        var rows = document.querySelectorAll('.opc-block-summary tr.totals-tax-summary');

        _.each(rows, function (row) {
            var next = row.nextElementSibling,
                found = false;

            while (next) {
                if (next.classList.contains('totals-tax-details')) {
                    found = true;
                    break;
                }

                if (next.tagName === 'TR' && !next.classList.contains('totals-tax-details')) {
                    break;
                }

                next = next.nextElementSibling;
            }

            row.classList.toggle(NO_DETAILS_CLASS, !found);
        });
    }

    function run() {
        try {
            heading();
            signedIn();
            methodNote();
            taxGuard();
        } catch (e) {
            return;
        }
    }

    function watch() {
        var root = document.getElementById('checkout') || document.body,
            scheduled = _.debounce(run, 120);

        run();
        quote.totals.subscribe(scheduled);
        customer.isLoggedIn.subscribe(scheduled);

        if (!window.MutationObserver) {
            window.setInterval(run, 1000);

            return;
        }

        new window.MutationObserver(scheduled).observe(root, {
            childList: true,
            subtree: true
        });
    }

    return function () {
        if (started) {
            return;
        }

        started = true;

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', watch);
        } else {
            watch();
        }
    };
});
