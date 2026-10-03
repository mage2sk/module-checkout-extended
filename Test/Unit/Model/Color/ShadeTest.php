<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Model\Color;

use Panth\CheckoutExtended\Model\Color\Shade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ShadeTest extends TestCase
{
    private Shade $shade;

    protected function setUp(): void
    {
        $this->shade = new Shade();
    }

    public static function normalizeCases(): array
    {
        return [
            ['#EA580C', '#ea580c'],
            ['ea580c', '#ea580c'],
            ['#abc', '#aabbcc'],
            [' #1F2E44 ', '#1f2e44'],
            ['#EA580Cdd', null],
            ['red', null],
            ['', null],
        ];
    }

    #[DataProvider('normalizeCases')]
    public function testNormalize(string $input, ?string $expected): void
    {
        $this->assertSame($expected, $this->shade->normalize($input));
    }

    public static function darkenCases(): array
    {
        return [
            ['#EA580C', 0.15, '#c74b0a'],
            ['#1a1a2e', 0.15, '#161627'],
            ['#1F2E44', 0.15, '#1a273a'],
            ['#ffffff', 0.15, '#d9d9d9'],
            ['#000000', 0.15, '#000000'],
        ];
    }

    #[DataProvider('darkenCases')]
    public function testDarkenProducesADarkerShade(string $input, float $ratio, string $expected): void
    {
        $result = $this->shade->darken($input, $ratio);
        $this->assertSame($expected, $result);
        $this->assertLessThanOrEqual($this->shade->luminance($input), $this->shade->luminance($result));
    }

    public function testDarkenRejectsInvalidInput(): void
    {
        $this->assertNull($this->shade->darken('#EA580Cdd', 0.15));
        $this->assertNull($this->shade->darken('orange', 0.15));
    }

    public function testRatioIsClamped(): void
    {
        $this->assertSame('#000000', $this->shade->darken('#EA580C', 5));
        $this->assertSame('#ea580c', $this->shade->darken('#EA580C', -1));
    }
}
