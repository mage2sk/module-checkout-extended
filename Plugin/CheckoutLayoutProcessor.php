<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Panth\CheckoutExtended\Helper\Data;

class CheckoutLayoutProcessor implements LayoutProcessorInterface
{
    private Data $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function process($jsLayout)
    {
        if (!$this->helper->isEnabled()) {
            return $jsLayout;
        }

        $summary = &$jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children'];

        // Set sort orders for existing summary children
        // cart_items should come first
        if (isset($summary['cart_items'])) {
            $summary['cart_items']['sortOrder'] = 0;
        }
        // totals after items
        if (isset($summary['totals'])) {
            $summary['totals']['sortOrder'] = 10;
        }

        // Newsletter
        $summary['panth-newsletter'] = [
            'component' => 'Panth_CheckoutExtended/js/view/newsletter',
            'sortOrder' => 20,
            'config' => [
                'enabled' => $this->helper->isNewsletterEnabled(),
                'label' => $this->helper->getNewsletterLabel(),
                'defaultChecked' => $this->helper->isNewsletterCheckedByDefault(),
            ],
        ];

        // Move discount code from payment step into summary
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']
            ['afterMethods']['children']['discount'])) {
            $discount = $jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
                ['afterMethods']['children']['discount'];
            $discount['sortOrder'] = 40;
            $summary['panth-discount'] = $discount;
            unset($jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
                ['afterMethods']['children']['discount']);
        }

        // Place Order button
        $summary['panth-place-order'] = [
            'component' => 'Panth_CheckoutExtended/js/view/sidebar-place-order',
            'sortOrder' => 50,
        ];

        // Placeholder injection for checkout address fields
        if ($this->helper->usePlaceholders()) {
            // Shipping address fieldset
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']
                ['shipping-step']['children']['shippingAddress']['children']
                ['shipping-address-fieldset']['children'])) {
                $this->applyPlaceholders($jsLayout['components']['checkout']['children']['steps']['children']
                    ['shipping-step']['children']['shippingAddress']['children']
                    ['shipping-address-fieldset']['children']);
            }

            // Global billing address fieldset (shared, when shown)
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
                ['afterMethods']['children']['billing-address-form']['children']
                ['form-fields']['children'])) {
                $this->applyPlaceholders($jsLayout['components']['checkout']['children']['steps']['children']
                    ['billing-step']['children']['payment']['children']
                    ['afterMethods']['children']['billing-address-form']['children']
                    ['form-fields']['children']);
            }

            // Per-payment-method billing address fieldsets (generated at runtime)
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
                ['payments-list']['children'])
                && is_array($jsLayout['components']['checkout']['children']['steps']['children']
                    ['billing-step']['children']['payment']['children']
                    ['payments-list']['children'])) {
                foreach ($jsLayout['components']['checkout']['children']['steps']['children']
                    ['billing-step']['children']['payment']['children']
                    ['payments-list']['children'] as &$paymentMethod) {
                    if (isset($paymentMethod['children']['form-fields']['children'])) {
                        $this->applyPlaceholders($paymentMethod['children']['form-fields']['children']);
                    }
                }
                unset($paymentMethod);
            }
        }

        return $jsLayout;
    }

    /**
     * Recursively walk a fieldset's children and set placeholder text on input fields.
     *
     * For each leaf field that carries a 'label', the label is copied into both
     * 'placeholder' and 'config.placeholder' so the uiComponent renders placeholder
     * text. The original label is preserved. Children containers are recursed into.
     */
    private function applyPlaceholders(array &$fields): void
    {
        foreach ($fields as &$field) {
            if (!is_array($field)) {
                continue;
            }

            // Recurse into nested children containers (e.g. region/grouped fields)
            if (isset($field['children']) && is_array($field['children'])) {
                $this->applyPlaceholders($field['children']);
            }

            // Input fields expose a 'label'; copy it into the placeholder slots.
            if (isset($field['label']) && is_string($field['label']) && $field['label'] !== '') {
                $field['placeholder'] = $field['label'];
                if (!isset($field['config']) || !is_array($field['config'])) {
                    $field['config'] = [];
                }
                $field['config']['placeholder'] = $field['label'];
            }
        }
        unset($field);
    }
}
