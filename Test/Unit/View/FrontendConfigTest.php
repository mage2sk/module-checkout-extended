<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\View;

use PHPUnit\Framework\TestCase;

class FrontendConfigTest extends TestCase
{
    private function moduleFile(string $relative): string
    {
        $path = dirname(__DIR__, 3) . '/' . $relative;
        $this->assertTrue(is_file($path), $relative . ' is missing');

        return (string) file_get_contents($path);
    }

    public function testCoreItemTemplateIsNotMappedGlobally(): void
    {
        $config = $this->moduleFile('view/frontend/requirejs-config.js');

        $this->assertStringNotContainsString('Magento_Checkout/template/summary/item/details.html', $config);
        $this->assertStringNotContainsString('map:', $config);
    }

    public function testItemDetailsMixinSwitchesTemplateOnlyWhenEnabled(): void
    {
        $mixin = $this->moduleFile('view/frontend/web/js/view/cart-details-mixin.js');

        $guard = strpos($mixin, "classList.contains('panth-checkout-extended')");
        $template = strpos($mixin, "template: 'Panth_CheckoutExtended/summary/item/details'");

        $this->assertNotFalse($guard);
        $this->assertNotFalse($template);
        $this->assertGreaterThan($guard, $template);
    }

    public function testShippingSummaryMixinIsRegisteredForCheckoutAndTaxComponents(): void
    {
        $config = $this->moduleFile('view/frontend/requirejs-config.js');
        $needle = "'Panth_CheckoutExtended/js/mixin/summary-shipping-mixin': true";

        $this->assertSame(2, substr_count($config, $needle));
        $this->assertStringContainsString("'Magento_Checkout/js/view/summary/shipping': {", $config);
        $this->assertStringContainsString("'Magento_Tax/js/view/checkout/summary/shipping': {", $config);
    }

    public function testShippingSummaryMixinShowsPendingStateUntilTotalsIncludeShipping(): void
    {
        $mixin = $this->moduleFile('view/frontend/web/js/mixin/summary-shipping-mixin.js');

        $this->assertStringContainsString('isShippingPending', $mixin);
        $this->assertStringContainsString("\$t('Not yet calculated')", $mixin);
        foreach (['isCalculated', 'getValue', 'getExcludingValue', 'getIncludingValue'] as $method) {
            $this->assertStringContainsString($method . ': function', $mixin);
        }
    }

    public function testEveryNewsletterAndOrderNoteFieldHasHelpText(): void
    {
        $xml = simplexml_load_string($this->moduleFile('etc/adminhtml/system.xml'));
        $this->assertNotFalse($xml);

        foreach (['newsletter', 'order_note'] as $groupId) {
            $fields = $xml->xpath(
                "//section[@id='panth_checkout_extended']/group[@id='" . $groupId . "']/field"
            );
            $this->assertNotEmpty($fields);
            foreach ($fields as $field) {
                $this->assertNotSame('', trim((string) $field->comment), $groupId . '/' . $field['id']);
            }
        }
    }

    public function testAutoSaveWaitsForRequiredShippingFields(): void
    {
        $template = $this->moduleFile('view/frontend/templates/checkout_init.phtml');

        $this->assertStringContainsString('function shippingFormComplete()', $template);
        $this->assertStringContainsString('aria-required', $template);
        $this->assertStringContainsString('aria-invalid', $template);
        $this->assertMatchesRegularExpression('/if \(!shippingFormComplete\(\)\) \{\s+console\.debug/', $template);
    }

    public function testThreeColumnLayoutStacksStepsOnNarrowDesktops(): void
    {
        $css = $this->moduleFile('view/frontend/web/css/checkout-extended.css');

        $this->assertStringContainsString('@media (min-width: 901px) and (max-width: 1199px)', $css);
        $this->assertStringContainsString('grid-template-rows: auto 1fr;', $css);
        $this->assertStringNotContainsString('--panth-co-qty-btn: 36px;', $css);
    }
}
