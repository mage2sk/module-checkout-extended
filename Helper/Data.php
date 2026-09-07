<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_PREFIX = 'panth_checkout_extended/';

    public function getConfigValue(string $group, string $field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PREFIX . $group . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isEnabled($storeId = null): bool
    {
        return (bool) $this->getConfigValue('general', 'enabled', $storeId);
    }

    public function getColumns($storeId = null): int
    {
        return (int) ($this->getConfigValue('layout', 'columns', $storeId) ?: 3);
    }

    public function getSidebarPosition($storeId = null): string
    {
        return (string) ($this->getConfigValue('layout', 'sidebar_position', $storeId) ?: 'right');
    }

    public function isSidebarSticky($storeId = null): bool
    {
        return (bool) $this->getConfigValue('layout', 'sidebar_sticky', $storeId);
    }

    public function getCardStyle($storeId = null): string
    {
        return (string) ($this->getConfigValue('style', 'card_style', $storeId) ?: 'elevated');
    }

    public function getBorderRadius($storeId = null): int
    {
        return (int) ($this->getConfigValue('style', 'border_radius', $storeId) ?: 12);
    }

    public function showStepIndicators($storeId = null): bool
    {
        return (bool) $this->getConfigValue('style', 'step_indicators', $storeId);
    }

    public function getAccentColor($storeId = null): string
    {
        return (string) ($this->getConfigValue('style', 'accent_color', $storeId) ?: '#1a1a2e');
    }

    public function getAccentHoverColor($storeId = null): string
    {
        return trim((string) $this->getConfigValue('style', 'accent_hover_color', $storeId));
    }

    public function isQtyIncrementEnabled($storeId = null): bool
    {
        return (bool) $this->getConfigValue('cart', 'qty_increment_enabled', $storeId);
    }

    public function isProductSkuEnabled($storeId = null): bool
    {
        return (bool) $this->getConfigValue('cart', 'product_sku_enabled', $storeId);
    }

    public function isProductLinkEnabled($storeId = null): bool
    {
        return (bool) $this->getConfigValue('cart', 'product_link_enabled', $storeId);
    }

    public function isNewsletterEnabled($storeId = null): bool
    {
        return (bool) $this->getConfigValue('newsletter', 'enabled', $storeId);
    }

    public function getNewsletterLabel($storeId = null): string
    {
        return (string) ($this->getConfigValue('newsletter', 'field_label', $storeId) ?: 'Subscribe to Newsletter');
    }

    public function isNewsletterCheckedByDefault($storeId = null): bool
    {
        return (bool) $this->getConfigValue('newsletter', 'default_checked', $storeId);
    }

    public function getFieldMode($storeId = null): string
    {
        return (string) ($this->getConfigValue('form_styles', 'field_mode', $storeId) ?: 'compact');
    }

    public function usePlaceholders($storeId = null): bool
    {
        return (bool) $this->getConfigValue('form_styles', 'use_placeholders', $storeId);
    }

    public function showTooltips($storeId = null): bool
    {
        return (bool) $this->getConfigValue('form_styles', 'show_tooltips', $storeId);
    }

    public function getDefaultShippingMethod($storeId = null): string
    {
        return (string) ($this->getConfigValue('shipping', 'default_method', $storeId) ?: '');
    }

    public function hideSingleShippingMethod($storeId = null): bool
    {
        return (bool) $this->getConfigValue('shipping', 'hide_single_method', $storeId);
    }

    public function sortShippingByPrice($storeId = null): bool
    {
        return (bool) $this->getConfigValue('shipping', 'sort_by_price', $storeId);
    }

    public function getDefaultPaymentMethod($storeId = null): string
    {
        return (string) ($this->getConfigValue('payment', 'default_method', $storeId) ?: '');
    }

    public function showBillingTitle($storeId = null): bool
    {
        return (bool) $this->getConfigValue('billing', 'show_title', $storeId);
    }

    public function isOrderNoteHandledByAdvancedCart($storeId = null): bool
    {
        if (!$this->_moduleManager->isEnabled('Panth_AdvancedCart')) {
            return false;
        }

        return $this->scopeConfig->isSetFlag('panth_advancedcart/general/enabled', ScopeInterface::SCOPE_STORE, $storeId)
            && $this->scopeConfig->isSetFlag('panth_advancedcart/order_notes/enabled', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isOrderNoteEnabled($storeId = null): bool
    {
        if ($this->isOrderNoteHandledByAdvancedCart($storeId)) {
            return false;
        }

        return (bool) $this->getConfigValue('order_note', 'enabled', $storeId);
    }

    public function getOrderNoteLabel($storeId = null): string
    {
        return (string) ($this->getConfigValue('order_note', 'label', $storeId) ?: 'Order note');
    }

    public function getOrderNotePlaceholder($storeId = null): string
    {
        $value = $this->getConfigValue('order_note', 'placeholder', $storeId);

        return $value === null ? 'Anything we should know about your order?' : (string) $value;
    }

    public function getOrderNoteMaxLength($storeId = null): int
    {
        $value = (int) $this->getConfigValue('order_note', 'max_length', $storeId);

        return $value > 0 ? $value : 500;
    }

    public function getCustomCss($storeId = null): string
    {
        return (string) ($this->getConfigValue('custom_code', 'custom_css', $storeId) ?: '');
    }

    public function getCustomJs($storeId = null): string
    {
        return (string) ($this->getConfigValue('custom_code', 'custom_js', $storeId) ?: '');
    }

    public function getCheckoutBodyClass(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $classes = ['panth-checkout-extended'];
        $classes[] = 'panth-checkout-' . $this->getColumns() . 'col';
        $classes[] = 'panth-sidebar-' . $this->getSidebarPosition();
        $classes[] = 'panth-card-' . $this->getCardStyle();

        if ($this->isSidebarSticky()) {
            $classes[] = 'panth-sidebar-sticky';
        }
        if ($this->showStepIndicators()) {
            $classes[] = 'panth-step-indicators';
        }

        $classes[] = 'panth-form-' . $this->getFieldMode();
        if ($this->usePlaceholders()) {
            $classes[] = 'panth-form-placeholders';
        }
        if ($this->showTooltips()) {
            $classes[] = 'panth-form-tooltips';
        }

        if (!$this->showBillingTitle()) {
            $classes[] = 'panth-billing-title-hidden';
        }

        return implode(' ', $classes);
    }
}
