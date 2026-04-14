<?php

declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin\Cart;

use Magento\Checkout\Model\DefaultConfigProvider;
use Panth\CheckoutExtended\Helper\Data;

/**
 * Plugin to add all CheckoutExtended feature flags to checkout config.
 * Exposes settings at window.checkoutConfig.panthCheckout
 */
class ConfigProvider
{
    private Data $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function afterGetConfig(DefaultConfigProvider $subject, array $result): array
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }

        if (!isset($result['panthCheckout'])) {
            $result['panthCheckout'] = [];
        }

        $result['panthCheckout'] = [
            'cart' => [
                'qtyIncrement' => $this->helper->isQtyIncrementEnabled(),
                'showSku' => $this->helper->isProductSkuEnabled(),
                'showLink' => $this->helper->isProductLinkEnabled(),
            ],
            'shipping' => [
                'defaultMethod' => $this->helper->getDefaultShippingMethod(),
                'hideSingleMethod' => $this->helper->hideSingleShippingMethod(),
                'sortByPrice' => $this->helper->sortShippingByPrice(),
            ],
            'payment' => [
                'defaultMethod' => $this->helper->getDefaultPaymentMethod(),
            ],
        ];

        return $result;
    }
}
