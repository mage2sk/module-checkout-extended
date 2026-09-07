<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Model\Color\Shade;

class DynamicStyles extends Template
{
    public const HOVER_DARKEN_RATIO = 0.15;

    private Data $helper;
    private Shade $shade;

    public function __construct(
        Context $context,
        Data $helper,
        array $data = [],
        ?Shade $shade = null
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->shade = $shade ?? new Shade();
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

    public function getAccentHoverColor(): string

    {

        $configured = $this->shade->normalize($this->helper->getAccentHoverColor());

        if ($configured !== null) {

            return $configured;

        }

        $accent = $this->shade->normalize($this->getAccentColor()) ?? '#1a1a2e';


        return $this->shade->darken($accent, self::HOVER_DARKEN_RATIO) ?? $accent;

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
