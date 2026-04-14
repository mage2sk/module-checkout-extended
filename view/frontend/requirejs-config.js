var config = {
    paths: {
        'ko': 'knockoutjs/knockout'
    },
    map: {
        '*': {
            'Magento_Checkout/summary/item/details':
                'Panth_CheckoutExtended/template/summary/item/details',
            'Magento_SalesRule/payment/discount':
                'Panth_CheckoutExtended/template/payment/discount'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/model/step-navigator': {
                'Panth_CheckoutExtended/js/mixin/step-navigator-mixin': true
            },
            'Magento_Checkout/js/view/shipping': {
                'Panth_CheckoutExtended/js/mixin/shipping-mixin': true
            },
            'Magento_Checkout/js/model/full-screen-loader': {
                'Panth_CheckoutExtended/js/mixin/loader-mixin': true
            },
            'Magento_Checkout/js/model/resource-url-manager': {
                'Panth_CheckoutExtended/js/model/cart-url-manager-mixin': true
            },
            'Magento_Checkout/js/view/summary/item/details': {
                'Panth_CheckoutExtended/js/view/cart-details-mixin': true
            },
'Magento_Checkout/js/action/place-order': {
                'Panth_CheckoutExtended/js/mixin/place-order-mixin': true
            },
            'Magento_Checkout/js/view/summary/abstract-total': {
                'Panth_CheckoutExtended/js/mixin/abstract-total-mixin': true
            },
            'Magento_Checkout/js/action/get-payment-information': {
                'Panth_CheckoutExtended/js/mixin/get-payment-information-mixin': true
            },
            'Magento_Checkout/js/action/set-payment-information-extended': {
                'Panth_CheckoutExtended/js/mixin/set-payment-info-mixin': true
            }
        }
    }
};
