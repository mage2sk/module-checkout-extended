<?php

declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin;

use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Plugin\CheckoutLayoutProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Panth\CheckoutExtended\Plugin\CheckoutLayoutProcessor
 */
class CheckoutLayoutProcessorTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var CheckoutLayoutProcessor
     */
    private CheckoutLayoutProcessor $processor;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->processor = new CheckoutLayoutProcessor($this->helperMock);
    }

    /**
     * Build a representative checkout jsLayout fixture.
     */
    private function getJsLayout(): array
    {
        return [
            'components' => [
                'checkout' => [
                    'children' => [
                        'sidebar' => [
                            'children' => [
                                'summary' => [
                                    'children' => [
                                        'totals' => [
                                            'component' => 'Magento_Checkout/js/view/summary/totals',
                                        ],
                                        'cart_items' => [
                                            'component' => 'Magento_Checkout/js/view/summary/cart-items',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'steps' => [
                            'children' => [
                                'shipping-step' => [
                                    'children' => [
                                        'shippingAddress' => [
                                            'children' => [
                                                'shipping-address-fieldset' => [
                                                    'children' => [
                                                        'firstname' => [
                                                            'label' => 'First Name',
                                                        ],
                                                        'region_id_input' => [
                                                            'label' => 'State/Province',
                                                            'config' => [
                                                                'customScope' => 'shippingAddress',
                                                            ],
                                                        ],
                                                        'street' => [
                                                            'children' => [
                                                                0 => [
                                                                    'label' => 'Street Address',
                                                                ],
                                                            ],
                                                        ],
                                                        'no_label_field' => [
                                                            'component' => 'some/component',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'billing-step' => [
                                    'children' => [
                                        'payment' => [
                                            'children' => [
                                                'afterMethods' => [
                                                    'children' => [
                                                        'discount' => [
                                                            'component' => 'Magento_SalesRule/js/view/payment/discount',
                                                            'displayArea' => 'afterMethods',
                                                        ],
                                                        'billing-address-form' => [
                                                            'children' => [
                                                                'form-fields' => [
                                                                    'children' => [
                                                                        'telephone' => [
                                                                            'label' => 'Phone Number',
                                                                        ],
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                                'payments-list' => [
                                                    'children' => [
                                                        'checkmo-form' => [
                                                            'children' => [
                                                                'form-fields' => [
                                                                    'children' => [
                                                                        'city' => [
                                                                            'label' => 'City',
                                                                        ],
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testProcessReturnsLayoutUnchangedWhenModuleDisabled(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(false);
        $this->helperMock->expects($this->never())->method('isNewsletterEnabled');

        $jsLayout = $this->getJsLayout();
        $result = $this->processor->process($jsLayout);

        $this->assertSame($jsLayout, $result);
        $this->assertArrayNotHasKey(
            'panth-newsletter',
            $result['components']['checkout']['children']['sidebar']['children']['summary']['children']
        );
    }

    public function testProcessInjectsNewsletterComponentWithHelperValues(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('isNewsletterEnabled')->willReturn(true);
        $this->helperMock->method('getNewsletterLabel')->willReturn('Join Our Newsletter');
        $this->helperMock->method('isNewsletterCheckedByDefault')->willReturn(true);
        $this->helperMock->method('usePlaceholders')->willReturn(false);

        $result = $this->processor->process($this->getJsLayout());

        $summary = $result['components']['checkout']['children']['sidebar']['children']['summary']['children'];

        $this->assertArrayHasKey('panth-newsletter', $summary);
        $this->assertSame(
            [
                'component' => 'Panth_CheckoutExtended/js/view/newsletter',
                'sortOrder' => 20,
                'config' => [
                    'enabled' => true,
                    'label' => 'Join Our Newsletter',
                    'defaultChecked' => true,
                ],
            ],
            $summary['panth-newsletter']
        );
    }

    public function testProcessSetsSortOrdersOnExistingSummaryChildren(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('getNewsletterLabel')->willReturn('Subscribe to Newsletter');
        $this->helperMock->method('usePlaceholders')->willReturn(false);

        $result = $this->processor->process($this->getJsLayout());

        $summary = $result['components']['checkout']['children']['sidebar']['children']['summary']['children'];

        $this->assertSame(0, $summary['cart_items']['sortOrder']);
        $this->assertSame(10, $summary['totals']['sortOrder']);
    }

    public function testProcessRelocatesDiscountIntoSummaryAndUnsetsOriginal(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('getNewsletterLabel')->willReturn('Subscribe to Newsletter');
        $this->helperMock->method('usePlaceholders')->willReturn(false);

        $result = $this->processor->process($this->getJsLayout());

        $summary = $result['components']['checkout']['children']['sidebar']['children']['summary']['children'];

        $this->assertArrayHasKey('panth-discount', $summary);
        $this->assertSame(
            'Magento_SalesRule/js/view/payment/discount',
            $summary['panth-discount']['component']
        );
        $this->assertSame(40, $summary['panth-discount']['sortOrder']);

        $this->assertArrayNotHasKey(
            'discount',
            $result['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
                ['afterMethods']['children']
        );
    }

    public function testProcessAddsSidebarPlaceOrderComponent(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('getNewsletterLabel')->willReturn('Subscribe to Newsletter');
        $this->helperMock->method('usePlaceholders')->willReturn(false);

        $result = $this->processor->process($this->getJsLayout());

        $summary = $result['components']['checkout']['children']['sidebar']['children']['summary']['children'];

        $this->assertArrayHasKey('panth-place-order', $summary);
        $this->assertSame(
            [
                'component' => 'Panth_CheckoutExtended/js/view/sidebar-place-order',
                'sortOrder' => 50,
            ],
            $summary['panth-place-order']
        );
    }

    public function testProcessAppliesPlaceholdersWhenEnabled(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('getNewsletterLabel')->willReturn('Subscribe to Newsletter');
        $this->helperMock->method('usePlaceholders')->willReturn(true);

        $result = $this->processor->process($this->getJsLayout());

        $shippingFields = $result['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children'];

        // Leaf field: label copied into both placeholder slots, label preserved
        $this->assertSame('First Name', $shippingFields['firstname']['placeholder']);
        $this->assertSame('First Name', $shippingFields['firstname']['config']['placeholder']);
        $this->assertSame('First Name', $shippingFields['firstname']['label']);

        // Existing config array is preserved while placeholder is added
        $this->assertSame('State/Province', $shippingFields['region_id_input']['config']['placeholder']);
        $this->assertSame('shippingAddress', $shippingFields['region_id_input']['config']['customScope']);

        // Nested children (street lines) are recursed into
        $this->assertSame('Street Address', $shippingFields['street']['children'][0]['placeholder']);
        $this->assertSame('Street Address', $shippingFields['street']['children'][0]['config']['placeholder']);

        // Fields without a label remain untouched
        $this->assertArrayNotHasKey('placeholder', $shippingFields['no_label_field']);

        // Global billing address fieldset
        $billingFields = $result['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']
            ['afterMethods']['children']['billing-address-form']['children']
            ['form-fields']['children'];
        $this->assertSame('Phone Number', $billingFields['telephone']['placeholder']);
        $this->assertSame('Phone Number', $billingFields['telephone']['config']['placeholder']);

        // Per-payment-method billing fieldsets
        $paymentFields = $result['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']
            ['payments-list']['children']['checkmo-form']['children']
            ['form-fields']['children'];
        $this->assertSame('City', $paymentFields['city']['placeholder']);
        $this->assertSame('City', $paymentFields['city']['config']['placeholder']);
    }

    public function testProcessDoesNotApplyPlaceholdersWhenDisabled(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('getNewsletterLabel')->willReturn('Subscribe to Newsletter');
        $this->helperMock->method('usePlaceholders')->willReturn(false);

        $result = $this->processor->process($this->getJsLayout());

        $shippingFields = $result['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children'];

        $this->assertArrayNotHasKey('placeholder', $shippingFields['firstname']);
        $this->assertArrayNotHasKey('config', $shippingFields['firstname']);

        $billingFields = $result['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']
            ['afterMethods']['children']['billing-address-form']['children']
            ['form-fields']['children'];
        $this->assertArrayNotHasKey('placeholder', $billingFields['telephone']);
    }

    public function testProcessHandlesMinimalLayoutWithoutExpectedKeysGracefully(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('isNewsletterEnabled')->willReturn(false);
        $this->helperMock->method('getNewsletterLabel')->willReturn('Subscribe to Newsletter');
        $this->helperMock->method('isNewsletterCheckedByDefault')->willReturn(false);
        $this->helperMock->method('usePlaceholders')->willReturn(true);

        // No sidebar/summary, no steps, no discount, no fieldsets at all
        $result = $this->processor->process([]);

        $this->assertIsArray($result);

        $summary = $result['components']['checkout']['children']['sidebar']['children']['summary']['children'];
        $this->assertArrayHasKey('panth-newsletter', $summary);
        $this->assertArrayHasKey('panth-place-order', $summary);
        $this->assertArrayNotHasKey('panth-discount', $summary);
        $this->assertArrayNotHasKey('cart_items', $summary);
        $this->assertArrayNotHasKey('totals', $summary);
    }
}
