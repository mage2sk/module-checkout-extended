<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin\Cart;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Framework\UrlInterface;
use Panth\CheckoutExtended\Helper\Data;

class ConfigProvider
{
    private Data $helper;

    private ImageHelper $imageHelper;

    private UrlInterface $urlBuilder;

    public function __construct(
        Data $helper,
        ImageHelper $imageHelper,
        UrlInterface $urlBuilder
    ) {
        $this->helper = $helper;
        $this->imageHelper = $imageHelper;
        $this->urlBuilder = $urlBuilder;
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
            'orderNote' => [
                'enabled' => $this->helper->isOrderNoteEnabled(),
                'label' => $this->helper->getOrderNoteLabel(),
                'placeholder' => $this->helper->getOrderNotePlaceholder(),
                'maxLength' => $this->helper->getOrderNoteMaxLength(),
            ],
            'placeholderImage' => $this->getPlaceholderImage(),
            'logoutUrl' => $this->urlBuilder->getUrl('customer/account/logout'),
        ];

        return $result;
    }

    private function getPlaceholderImage(): string
    {
        try {
            return (string) $this->imageHelper->getDefaultPlaceholderUrl('small_image');
        } catch (\Throwable $e) {
            return '';
        }
    }
}
