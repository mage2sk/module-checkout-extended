<?php

declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\Cart;

use Magento\Checkout\Model\DefaultConfigProvider;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Plugin\Cart\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Panth\CheckoutExtended\Plugin\Cart\ConfigProvider
 */
class ConfigProviderTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var DefaultConfigProvider|MockObject
     */
    private $subjectMock;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $plugin;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->subjectMock = $this->createMock(DefaultConfigProvider::class);
        $this->plugin = new ConfigProvider($this->helperMock);
    }

    public function testAfterGetConfigIsGatedOnIsEnabled(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(false);

        $this->helperMock->expects($this->never())->method('isQtyIncrementEnabled');
        $this->helperMock->expects($this->never())->method('getDefaultShippingMethod');
        $this->helperMock->expects($this->never())->method('getDefaultPaymentMethod');

        $result = ['quoteData' => ['entity_id' => 42]];

        $this->assertSame(
            $result,
            $this->plugin->afterGetConfig($this->subjectMock, $result)
        );
    }

    public function testAfterGetConfigDoesNotAddPanthCheckoutKeyWhenDisabled(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(false);

        $result = $this->plugin->afterGetConfig($this->subjectMock, []);

        $this->assertArrayNotHasKey('panthCheckout', $result);
    }

    public function testAfterGetConfigAddsPanthCheckoutConfigWhenEnabled(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('isQtyIncrementEnabled')->willReturn(true);
        $this->helperMock->method('isProductSkuEnabled')->willReturn(false);
        $this->helperMock->method('isProductLinkEnabled')->willReturn(true);
        $this->helperMock->method('getDefaultShippingMethod')->willReturn('flatrate_flatrate');
        $this->helperMock->method('hideSingleShippingMethod')->willReturn(true);
        $this->helperMock->method('sortShippingByPrice')->willReturn(false);
        $this->helperMock->method('getDefaultPaymentMethod')->willReturn('checkmo');

        $result = $this->plugin->afterGetConfig($this->subjectMock, []);

        $this->assertArrayHasKey('panthCheckout', $result);
        $this->assertSame(
            [
                'qtyIncrement' => true,
                'showSku' => false,
                'showLink' => true,
            ],
            $result['panthCheckout']['cart']
        );
        $this->assertSame(
            [
                'defaultMethod' => 'flatrate_flatrate',
                'hideSingleMethod' => true,
                'sortByPrice' => false,
            ],
            $result['panthCheckout']['shipping']
        );
        $this->assertSame(
            [
                'defaultMethod' => 'checkmo',
            ],
            $result['panthCheckout']['payment']
        );
    }

    public function testAfterGetConfigPreservesExistingResultKeysAndOverwritesPanthCheckout(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('isQtyIncrementEnabled')->willReturn(false);
        $this->helperMock->method('isProductSkuEnabled')->willReturn(true);
        $this->helperMock->method('isProductLinkEnabled')->willReturn(false);
        $this->helperMock->method('getDefaultShippingMethod')->willReturn('');
        $this->helperMock->method('hideSingleShippingMethod')->willReturn(false);
        $this->helperMock->method('sortShippingByPrice')->willReturn(true);
        $this->helperMock->method('getDefaultPaymentMethod')->willReturn('');

        $existing = [
            'quoteData' => ['entity_id' => 7],
            'panthCheckout' => ['stale' => 'value'],
        ];

        $result = $this->plugin->afterGetConfig($this->subjectMock, $existing);

        // Unrelated keys are untouched
        $this->assertSame(['entity_id' => 7], $result['quoteData']);

        // panthCheckout is fully rebuilt from helper values
        $this->assertArrayNotHasKey('stale', $result['panthCheckout']);
        $this->assertSame(
            [
                'cart' => [
                    'qtyIncrement' => false,
                    'showSku' => true,
                    'showLink' => false,
                ],
                'shipping' => [
                    'defaultMethod' => '',
                    'hideSingleMethod' => false,
                    'sortByPrice' => true,
                ],
                'payment' => [
                    'defaultMethod' => '',
                ],
            ],
            $result['panthCheckout']
        );
    }
}
