<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\Cart;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Framework\UrlInterface;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Plugin\Cart\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    private const LOGOUT_URL = 'https://store.example/customer/account/logout/';
    private const PLACEHOLDER = 'https://store.example/media/catalog/product/placeholder/default/small_image.jpg';

    private $helperMock;

    private $imageHelperMock;

    private $urlBuilderMock;

    private $subjectMock;

    private ConfigProvider $plugin;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->imageHelperMock = $this->createMock(ImageHelper::class);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);
        $this->subjectMock = $this->createMock(DefaultConfigProvider::class);

        $this->imageHelperMock->method('getDefaultPlaceholderUrl')
            ->with('small_image')
            ->willReturn(self::PLACEHOLDER);
        $this->urlBuilderMock->method('getUrl')
            ->with('customer/account/logout')
            ->willReturn(self::LOGOUT_URL);

        $this->plugin = new ConfigProvider($this->helperMock, $this->imageHelperMock, $this->urlBuilderMock);
    }

    public function testAfterGetConfigIsGatedOnIsEnabled(): void
    {
        $this->helperMock->method('isEnabled')->willReturn(false);

        $this->helperMock->expects($this->never())->method('isQtyIncrementEnabled');
        $this->helperMock->expects($this->never())->method('getDefaultShippingMethod');
        $this->helperMock->expects($this->never())->method('getDefaultPaymentMethod');
        $this->helperMock->expects($this->never())->method('isOrderNoteEnabled');
        $this->urlBuilderMock->expects($this->never())->method('getUrl');

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
        $this->helperMock->method('isOrderNoteEnabled')->willReturn(true);
        $this->helperMock->method('getOrderNoteLabel')->willReturn('Order note');
        $this->helperMock->method('getOrderNotePlaceholder')->willReturn('Anything we should know?');
        $this->helperMock->method('getOrderNoteMaxLength')->willReturn(500);

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
        $this->assertSame(
            [
                'enabled' => true,
                'label' => 'Order note',
                'placeholder' => 'Anything we should know?',
                'maxLength' => 500,
            ],
            $result['panthCheckout']['orderNote']
        );
        $this->assertSame(self::PLACEHOLDER, $result['panthCheckout']['placeholderImage']);
        $this->assertSame(self::LOGOUT_URL, $result['panthCheckout']['logoutUrl']);
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
        $this->helperMock->method('isOrderNoteEnabled')->willReturn(false);
        $this->helperMock->method('getOrderNoteLabel')->willReturn('Order note');
        $this->helperMock->method('getOrderNotePlaceholder')->willReturn('');
        $this->helperMock->method('getOrderNoteMaxLength')->willReturn(500);

        $existing = [
            'quoteData' => ['entity_id' => 7],
            'panthCheckout' => ['stale' => 'value'],
        ];

        $result = $this->plugin->afterGetConfig($this->subjectMock, $existing);

        $this->assertSame(['entity_id' => 7], $result['quoteData']);

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
                'orderNote' => [
                    'enabled' => false,
                    'label' => 'Order note',
                    'placeholder' => '',
                    'maxLength' => 500,
                ],
                'placeholderImage' => self::PLACEHOLDER,
                'logoutUrl' => self::LOGOUT_URL,
            ],
            $result['panthCheckout']
        );
    }

    public function testPlaceholderImageFallsBackToEmptyStringWhenHelperThrows(): void
    {
        $imageHelper = $this->createMock(ImageHelper::class);
        $imageHelper->method('getDefaultPlaceholderUrl')->willThrowException(new \RuntimeException('no placeholder'));

        $plugin = new ConfigProvider($this->helperMock, $imageHelper, $this->urlBuilderMock);

        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('getOrderNoteMaxLength')->willReturn(500);

        $result = $plugin->afterGetConfig($this->subjectMock, []);

        $this->assertSame('', $result['panthCheckout']['placeholderImage']);
        $this->assertSame(self::LOGOUT_URL, $result['panthCheckout']['logoutUrl']);
    }
}
