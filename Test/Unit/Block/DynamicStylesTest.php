<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Block;

use Magento\Framework\View\Element\Template\Context;
use Panth\CheckoutExtended\Block\DynamicStyles;
use Panth\CheckoutExtended\Helper\Data;
use PHPUnit\Framework\TestCase;

class DynamicStylesTest extends TestCase
{
    private $helperMock;

    private $block;

    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);
        $this->helperMock = $this->createMock(Data::class);

        $this->block = new DynamicStyles($contextMock, $this->helperMock);
    }

    public function testGetHelperReturnsInjectedHelper(): void
    {
        $this->assertSame($this->helperMock, $this->block->getHelper());
    }

    public function testIsEnabledDelegatesToHelper(bool $value): void
    {
        $this->helperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn($value);

        $this->assertSame($value, $this->block->isEnabled());
    }

    public function isEnabledDataProvider(): array
    {
        return [
            'enabled' => [true],
            'disabled' => [false],
        ];
    }

    public function testGetAccentColorDelegatesToHelper(): void
    {
        $this->helperMock->expects($this->once())
            ->method('getAccentColor')
            ->willReturn('#1a1a2e');

        $this->assertSame('#1a1a2e', $this->block->getAccentColor());
    }

    public function testGetBorderRadiusDelegatesToHelper(): void
    {
        $this->helperMock->expects($this->once())
            ->method('getBorderRadius')
            ->willReturn(12);

        $this->assertSame(12, $this->block->getBorderRadius());
    }

    public function testGetCardStyleDelegatesToHelper(): void
    {
        $this->helperMock->expects($this->once())
            ->method('getCardStyle')
            ->willReturn('glass');

        $this->assertSame('glass', $this->block->getCardStyle());
    }

    public function testGetColumnsDelegatesToHelper(): void
    {
        $this->helperMock->expects($this->once())
            ->method('getColumns')
            ->willReturn(3);

        $this->assertSame(3, $this->block->getColumns());
    }

    public function testGetSidebarPositionDelegatesToHelper(): void
    {
        $this->helperMock->expects($this->once())
            ->method('getSidebarPosition')
            ->willReturn('left');

        $this->assertSame('left', $this->block->getSidebarPosition());
    }

    public function testIsSidebarStickyDelegatesToHelper(): void
    {
        $this->helperMock->expects($this->once())
            ->method('isSidebarSticky')
            ->willReturn(true);

        $this->assertTrue($this->block->isSidebarSticky());
    }

    public function testShowStepIndicatorsDelegatesToHelper(): void
    {
        $this->helperMock->expects($this->once())
            ->method('showStepIndicators')
            ->willReturn(true);

        $this->assertTrue($this->block->showStepIndicators());
    }

    public function testGetCustomCssDelegatesToHelper(): void
    {
        $css = '.panth-checkout-extended { color: red; }';

        $this->helperMock->expects($this->once())
            ->method('getCustomCss')
            ->willReturn($css);

        $this->assertSame($css, $this->block->getCustomCss());
    }

    public function testGetCustomJsDelegatesToHelper(): void
    {
        $js = 'console.log("checkout");';

        $this->helperMock->expects($this->once())
            ->method('getCustomJs')
            ->willReturn($js);

        $this->assertSame($js, $this->block->getCustomJs());
    }

    public function testGetCheckoutBodyClassDelegatesToHelper(): void
    {
        $bodyClass = 'panth-checkout-extended panth-checkout-3col panth-sidebar-right panth-card-elevated';

        $this->helperMock->expects($this->once())
            ->method('getCheckoutBodyClass')
            ->willReturn($bodyClass);

        $this->assertSame($bodyClass, $this->block->getCheckoutBodyClass());
    }
}
