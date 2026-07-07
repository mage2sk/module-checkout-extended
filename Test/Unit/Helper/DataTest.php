<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Panth\CheckoutExtended\Helper\Data;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    private $scopeConfigMock;

    private $helper;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getScopeConfig')->willReturn($this->scopeConfigMock);

        $this->helper = new Data($contextMock);
    }

    public function testGetConfigValueBuildsCorrectPath(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                'panth_checkout_extended/general/enabled',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('1');

        $this->assertSame('1', $this->helper->getConfigValue('general', 'enabled'));
    }

    public function testGetConfigValuePassesStoreId(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                'panth_checkout_extended/layout/columns',
                ScopeInterface::SCOPE_STORE,
                5
            )
            ->willReturn('2');

        $this->assertSame('2', $this->helper->getConfigValue('layout', 'columns', 5));
    }

    public function testTypedGettersReturnDefaultsWhenConfigIsNull(): void
    {
        $this->scopeConfigMock->method('getValue')->willReturn(null);

        $this->assertFalse($this->helper->isEnabled());
        $this->assertSame(3, $this->helper->getColumns());
        $this->assertSame('right', $this->helper->getSidebarPosition());
        $this->assertFalse($this->helper->isSidebarSticky());
        $this->assertSame('elevated', $this->helper->getCardStyle());
        $this->assertSame(12, $this->helper->getBorderRadius());
        $this->assertFalse($this->helper->showStepIndicators());
        $this->assertSame('#1a1a2e', $this->helper->getAccentColor());
        $this->assertFalse($this->helper->isQtyIncrementEnabled());
        $this->assertFalse($this->helper->isProductSkuEnabled());
        $this->assertFalse($this->helper->isProductLinkEnabled());
        $this->assertFalse($this->helper->isNewsletterEnabled());
        $this->assertSame('Subscribe to Newsletter', $this->helper->getNewsletterLabel());
        $this->assertFalse($this->helper->isNewsletterCheckedByDefault());
        $this->assertSame('compact', $this->helper->getFieldMode());
        $this->assertFalse($this->helper->usePlaceholders());
        $this->assertFalse($this->helper->showTooltips());
        $this->assertSame('', $this->helper->getDefaultShippingMethod());
        $this->assertFalse($this->helper->hideSingleShippingMethod());
        $this->assertFalse($this->helper->sortShippingByPrice());
        $this->assertSame('', $this->helper->getDefaultPaymentMethod());
        $this->assertFalse($this->helper->showBillingTitle());
        $this->assertSame('', $this->helper->getCustomCss());
        $this->assertSame('', $this->helper->getCustomJs());
    }

    public function testTypedGettersReturnDefaultsWhenConfigIsEmptyString(): void
    {
        $this->scopeConfigMock->method('getValue')->willReturn('');

        $this->assertSame(3, $this->helper->getColumns());
        $this->assertSame('right', $this->helper->getSidebarPosition());
        $this->assertSame('elevated', $this->helper->getCardStyle());
        $this->assertSame(12, $this->helper->getBorderRadius());
        $this->assertSame('#1a1a2e', $this->helper->getAccentColor());
        $this->assertSame('compact', $this->helper->getFieldMode());
        $this->assertSame('Subscribe to Newsletter', $this->helper->getNewsletterLabel());
    }

    public function testTypedGettersReturnConfiguredValues(): void
    {
        $this->scopeConfigMock->method('getValue')->willReturnMap([
            ['panth_checkout_extended/general/enabled', ScopeInterface::SCOPE_STORE, null, '1'],
            ['panth_checkout_extended/layout/columns', ScopeInterface::SCOPE_STORE, null, '2'],
            ['panth_checkout_extended/layout/sidebar_position', ScopeInterface::SCOPE_STORE, null, 'left'],
            ['panth_checkout_extended/layout/sidebar_sticky', ScopeInterface::SCOPE_STORE, null, '1'],
            ['panth_checkout_extended/style/card_style', ScopeInterface::SCOPE_STORE, null, 'flat'],
            ['panth_checkout_extended/style/border_radius', ScopeInterface::SCOPE_STORE, null, '8'],
            ['panth_checkout_extended/style/step_indicators', ScopeInterface::SCOPE_STORE, null, '1'],
            ['panth_checkout_extended/style/accent_color', ScopeInterface::SCOPE_STORE, null, '#ff0000'],
            ['panth_checkout_extended/newsletter/field_label', ScopeInterface::SCOPE_STORE, null, 'Join Us'],
            ['panth_checkout_extended/form_styles/field_mode', ScopeInterface::SCOPE_STORE, null, 'spacious'],
            ['panth_checkout_extended/shipping/default_method', ScopeInterface::SCOPE_STORE, null, 'flatrate_flatrate'],
            ['panth_checkout_extended/payment/default_method', ScopeInterface::SCOPE_STORE, null, 'checkmo'],
            ['panth_checkout_extended/custom_code/custom_css', ScopeInterface::SCOPE_STORE, null, '.foo{}'],
            ['panth_checkout_extended/custom_code/custom_js', ScopeInterface::SCOPE_STORE, null, 'alert(1);'],
        ]);

        $this->assertTrue($this->helper->isEnabled());
        $this->assertSame(2, $this->helper->getColumns());
        $this->assertSame('left', $this->helper->getSidebarPosition());
        $this->assertTrue($this->helper->isSidebarSticky());
        $this->assertSame('flat', $this->helper->getCardStyle());
        $this->assertSame(8, $this->helper->getBorderRadius());
        $this->assertTrue($this->helper->showStepIndicators());
        $this->assertSame('#ff0000', $this->helper->getAccentColor());
        $this->assertSame('Join Us', $this->helper->getNewsletterLabel());
        $this->assertSame('spacious', $this->helper->getFieldMode());
        $this->assertSame('flatrate_flatrate', $this->helper->getDefaultShippingMethod());
        $this->assertSame('checkmo', $this->helper->getDefaultPaymentMethod());
        $this->assertSame('.foo{}', $this->helper->getCustomCss());
        $this->assertSame('alert(1);', $this->helper->getCustomJs());
    }

    public function testGetCheckoutBodyClass(array $config, string $expected): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturnCallback(
                static function (string $path) use ($config) {
                    return $config[$path] ?? null;
                }
            );

        $this->assertSame($expected, $this->helper->getCheckoutBodyClass());
    }

    public function checkoutBodyClassDataProvider(): array
    {
        $prefix = 'panth_checkout_extended/';

        return [
            'disabled returns empty string' => [
                [
                    $prefix . 'general/enabled' => '0',
                ],
                '',
            ],
            'enabled with all defaults' => [
                [
                    $prefix . 'general/enabled' => '1',
                ],
                'panth-checkout-extended panth-checkout-3col panth-sidebar-right'
                . ' panth-card-elevated panth-form-compact panth-billing-title-hidden',
            ],
            'enabled with all optional flags on' => [
                [
                    $prefix . 'general/enabled' => '1',
                    $prefix . 'layout/columns' => '2',
                    $prefix . 'layout/sidebar_position' => 'left',
                    $prefix . 'layout/sidebar_sticky' => '1',
                    $prefix . 'style/card_style' => 'flat',
                    $prefix . 'style/step_indicators' => '1',
                    $prefix . 'form_styles/field_mode' => 'spacious',
                    $prefix . 'form_styles/use_placeholders' => '1',
                    $prefix . 'form_styles/show_tooltips' => '1',
                    $prefix . 'billing/show_title' => '1',
                ],
                'panth-checkout-extended panth-checkout-2col panth-sidebar-left'
                . ' panth-card-flat panth-sidebar-sticky panth-step-indicators'
                . ' panth-form-spacious panth-form-placeholders panth-form-tooltips',
            ],
            'enabled with mixed flags' => [
                [
                    $prefix . 'general/enabled' => '1',
                    $prefix . 'layout/columns' => '1',
                    $prefix . 'layout/sidebar_position' => 'right',
                    $prefix . 'layout/sidebar_sticky' => '0',
                    $prefix . 'style/card_style' => 'bordered',
                    $prefix . 'style/step_indicators' => '1',
                    $prefix . 'form_styles/field_mode' => 'compact',
                    $prefix . 'form_styles/use_placeholders' => '1',
                    $prefix . 'form_styles/show_tooltips' => '0',
                    $prefix . 'billing/show_title' => '0',
                ],
                'panth-checkout-extended panth-checkout-1col panth-sidebar-right'
                . ' panth-card-bordered panth-step-indicators panth-form-compact'
                . ' panth-form-placeholders panth-billing-title-hidden',
            ],
            'enabled sticky sidebar without step indicators' => [
                [
                    $prefix . 'general/enabled' => '1',
                    $prefix . 'layout/sidebar_sticky' => '1',
                    $prefix . 'billing/show_title' => '1',
                ],
                'panth-checkout-extended panth-checkout-3col panth-sidebar-right'
                . ' panth-card-elevated panth-sidebar-sticky panth-form-compact',
            ],
        ];
    }
}
