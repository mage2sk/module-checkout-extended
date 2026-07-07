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

        if (isset($summary['cart_items'])) {
            $summary['cart_items']['sortOrder'] = 0;
        }

        if (isset($summary['totals'])) {
            $summary['totals']['sortOrder'] = 10;
        }

        $summary['panth-newsletter'] = [
            'component' => 'Panth_CheckoutExtended/js/view/newsletter',
            'sortOrder' => 20,
            'config' => [
                'enabled' => $this->helper->isNewsletterEnabled(),
                'label' => $this->helper->getNewsletterLabel(),
                'defaultChecked' => $this->helper->isNewsletterCheckedByDefault(),
            ],
        ];

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

        $summary['panth-place-order'] = [
            'component' => 'Panth_CheckoutExtended/js/view/sidebar-place-order',
            'sortOrder' => 50,
        ];

        if ($this->helper->usePlaceholders()) {
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']
                ['shipping-step']['children']['shippingAddress']['children']
                ['shipping-address-fieldset']['children'])) {
                $this->applyPlaceholders($jsLayout['components']['checkout']['children']['steps']['children']
                    ['shipping-step']['children']['shippingAddress']['children']
                    ['shipping-address-fieldset']['children']);
            }

            if (isset($jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
                ['afterMethods']['children']['billing-address-form']['children']
                ['form-fields']['children'])) {
                $this->applyPlaceholders($jsLayout['components']['checkout']['children']['steps']['children']
                    ['billing-step']['children']['payment']['children']
                    ['afterMethods']['children']['billing-address-form']['children']
                    ['form-fields']['children']);
            }

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

    private function applyPlaceholders(array &$fields): void
    {
        foreach ($fields as &$field) {
            if (!is_array($field)) {
                continue;
            }

            if (isset($field['children']) && is_array($field['children'])) {
                $this->applyPlaceholders($field['children']);
            }

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
