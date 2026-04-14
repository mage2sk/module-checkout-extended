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

        return $jsLayout;
    }
}
