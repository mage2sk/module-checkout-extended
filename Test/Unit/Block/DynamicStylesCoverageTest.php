<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Block;

use Magento\Framework\View\Element\Template\Context;
use Panth\CheckoutExtended\Block\DynamicStyles;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Model\Color\Shade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DynamicStylesCoverageTest extends TestCase
{
    private $helper;

    protected function setUp(): void
    {
        $this->helper = $this->createStub(Data::class);
    }

    private function block(?Shade $shade = null): DynamicStyles
    {
        return new DynamicStyles($this->createStub(Context::class), $this->helper, [], $shade);
    }

    #[DataProvider('delegateProvider')]
    public function testRemainingGettersDelegateToHelper(string $method, $value): void
    {
        $helper = $this->createMock(Data::class);
        $helper->expects($this->once())->method($method)->willReturn($value);
        $block = new DynamicStyles($this->createStub(Context::class), $helper);

        $this->assertSame($value, $block->{$method}());
    }

    public static function delegateProvider(): array
    {
        return [
            ['getFieldMode', 'spacious'],
            ['usePlaceholders', true],
            ['showTooltips', true],
            ['showBillingTitle', false],
            ['getDefaultShippingMethod', 'flatrate_flatrate'],
            ['hideSingleShippingMethod', true],
            ['sortShippingByPrice', false],
            ['getDefaultPaymentMethod', 'checkmo'],
        ];
    }

    public function testConfiguredShortHexHoverIsExpanded(): void
    {
        $this->helper->method('getAccentHoverColor')->willReturn('ABC');
        $this->helper->method('getAccentColor')->willReturn('#000000');

        $this->assertSame('#aabbcc', $this->block()->getAccentHoverColor());
    }

    public function testDerivedHoverDarkensAccentByFifteenPercent(): void
    {
        $this->helper->method('getAccentHoverColor')->willReturn('');
        $this->helper->method('getAccentColor')->willReturn('#c8c8c8');

        $this->assertSame('#aaaaaa', $this->block()->getAccentHoverColor());
    }

    public function testInvalidAccentYieldsEmptyHover(): void
    {
        $this->helper->method('getAccentHoverColor')->willReturn('');
        $this->helper->method('getAccentColor')->willReturn('red');

        $this->assertSame('', $this->block()->getAccentHoverColor());
    }

    public function testHoverFallsBackToAccentWhenDarkenFails(): void
    {
        $this->helper->method('getAccentHoverColor')->willReturn('');
        $this->helper->method('getAccentColor')->willReturn('#123456');

        $shade = $this->createMock(Shade::class);
        $shade->method('normalize')->willReturnMap([
            ['', null],
            ['#123456', '#123456'],
        ]);
        $shade->expects($this->once())
            ->method('darken')
            ->with('#123456', DynamicStyles::HOVER_DARKEN_RATIO)
            ->willReturn(null);

        $this->assertSame('#123456', $this->block($shade)->getAccentHoverColor());
    }
}
