<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\CheckoutExtended\Helper\Data;

class DynamicStyles extends Template
{
    private Data $helper;

    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    public function getHelper(): Data
    {
        return $this->helper;
    }

    public function isEnabled(): bool
    {
        return $this->helper->isEnabled();
    }

    public function getAccentColor(): string
    {
        return $this->helper->getAccentColor();
    }

    public function getBorderRadius(): int
    {
        return $this->helper->getBorderRadius();
    }

    public function getCheckoutBodyClass(): string
    {
        return $this->helper->getCheckoutBodyClass();
    }

    public function getColumns(): int
    {
        return $this->helper->getColumns();
    }

    public function getSidebarPosition(): string
    {
        return $this->helper->getSidebarPosition();
    }

    public function isSidebarSticky(): bool
    {
        return $this->helper->isSidebarSticky();
    }

    public function getCardStyle(): string
    {
        return $this->helper->getCardStyle();
    }

    public function showStepIndicators(): bool
    {
        return $this->helper->showStepIndicators();
    }

    public function getFieldMode(): string
    {
        return $this->helper->getFieldMode();
    }

    public function usePlaceholders(): bool
    {
        return $this->helper->usePlaceholders();
    }

    public function showTooltips(): bool
    {
        return $this->helper->showTooltips();
    }

    public function showBillingTitle(): bool
    {
        return $this->helper->showBillingTitle();
    }

    public function getDefaultShippingMethod(): string
    {
        return $this->helper->getDefaultShippingMethod();
    }

    public function hideSingleShippingMethod(): bool
    {
        return $this->helper->hideSingleShippingMethod();
    }

    public function sortShippingByPrice(): bool
    {
        return $this->helper->sortShippingByPrice();
    }

    public function getDefaultPaymentMethod(): string
    {
        return $this->helper->getDefaultPaymentMethod();
    }

    public function getCustomCss(): string
    {
        return $this->helper->getCustomCss();
    }

    public function getCustomJs(): string
    {
        return $this->helper->getCustomJs();
    }
}
