define([
    'uiComponent',
    'ko',
    'underscore',
    'Panth_CheckoutExtended/js/model/order-note'
], function (Component, ko, _, noteModel) {
    'use strict';

    var DEFAULT_MAX = 500;

    return Component.extend({
        defaults: {
            template: 'Panth_CheckoutExtended/order-note',
            enabled: false,
            label: '',
            placeholder: '',
            maxLength: DEFAULT_MAX,
            inputId: 'panth-co-order-note',
            counterId: 'panth-co-order-note-counter'
        },

        initialize: function () {
            var self = this,
                cfg,
                max;

            this._super();

            cfg = (window.checkoutConfig &&
                window.checkoutConfig.panthCheckout &&
                window.checkoutConfig.panthCheckout.orderNote) || {};

            if (this.enabled === undefined || this.enabled === null) {
                this.enabled = !!cfg.enabled;
            }

            if (!this.label && cfg.label) {
                this.label = cfg.label;
            }

            if (!this.placeholder && cfg.placeholder) {
                this.placeholder = cfg.placeholder;
            }

            max = parseInt(this.maxLength, 10);

            if (!(max > 0)) {
                max = parseInt(cfg.maxLength, 10);
            }

            if (!(max > 0)) {
                max = DEFAULT_MAX;
            }

            this.maxLength = max;
            this.enabled = !!this.enabled;
            this.note = noteModel.value;

            noteModel.enabled(this.enabled);
            noteModel.maxLength(max);

            if (!this.enabled) {
                this.note('');
            }

            this.note.subscribe(function (value) {
                var text = value === null || value === undefined ? '' : String(value);

                if (text.length > max) {
                    self.note(text.slice(0, max));
                }
            });

            this.counterText = ko.computed(function () {
                var text = self.note() || '';

                return String(text.length) + '/' + String(max);
            });

            return this;
        },

        isVisible: function () {
            return this.enabled === true;
        }
    });
});
