define([
    'jquery',
    'ko',
    'underscore',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, ko, _, modal, $t) {
    'use strict';

    var ROOT = 'panth-ab',
        BODY_OPEN = 'panth-ab-open',
        started = false,
        $content = null,
        content = null,
        listEl = null,
        afterClose = null,
        closedFired = false,
        closeTimer = null;

    function cards() {
        return Array.prototype.slice.call(
            document.querySelectorAll('.shipping-address-items .shipping-address-item')
        );
    }

    function textLines(card) {
        var clone = card.cloneNode(true),
            strip = clone.querySelectorAll('button, .action, .field.choice'),
            text;

        _.each(strip, function (el) {
            el.parentNode.removeChild(el);
        });

        clone.style.position = 'absolute';
        clone.style.left = '-9999px';
        clone.style.width = '400px';
        document.body.appendChild(clone);
        text = (clone.innerText || '').split('\n');
        document.body.removeChild(clone);

        return _.filter(_.map(text, function (line) {
            return line.trim();
        }), function (line) {
            return line.length > 0;
        });
    }

    function regionName(region) {
        if (region && typeof region === 'object') {
            return region.region || region['region_code'] || '';
        }

        return region || '';
    }

    function cardData(card) {
        var vm = null,
            address = null,
            lines = [],
            name,
            street,
            cityLine,
            countryName,
            fallback;

        try {
            vm = ko.dataFor(card);
        } catch (e) {
            vm = null;
        }

        if (vm && typeof vm.address === 'function') {
            address = vm.address();
        }

        if (address) {
            name = _.compact([
                address.prefix, address.firstname, address.middlename, address.lastname, address.suffix
            ]).join(' ').replace(/\s+/g, ' ').trim();

            if (address.company) {
                lines.push(String(address.company));
            }

            street = _.compact(_.values(address.street || {})).join(', ');

            if (street) {
                lines.push(street);
            }

            cityLine = _.compact([address.city, regionName(address.region)]).join(', ');

            if (address.postcode) {
                cityLine = (cityLine + ' ' + address.postcode).trim();
            }

            if (cityLine) {
                lines.push(cityLine);
            }

            if (address.countryId) {
                countryName = typeof vm.getCountryName === 'function' ?
                    vm.getCountryName(address.countryId) : '';
                lines.push(String(countryName || address.countryId));
            }

            if (address.telephone) {
                lines.push(String(address.telephone));
            }

            if (name) {
                return {name: name, lines: lines};
            }
        }

        fallback = textLines(card);

        return {
            name: fallback[0] || $t('Address'),
            lines: fallback.slice(1)
        };
    }

    function ensureCloseHook() {
        $content.off('modalclosed.' + ROOT).on('modalclosed.' + ROOT, function () {
            var fn = afterClose;

            closedFired = true;
            afterClose = null;
            window.clearTimeout(closeTimer);
            closeTimer = null;
            document.body.classList.remove(BODY_OPEN);

            if (fn) {
                fn();
            }
        });
    }

    function build() {
        var addBtn;

        if (content) {
            return;
        }

        content = document.createElement('div');
        content.className = ROOT;

        listEl = document.createElement('div');
        listEl.className = ROOT + '__list';
        listEl.setAttribute('role', 'radiogroup');
        listEl.setAttribute('aria-label', $t('Shipping Address Book'));

        addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = ROOT + '__add';
        addBtn.textContent = $t('Add New Address');
        addBtn.addEventListener('click', addNew);

        content.appendChild(listEl);
        content.appendChild(addBtn);

        $content = $(content);
        $content.modal({
            type: 'popup',
            modalClass: ROOT + '-modal',
            responsive: true,
            innerScroll: true,
            clickableOverlay: true,
            title: $t('Shipping Address Book'),
            buttons: [{
                text: $t('Cancel'),
                'class': ROOT + '__cancel',
                click: function () {
                    this.closeModal();
                }
            }, {
                text: $t('Save Address'),
                'class': ROOT + '__save',
                click: function () {
                    save();
                }
            }]
        });

        ensureCloseHook();
        watchVisibility();
    }

    function watchVisibility() {
        var root = modalRoot()[0],
            wasShown;

        if (!root || !window.MutationObserver) {
            return;
        }

        wasShown = root.classList.contains('_show');

        new window.MutationObserver(function () {
            var shown = root.classList.contains('_show');

            if (shown === wasShown) {
                return;
            }

            wasShown = shown;
            window.setTimeout(shown ? ensureFocusInside : ensureClosed, 450);
        }).observe(root, {attributes: true, attributeFilter: ['class']});
    }

    function populate() {
        listEl.innerHTML = '';

        _.each(cards(), function (card, index) {
            var data = cardData(card),
                selected = card.classList.contains('selected-item'),
                row = document.createElement('label'),
                input = document.createElement('input'),
                body = document.createElement('span'),
                name = document.createElement('span'),
                lines = document.createElement('span');

            row.className = ROOT + '__card' + (selected ? ' is-selected' : '');

            input.type = 'radio';
            input.name = 'panth-ab-choice';
            input.value = String(index);
            input.checked = selected;

            body.className = ROOT + '__card-body';
            name.className = ROOT + '__card-name';
            name.textContent = data.name;
            lines.className = ROOT + '__card-lines';
            lines.textContent = _.compact(data.lines).join(', ');

            body.appendChild(name);
            body.appendChild(lines);
            row.appendChild(input);
            row.appendChild(body);

            input.addEventListener('change', function () {
                _.each(listEl.querySelectorAll('.' + ROOT + '__card'), function (el) {
                    el.classList.remove('is-selected');
                });
                row.classList.add('is-selected');
            });

            listEl.appendChild(row);
        });
    }

    function isOpen() {
        var widget = $content ? $content.data('mage-modal') : null;

        if (widget && widget.options && widget.options.isOpen !== undefined) {
            return !!widget.options.isOpen;
        }

        return !!$content && $content.closest('.modal-popup').hasClass('_show');
    }

    function modalRoot() {
        return $content ? $content.closest('.modal-popup') : $();
    }

    function ensureFocusInside() {
        var root = modalRoot()[0],
            closeBtn;

        if (!root || !isOpen() || root.contains(document.activeElement)) {
            return;
        }

        closeBtn = root.querySelector('.action-close');

        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function ensureClosed() {
        var widget = $content ? $content.data('mage-modal') : null,
            root = modalRoot()[0];

        if (!widget || widget.options.isOpen || closedFired || !root || root.classList.contains('_show')) {
            return;
        }

        if (typeof widget._close !== 'function') {
            return;
        }

        if (widget.options.transitionEvent) {
            $(root).off(widget.options.transitionEvent);
        }

        widget._close();
    }

    function open() {
        build();
        populate();
        afterClose = null;
        closedFired = false;
        window.clearTimeout(closeTimer);
        closeTimer = null;
        document.body.classList.add(BODY_OPEN);
        $content.modal('openModal');
    }

    function close(then) {
        if (!$content) {
            return;
        }

        afterClose = then || null;

        if (!isOpen()) {
            afterClose = null;
            document.body.classList.remove(BODY_OPEN);

            if (then) {
                then();
            }

            return;
        }

        closedFired = false;
        $content.modal('closeModal');
        window.clearTimeout(closeTimer);
        closeTimer = window.setTimeout(ensureClosed, 700);
    }

    function addNew() {
        close(function () {
            var trigger = document.querySelector('.new-address-popup .action-show-popup');

            if (trigger) {
                trigger.click();
            }
        });
    }

    function save() {
        var chosen = listEl.querySelector('input[name="panth-ab-choice"]:checked'),
            card,
            ship;

        if (!chosen) {
            close();

            return;
        }

        card = cards()[parseInt(chosen.value, 10)];

        if (!card || card.classList.contains('selected-item')) {
            close();

            return;
        }

        ship = card.querySelector('.action-select-shipping-item');

        close(function () {
            if (ship && ship.isConnected) {
                ship.click();
            }
        });
    }

    function trigger() {
        var host = document.querySelector('.new-address-popup'),
            existing = host ? host.querySelector('.' + ROOT + '-trigger') : null,
            btn;

        if (!host) {
            return;
        }

        if (cards().length < 1) {
            if (existing) {
                existing.parentNode.removeChild(existing);
            }

            return;
        }

        if (existing) {
            return;
        }

        btn = document.createElement('button');
        btn.type = 'button';
        btn.className = ROOT + '-trigger action';
        btn.textContent = $t('Address book');
        btn.addEventListener('click', function () {
            btn.focus();
            open();
        });
        host.insertBefore(btn, host.firstChild);
    }

    function watch() {
        var root = document.getElementById('checkout') || document.body,
            scheduled = _.debounce(trigger, 120);

        trigger();

        if (!window.MutationObserver) {
            window.setInterval(trigger, 1000);

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
