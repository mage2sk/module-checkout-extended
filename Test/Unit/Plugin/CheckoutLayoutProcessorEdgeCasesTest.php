<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin;

use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Plugin\CheckoutLayoutProcessor;
use PHPUnit\Framework\TestCase;

class CheckoutLayoutProcessorEdgeCasesTest extends TestCase
{
    private $helper;
    private CheckoutLayoutProcessor $processor;

    protected function setUp(): void
    {
        $this->helper = $this->createStub(Data::class);
        $this->helper->method('isEnabled')->willReturn(true);
        $this->processor = new CheckoutLayoutProcessor($this->helper);
    }

    private function layoutWithSteps(array $steps): array
    {
        return ['components' => ['checkout' => ['children' => ['steps' => ['children' => $steps]]]]];
    }

    private function summary(array $result): array
    {
        return $result['components']['checkout']['children']['sidebar']['children']['summary']['children'];
    }

    public function testCreatesSummaryBranchWhenLayoutIsEmpty(): void
    {
        $result = $this->processor->process([]);
        $summary = $this->summary($result);

        $this->assertSame(['panth-newsletter', 'panth-order-note', 'panth-place-order'], array_keys($summary));
        $this->assertArrayNotHasKey('cart_items', $summary);
        $this->assertArrayNotHasKey('totals', $summary);
        $this->assertArrayNotHasKey('panth-discount', $summary);
        $this->assertSame(50, $summary['panth-place-order']['sortOrder']);
    }

    public function testShippingPlaceholdersHandleOddFieldShapes(): void
    {
        $this->helper->method('usePlaceholders')->willReturn(true);

        $layout = $this->layoutWithSteps([
            'shipping-step' => ['children' => ['shippingAddress' => ['children' => [
                'shipping-address-fieldset' => ['children' => [
                    'scalar' => 'not-an-array',
                    'empty_label' => ['label' => ''],
                    'phrase_label' => ['label' => ['text' => 'Company']],
                    'string_config' => ['label' => 'Zip', 'config' => 'legacy'],
                    'scalar_children' => ['label' => 'Street', 'children' => 'none'],
                    'group' => ['children' => [['label' => 'Line 1'], ['children' => [['label' => 'Deep']]]]],
                ]],
            ]]]],
        ]);

        $fields = $this->processor->process($layout)['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];

        $this->assertSame('not-an-array', $fields['scalar']);
        $this->assertArrayNotHasKey('placeholder', $fields['empty_label']);
        $this->assertArrayNotHasKey('placeholder', $fields['phrase_label']);
        $this->assertSame(['placeholder' => 'Zip'], $fields['string_config']['config']);
        $this->assertSame('Zip', $fields['string_config']['placeholder']);
        $this->assertSame('none', $fields['scalar_children']['children']);
        $this->assertSame('Street', $fields['scalar_children']['placeholder']);
        $this->assertSame('Line 1', $fields['group']['children'][0]['config']['placeholder']);
        $this->assertSame('Deep', $fields['group']['children'][1]['children'][0]['placeholder']);
        $this->assertArrayNotHasKey('placeholder', $fields['group']);
    }

    public function testPaymentListThatIsNotAnArrayIsLeftAlone(): void
    {
        $this->helper->method('usePlaceholders')->willReturn(true);

        $layout = $this->layoutWithSteps([
            'billing-step' => ['children' => ['payment' => ['children' => [
                'payments-list' => ['children' => 'invalid'],
            ]]]],
        ]);

        $result = $this->processor->process($layout);

        $this->assertSame(
            'invalid',
            $result['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']['payments-list']['children']
        );
    }

    public function testPaymentMethodsWithoutFormFieldsAreSkipped(): void
    {
        $this->helper->method('usePlaceholders')->willReturn(true);

        $layout = $this->layoutWithSteps([
            'billing-step' => ['children' => ['payment' => ['children' => [
                'payments-list' => ['children' => [
                    'free' => ['component' => 'free'],
                    'card' => ['children' => ['form-fields' => ['children' => ['cc' => ['label' => 'Card']]]]],
                ]],
            ]]]],
        ]);

        $list = $this->processor->process($layout)['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']['payments-list']['children'];

        $this->assertSame(['component' => 'free'], $list['free']);
        $this->assertSame('Card', $list['card']['children']['form-fields']['children']['cc']['placeholder']);
    }

    public function testDiscountRelocationKeepsOriginalPropertiesAndSiblings(): void
    {
        $layout = $this->layoutWithSteps([
            'billing-step' => ['children' => ['payment' => ['children' => [
                'afterMethods' => ['children' => [
                    'discount' => ['component' => 'discount', 'sortOrder' => 5, 'config' => ['a' => 1]],
                    'other' => ['component' => 'other'],
                ]],
            ]]]],
        ]);

        $result = $this->processor->process($layout);
        $after = $result['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']['afterMethods']['children'];

        $this->assertSame(['other' => ['component' => 'other']], $after);
        $this->assertSame(
            ['component' => 'discount', 'sortOrder' => 40, 'config' => ['a' => 1]],
            $this->summary($result)['panth-discount']
        );
    }
}
