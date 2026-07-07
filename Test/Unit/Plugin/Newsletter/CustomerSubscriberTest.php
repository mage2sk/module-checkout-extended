<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\Newsletter;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Plugin\Newsletter\CustomerSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

interface CustomerPaymentExtensionStubInterface
{
    public function getPanthSubscribeNewsletter();
}

interface CustomerQuoteStubInterface
{
    public function getCustomerId();

    public function getCustomerEmail();
}

class CustomerSubscriberTest extends TestCase
{
    private const STORE_ID = 7;
    private const CART_ID = 42;
    private const ORDER_ID = '100000456';

    private SubscriptionManagerInterface $subscriptionManager;

    private CartRepositoryInterface $cartRepository;

    private StoreManagerInterface $storeManager;

    private LoggerInterface $logger;

    private Data $helper;

    private PaymentInformationManagementInterface $subject;

    private PaymentInterface $paymentMethod;

    private CustomerSubscriber $plugin;

    protected function setUp(): void
    {
        $this->subscriptionManager = $this->createMock(SubscriptionManagerInterface::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->helper = $this->createMock(Data::class);
        $this->subject = $this->createMock(PaymentInformationManagementInterface::class);
        $this->paymentMethod = $this->createMock(PaymentInterface::class);

        $this->plugin = new CustomerSubscriber(
            $this->subscriptionManager,
            $this->cartRepository,
            $this->storeManager,
            $this->logger,
            $this->helper
        );
    }

    public function testReturnsResultUnchangedAndNeverSubscribesWhenNewsletterDisabled(): void
    {
        $this->helper->method('isNewsletterEnabled')->willReturn(false);
        $this->paymentMethod->expects($this->never())->method('getExtensionAttributes');
        $this->cartRepository->expects($this->never())->method('get');
        $this->subscriptionManager->expects($this->never())->method('subscribe');
        $this->subscriptionManager->expects($this->never())->method('subscribeCustomer');

        $result = $this->plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::ORDER_ID,
            self::CART_ID,
            $this->paymentMethod
        );

        $this->assertSame(self::ORDER_ID, $result);
    }

    public function testSubscribesByCustomerIdWhenCustomerIdIsAvailable(): void
    {
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->mockExtensionAttribute(true);
        $this->mockStore();

        $this->mockQuote('15', 'customer@example.com');

        $this->subscriptionManager->expects($this->once())
            ->method('subscribeCustomer')
            ->with(15, self::STORE_ID)
            ->willReturn($this->createMock(Subscriber::class));
        $this->subscriptionManager->expects($this->never())->method('subscribe');

        $result = $this->plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::ORDER_ID,
            self::CART_ID,
            $this->paymentMethod
        );

        $this->assertSame(self::ORDER_ID, $result);
    }

    public function testFallsBackToEmailSubscriptionWhenCustomerIdIsMissing(): void
    {
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->mockExtensionAttribute(true);
        $this->mockStore();
        $this->mockQuote(null, 'fallback@example.com');

        $this->subscriptionManager->expects($this->once())
            ->method('subscribe')
            ->with('fallback@example.com', self::STORE_ID)
            ->willReturn($this->createMock(Subscriber::class));
        $this->subscriptionManager->expects($this->never())->method('subscribeCustomer');

        $result = $this->plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::ORDER_ID,
            self::CART_ID,
            $this->paymentMethod
        );

        $this->assertSame(self::ORDER_ID, $result);
    }

    public function testDoesNotSubscribeWhenAttributeIsFalseOrAbsent(?bool $attributeValue): void
    {
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->mockExtensionAttribute($attributeValue);
        $this->cartRepository->expects($this->never())->method('get');
        $this->subscriptionManager->expects($this->never())->method('subscribe');
        $this->subscriptionManager->expects($this->never())->method('subscribeCustomer');

        $result = $this->plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::ORDER_ID,
            self::CART_ID,
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
        $this->subscriptionManager->expects($this->never())->method('subscribeCustomer');

        $result = $this->plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::ORDER_ID,
            self::CART_ID,
            $this->paymentMethod
        );

        $this->assertSame(self::ORDER_ID, $result);
    }

    public function testSwallowsSubscribeCustomerExceptionAndStillReturnsOrderResult(): void
    {
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->mockExtensionAttribute(true);
        $this->mockStore();
        $this->mockQuote(15, 'customer@example.com');

        $this->subscriptionManager->expects($this->once())
            ->method('subscribeCustomer')
            ->with(15, self::STORE_ID)
            ->willThrowException(new \Exception('Subscription failed'));

        $this->logger->expects($this->once())->method('error');

        $result = $this->plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::ORDER_ID,
            self::CART_ID,
            $this->paymentMethod
        );

        $this->assertSame(self::ORDER_ID, $result);
    }

    private function mockExtensionAttribute(?bool $value): void
    {
        $extensionAttributes = $this->createMock(CustomerPaymentExtensionStubInterface::class);
        $extensionAttributes->method('getPanthSubscribeNewsletter')->willReturn($value);
        $this->paymentMethod->method('getExtensionAttributes')->willReturn($extensionAttributes);
    }

    private function mockStore(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $this->storeManager->method('getStore')->willReturn($store);
    }

    private function mockQuote($customerId, ?string $customerEmail): void
    {
        $quote = $this->createMock(CustomerQuoteStubInterface::class);
        $quote->method('getCustomerId')->willReturn($customerId);
        $quote->method('getCustomerEmail')->willReturn($customerEmail);

        $this->cartRepository->expects($this->once())
            ->method('get')
            ->with(self::CART_ID)
            ->willReturn($quote);
    }
}
