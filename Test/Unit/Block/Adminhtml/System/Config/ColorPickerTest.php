<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Panth\CheckoutExtended\Block\Adminhtml\System\Config\ColorPicker;
use PHPUnit\Framework\TestCase;

class ColorPickerTest extends TestCase
{
    private function render(AbstractElement $element): string
    {
        $reflection = new \ReflectionClass(ColorPicker::class);
        $block = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('_getElementHtml');

        return $method->invoke($block, $element);
    }

    public function testAppendsColorInputScriptTargetingElementId(): void
    {
        $element = $this->createStub(AbstractElement::class);
        $element->method('getElementHtml')->willReturn('<input id="accent" value="#ff0000"/>');
        $element->method('getEscapedValue')->willReturn('#ff0000');
        $element->method('getHtmlId')->willReturn('accent');

        $html = $this->render($element);

        $this->assertStringStartsWith('<input id="accent" value="#ff0000"/>', $html);
        $this->assertStringContainsString('$("#accent")', $html);
        $this->assertStringContainsString('el.attr("type", "color");', $html);
        $this->assertStringContainsString('require(["jquery"]', $html);
        $this->assertStringEndsWith('</script>', $html);
    }

    public function testScriptUsesTheElementSpecificHtmlId(): void
    {
        $element = $this->createStub(AbstractElement::class);
        $element->method('getElementHtml')->willReturn('');
        $element->method('getEscapedValue')->willReturn('');
        $element->method('getHtmlId')->willReturn('panth_checkout_extended_style_accent_hover_color');

        $html = $this->render($element);

        $this->assertStringContainsString('$("#panth_checkout_extended_style_accent_hover_color")', $html);
        $this->assertStringContainsString('"width": "60px"', $html);
        $this->assertSame(1, substr_count($html, '<script'));
    }
}
