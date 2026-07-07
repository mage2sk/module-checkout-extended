<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\Newsletter;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Plugin\Newsletter\GuestSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

interface GuestPaymentExtensionStubInterface
{
    public function getPanthSubscribeNewsletter();
}

class GuestSubscriberTest extends TestCase
{
    private const STORE_ID = 5;
    private const EMAIL = 'guest@example.com';
    private const CART_ID = 'masked-cart-id';
    private const ORDER_ID = '100000123';

    private SubscriptionManagerInterface $subscriptionManager;

    private StoreManagerInterface $storeManager;

    private LoggerInterface $logger;

    private Data $helper;

    private GuestPaymentInformationManagementInterface $subject;

    private PaymentInterface $paymentMethod;

    private GuestSubscriber $plugin;

    protected function setUp(): void
    {
        $this->subscriptionManager = $this->createMock(SubscriptionManagerInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->helper = $this->createMock(Data::class);
        $this->subject = $this->createMock(GuestPaymentInformationManagementInterface::class);
        $this->paymentMethod = $this->createMock(PaymentInterface::class);

        $this->plugin = new GuestSubscriber(
            $this->subscriptionManager,
            $this->storeManager,
            $this->logger,
            $this->helper
        );
    }

    public function testReturnsResultUnchangedAndNeverSubscribesWhenNewsletterDisabled(): void
    {
        $this->helper->method('isNewsletterEnabled')->willReturn(false);
        $this->paymentMethod->expects($this->never())->method('getExtensionAttributes');
        $this->subscriptionManager->expects($this->never())->method('subscribe');

        $result = $this->plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::ORDER_ID,
            self::CART_ID,
            self::EMAIL,
            $this->paymentMethod
        );

        $this->assertSame(self::ORDER_ID, $result);
    }

    public function testSubscribesEmailWhenEnabledAndExtensionAttributeIsTrue(): void
    {
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->mockExtensionAttribute(true);
        $this->mockStore();

        $this->subscriptionManager->expects($this->once())
            ->method('subscribe')
            ->with(self::EMAIL, self::STORE_ID)
            ->willReturn($this->createMock(Subscriber::class));

        $result = $this->plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::ORDER_ID,
            self::CART_ID,
            self::EMAIL,
            $this->paymentMethod
        );

        $this->assertSame(self::ORDER_ID, $result);
    }

    public function testDoesNotSubscribeWhenAttributeIsFalseOrAbsent(?bool $attributeValue): void
    {
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->mockExtensionAttribute($attributeValue);
        $this->subscriptionManager->expects($this->never())->method('subscribe');

        $result = $this->plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::ORDER_ID,
            self::CART_ID,
            self::EMAIL,
            $this->paymentMethod
        );

        $this->assertSame(self::ORDER_ID, $result);
    }

    public static function notOptedInProvider(): array
    {
        return [
            'attribute false' => [false],
            'attribute absent (null)' => [null],
        ];
    }

    public function testReturnsResultWhenExtensionAttributesObjectIsNull(): void
    {
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->paymentMethod->method('getExtensionAttributes')->willReturn(null);
        $this->subscriptionManager->expects($this->never())->method('subscribe');

        $result = $this->plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::ORDER_ID,
            self::CART_ID,
            self::EMAIL,
            $this->paymentMethod
        );

        $this->assertSame(self::ORDER_ID, $result);
    }

    public function testSwallowsSubscribeExceptionAndStillReturnsOrderResult(): void
    {
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->mockExtensionAttribute(true);
        $this->mockStore();

        $this->subscriptionManager->expects($this->once())
            ->method('subscribe')
            ->with(self::EMAIL, self::STORE_ID)
            ->willThrowException(new \Exception('SMTP down'));

        $this->logger->expects($this->once())->method('error');

        $result = $this->plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::ORDER_ID,
            self::CART_ID,
            self::EMAIL,
            $this->paymentMethod
        );

        $this->assertSame(self::ORDER_ID, $result);
    }

    private function mockExtensionAttribute(?bool $value): void
    {
        $extensionAttributes = $this->createMock(GuestPaymentExtensionStubInterface::class);
        $extensionAttributes->method('getPanthSubscribeNewsletter')->willReturn($value);
        $this->paymentMethod->method('getExtensionAttributes')->willReturn($extensionAttributes);
    }

    private function mockStore(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $this->storeManager->method('getStore')->willReturn($store);
    }
}
