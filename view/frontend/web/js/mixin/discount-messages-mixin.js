define([
    'jquery'
], function ($) {
    'use strict';

    var BLOCK_SELECTOR = '.opc-sidebar [data-role=checkout-messages], .panth-discount-block [data-role=checkout-messages]';

    return function (Component) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                var self = this,
                    reveal = function (list) {
                        if (list && list.length) {
                            $(BLOCK_SELECTOR).stop(true, true).css('display', '');
                        }
                    };

                this._super();

                if (this.messageContainer) {
                    if (typeof this.messageContainer.getErrorMessages === 'function') {
                        this.messageContainer.getErrorMessages().subscribe(reveal);
                    }
                    if (typeof this.messageContainer.getSuccessMessages === 'function') {
                        this.messageContainer.getSuccessMessages().subscribe(reveal);
                    }
                }

                return this;
            },

            isVisible: function () {
                return !!(this.messageContainer && this.messageContainer.hasMessages());
            },

            onHiddenChange: function () {
                return;
            }
        });
    };
});
