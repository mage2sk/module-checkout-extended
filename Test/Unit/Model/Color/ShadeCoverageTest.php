<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Model\Color;

use Panth\CheckoutExtended\Model\Color\Shade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ShadeCoverageTest extends TestCase
{
    private Shade $shade;

    protected function setUp(): void
    {
        $this->shade = new Shade();
    }

    #[DataProvider('luminanceProvider')]
    public function testLuminance(string $hex, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, $this->shade->luminance($hex), 0.0001);
    }

    public static function luminanceProvider(): array
    {
        return [
            'black' => ['#000000', 0.0],
            'white' => ['#ffffff', 1.0],
            'short white' => ['fff', 1.0],
            'pure red' => ['#ff0000', 0.2126],
            'pure green' => ['#00ff00', 0.7152],
            'pure blue' => ['#0000ff', 0.0722],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testLuminanceRejectsInvalidInput(string $hex): void
    {
        $this->assertNull($this->shade->luminance($hex));
    }

    public static function invalidProvider(): array
    {
        return [
            'empty' => [''],
            'word' => ['blue'],
            'four digits' => ['#abcd'],
            'bad char' => ['#gg0000'],
        ];
    }

    public function testNegativeRatioKeepsColour(): void
    {
        $this->assertSame('#808080', $this->shade->darken('808080', -0.5));
    }

    public function testFullRatioProducesBlack(): void
    {
        $this->assertSame('#000000', $this->shade->darken('#ABCDEF', 1.0));
    }

    public function testDarkenPadsLowChannels(): void
    {
        $this->assertSame('#050505', $this->shade->darken('#0a0a0a', 0.5));
    }
}
