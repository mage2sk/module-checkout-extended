define([
    'ko',
    'underscore',
    'Magento_Customer/js/customer-data'
], function (ko, _, customerData) {
    'use strict';

    var DEFAULT_SIZE = 78,
        images = ko.observable(seed()),
        subscribed = false;

    function valid(entry) {
        return !!(entry && _.isObject(entry) && entry.src &&
            String(entry.src) !== 'null' && String(entry.src) !== 'undefined');
    }

    function normalise(entry) {
        return {
            src: String(entry.src),
            alt: entry.alt === undefined || entry.alt === null ? '' : String(entry.alt),
            width: entry.width,
            height: entry.height
        };
    }

    function seed() {
        var cfg = window.checkoutConfig,
            map = {};

        if (cfg && cfg.imageData && _.isObject(cfg.imageData)) {
            _.each(cfg.imageData, function (entry, key) {
                if (valid(entry)) {
                    map[String(key)] = normalise(entry);
                }
            });
        }

        return map;
    }

    function absorb(cart) {
        var map = _.extend({}, images()),
            changed = false;

        if (!cart || !_.isArray(cart.items)) {
            return;
        }

        _.each(cart.items, function (item) {
            var key,
                current;

            if (!item || item['item_id'] === undefined || item['item_id'] === null || !valid(item['product_image'])) {
                return;
            }

            key = String(item['item_id']);
            current = map[key];

            if (!current || current.src !== String(item['product_image'].src)) {
                map[key] = normalise(item['product_image']);
                changed = true;
            }
        });

        if (changed) {
            images(map);
        }
    }

    function subscribeOnce() {
        var cart;

        if (subscribed) {
            return;
        }

        subscribed = true;

        try {
            cart = customerData.get('cart');
            absorb(cart());
            cart.subscribe(absorb);
        } catch (e) {
            return;
        }
    }

    function placeholder() {
        var cfg = window.checkoutConfig && window.checkoutConfig.panthCheckout;

        return cfg && cfg.placeholderImage ? String(cfg.placeholderImage) : '';
    }

    function fallbackSize(dimension) {
        var known = _.find(images(), function (entry) {
            return entry && parseFloat(entry[dimension]) > 0;
        });

        return known ? known[dimension] : DEFAULT_SIZE;
    }

    return function (Component) {
        if (!document.body.classList.contains('panth-checkout-extended')) {
            return Component;
        }

        return Component.extend({
            defaults: {
                template: 'Panth_CheckoutExtended/summary/item/details/thumbnail'
            },

            initialize: function () {
                this._super();
                subscribeOnce();

                return this;
            },

            panthImage: function (item) {
                var map = images(),
                    id = item ? item['item_id'] : undefined;

                if (id === undefined || id === null) {
                    return null;
                }

                return map[String(id)] || null;
            },

            isImageMissing: function (item) {
                return !this.panthImage(item);
            },

            getImageItem: function (item) {
                var image = this.panthImage(item);

                return image ? image : [];
            },

            getSrc: function (item) {
                var image = this.panthImage(item);

                if (image) {
                    return image.src;
                }

                return placeholder();
            },

            getWidth: function (item) {
                var image = this.panthImage(item);

                return image && image.width ? image.width : fallbackSize('width');
            },

            getHeight: function (item) {
                var image = this.panthImage(item);

                return image && image.height ? image.height : fallbackSize('height');
            },

            getAlt: function (item) {
                var image = this.panthImage(item);

                if (image) {
                    return image.alt;
                }

                return item && item.name ? String(item.name) : '';
            }
        });
    };
});
