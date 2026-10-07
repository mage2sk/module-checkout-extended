<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\ScopeInterface;
use Panth\CheckoutExtended\Helper\Data;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataCoverageTest extends TestCase
{
    private $scopeConfig;
    private $moduleManager;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $this->moduleManager = $this->createStub(Manager::class);
    }

    private function helper(): Data
    {
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($this->scopeConfig);
        $context->method('getModuleManager')->willReturn($this->moduleManager);

        return new Data($context);
    }

    private function path(string $suffix): string
    {
        return Data::XML_PATH_PREFIX . $suffix;
    }

    #[DataProvider('legacyAccentProvider')]
    public function testLegacyAccentColorIsTreatedAsUnset(string $configured, string $expected): void
    {
        $this->scopeConfig->method('getValue')->willReturn($configured);

        $this->assertSame($expected, $this->helper()->getAccentColor());
    }

    public static function legacyAccentProvider(): array
    {
        return [
            'legacy lowercase' => ['#1a1a2e', ''],
            'legacy uppercase' => ['#1A1A2E', ''],
            'legacy padded' => ['  #1a1a2e ', ''],
            'custom padded' => [' #336699 ', '#336699'],
        ];
    }

    public function testAccentHoverColorIsTrimmed(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with($this->path('style/accent_hover_color'), ScopeInterface::SCOPE_STORE, 4)
            ->willReturn("  #112233\n");

        $this->assertSame('#112233', $this->helper()->getAccentHoverColor(4));
    }

    public function testAccentHoverColorIsEmptyWhenUnset(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame('', $this->helper()->getAccentHoverColor());
    }

    #[DataProvider('flagGetterProvider')]
    public function testFlagGettersReadTheirOwnPathForTheGivenStore(string $method, string $path): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with($this->path($path), ScopeInterface::SCOPE_STORE, 9)
            ->willReturn('1');

        $this->assertTrue($this->helper()->{$method}(9));
    }

    public static function flagGetterProvider(): array
    {
        return [
            ['isEnabled', 'general/enabled'],
            ['isSidebarSticky', 'layout/sidebar_sticky'],
            ['showStepIndicators', 'style/step_indicators'],
            ['isQtyIncrementEnabled', 'cart/qty_increment_enabled'],
            ['isProductSkuEnabled', 'cart/product_sku_enabled'],
            ['isProductLinkEnabled', 'cart/product_link_enabled'],
            ['isNewsletterEnabled', 'newsletter/enabled'],
            ['isNewsletterCheckedByDefault', 'newsletter/default_checked'],
            ['usePlaceholders', 'form_styles/use_placeholders'],
            ['showTooltips', 'form_styles/show_tooltips'],
            ['hideSingleShippingMethod', 'shipping/hide_single_method'],
            ['sortShippingByPrice', 'shipping/sort_by_price'],
            ['showBillingTitle', 'billing/show_title'],
        ];
    }

    public function testOrderNotePlaceholderKeepsZeroString(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('0');

        $this->assertSame('0', $this->helper()->getOrderNotePlaceholder());
    }

    public function testOrderNoteLabelFallsBackWhenEmpty(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('');

        $this->assertSame('Order note', $this->helper()->getOrderNoteLabel());
    }

    public function testAdvancedCartHandlingIsFalseWhenModuleMissing(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->moduleManager = $this->createMock(Manager::class);
        $this->moduleManager->expects($this->once())
            ->method('isEnabled')
            ->with('Panth_AdvancedCart')
            ->willReturn(false);
        $this->scopeConfig->expects($this->never())->method('isSetFlag');

        $this->assertFalse($this->helper()->isOrderNoteHandledByAdvancedCart(3));
    }

    public function testAdvancedCartHandlingRequiresBothFlags(): void
    {
        $this->moduleManager->method('isEnabled')->willReturn(true);
        $this->scopeConfig->method('isSetFlag')->willReturnMap([
            ['panth_advancedcart/general/enabled', ScopeInterface::SCOPE_STORE, 3, true],
            ['panth_advancedcart/order_notes/enabled', ScopeInterface::SCOPE_STORE, 3, true],
            ['panth_advancedcart/general/enabled', ScopeInterface::SCOPE_STORE, 4, false],
            ['panth_advancedcart/order_notes/enabled', ScopeInterface::SCOPE_STORE, 4, true],
        ]);

        $this->assertTrue($this->helper()->isOrderNoteHandledByAdvancedCart(3));
        $this->assertFalse($this->helper()->isOrderNoteHandledByAdvancedCart(4));
    }

    public function testOrderNoteDisabledWithoutReadingOwnFlagWhenAdvancedCartHandlesIt(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->moduleManager->method('isEnabled')->willReturn(true);
        $this->scopeConfig->method('isSetFlag')->willReturn(true);
        $this->scopeConfig->expects($this->never())->method('getValue');

        $this->assertFalse($this->helper()->isOrderNoteEnabled(2));
    }

    public function testBodyClassIsEmptyWhenModuleDisabled(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame('', $this->helper()->getCheckoutBodyClass());
    }

    public function testBodyClassWithAllOptionalFlagsOff(): void
    {
        $this->scopeConfig->method('getValue')->willReturnCallback(
            fn (string $path) => $path === $this->path('general/enabled') ? '1' : null
        );

        $this->assertSame(
            'panth-checkout-extended panth-checkout-3col panth-sidebar-right panth-card-elevated'
            . ' panth-form-compact panth-billing-title-hidden',
            $this->helper()->getCheckoutBodyClass()
        );
    }

    public function testBodyClassWithEveryOptionalFlagOn(): void
    {
        $values = [
            'general/enabled' => '1',
            'layout/columns' => '1',
            'layout/sidebar_position' => 'left',
            'layout/sidebar_sticky' => '1',
            'style/card_style' => 'bordered',
            'style/step_indicators' => '1',
            'form_styles/field_mode' => 'spacious',
            'form_styles/use_placeholders' => '1',
            'form_styles/show_tooltips' => '1',
            'billing/show_title' => '1',
        ];
        $this->scopeConfig->method('getValue')->willReturnCallback(
            fn (string $path) => $values[substr($path, strlen(Data::XML_PATH_PREFIX))] ?? null
        );

        $this->assertSame(
            'panth-checkout-extended panth-checkout-1col panth-sidebar-left panth-card-bordered'
            . ' panth-sidebar-sticky panth-step-indicators panth-form-spacious panth-form-placeholders'
            . ' panth-form-tooltips',
            $this->helper()->getCheckoutBodyClass()
        );
    }
}
