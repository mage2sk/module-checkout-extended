<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\Checkout;

use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Payment\Api\Data\PaymentMethodInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Plugin\Checkout\PaymentMethodsConfigProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PaymentMethodsConfigProviderTest extends TestCase
{
    private const STORE_ID = 3;

    private $paymentMethodList;
    private $storeManager;
    private $helper;
    private $logger;
    private $customerSession;
    private $subscriberFactory;
    private $subject;
    private PaymentMethodsConfigProvider $plugin;

    protected function setUp(): void
    {
        $this->paymentMethodList = $this->createMock(PaymentMethodListInterface::class);
        $this->storeManager = $this->createStub(StoreManagerInterface::class);
        $this->helper = $this->createStub(Data::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->customerSession = $this->createStub(CustomerSession::class);
        $this->subscriberFactory = $this->createMock(SubscriberFactory::class);
        $this->subject = $this->createStub(DefaultConfigProvider::class);

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->plugin = new PaymentMethodsConfigProvider(
            $this->paymentMethodList,
            $this->storeManager,
            $this->helper,
            $this->logger,
            $this->customerSession,
            $this->subscriberFactory
        );
    }

    private function method(string $code, string $title): PaymentMethodInterface
    {
        $method = $this->createStub(PaymentMethodInterface::class);
        $method->method('getCode')->willReturn($code);
        $method->method('getTitle')->willReturn($title);

        return $method;
    }

    public function testReturnsResultUntouchedWhenModuleDisabled(): void
    {
        $this->helper->method('isEnabled')->willReturn(false);
        $this->paymentMethodList->expects($this->never())->method('getActiveList');
        $this->subscriberFactory->expects($this->never())->method('create');
        $this->logger->expects($this->never())->method('error');

        $input = ['foo' => 'bar'];

        $this->assertSame($input, $this->plugin->afterGetConfig($this->subject, $input));
    }

    public function testGuestGetsUnsubscribedFlagAndActivePaymentMethods(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->customerSession->method('getCustomer')->willReturn(new DataObject());
        $this->subscriberFactory->expects($this->never())->method('create');
        $this->logger->expects($this->never())->method('error');
        $this->paymentMethodList->expects($this->once())
            ->method('getActiveList')
            ->with(self::STORE_ID)
            ->willReturn([$this->method('checkmo', 'Check / Money order'), $this->method('free', 'No Payment')]);

        $result = $this->plugin->afterGetConfig($this->subject, ['paymentMethods' => []]);

        $this->assertFalse($result['panthNewsletterSubscribed']);
        $this->assertSame(
            [
                ['code' => 'checkmo', 'title' => 'Check / Money order'],
                ['code' => 'free', 'title' => 'No Payment'],
            ],
            $result['paymentMethods']
        );
    }

    public function testLoggedInCustomerSubscriptionStateIsExposed(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->customerSession->method('getCustomer')
            ->willReturn(new DataObject(['id' => 9, 'email' => 'jane@example.com']));

        $subscriber = $this->createMock(Subscriber::class);
        $subscriber->expects($this->once())->method('loadByEmail')->with('jane@example.com')->willReturnSelf();
        $subscriber->expects($this->once())->method('isSubscribed')->willReturn(true);
        $this->subscriberFactory->expects($this->once())->method('create')->willReturn($subscriber);
        $this->paymentMethodList->expects($this->never())->method('getActiveList');
        $this->logger->expects($this->never())->method('error');

        $existing = [['code' => 'checkmo', 'title' => 'Check']];
        $result = $this->plugin->afterGetConfig($this->subject, ['paymentMethods' => $existing]);

        $this->assertTrue($result['panthNewsletterSubscribed']);
        $this->assertSame($existing, $result['paymentMethods']);
    }

    public function testSubscriberLookupFailureFallsBackToFalse(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->customerSession->method('getCustomer')
            ->willReturn(new DataObject(['id' => 9, 'email' => 'jane@example.com']));
        $this->subscriberFactory->expects($this->once())
            ->method('create')
            ->willThrowException(new \RuntimeException('db down'));
        $this->paymentMethodList->expects($this->once())->method('getActiveList')->willReturn([]);
        $this->logger->expects($this->never())->method('error');

        $result = $this->plugin->afterGetConfig($this->subject, []);

        $this->assertFalse($result['panthNewsletterSubscribed']);
        $this->assertSame([], $result['paymentMethods']);
    }

    public function testNullCustomerKeepsFlagFalse(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->customerSession->method('getCustomer')->willReturn(null);
        $this->subscriberFactory->expects($this->never())->method('create');
        $this->logger->expects($this->never())->method('error');
        $this->paymentMethodList->expects($this->once())
            ->method('getActiveList')
            ->willReturn([$this->method('checkmo', 'Check')]);

        $result = $this->plugin->afterGetConfig($this->subject, []);

        $this->assertFalse($result['panthNewsletterSubscribed']);
        $this->assertSame([['code' => 'checkmo', 'title' => 'Check']], $result['paymentMethods']);
    }

    public function testPaymentListFailureIsLoggedAndKeyLeftUnset(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->customerSession->method('getCustomer')->willReturn(new DataObject());
        $this->subscriberFactory->expects($this->never())->method('create');
        $this->paymentMethodList->expects($this->once())
            ->method('getActiveList')
            ->willThrowException(new \RuntimeException('boom'));
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Panth CheckoutExtended: Failed to load payment methods: boom');

        $result = $this->plugin->afterGetConfig($this->subject, ['other' => 1]);

        $this->assertArrayNotHasKey('paymentMethods', $result);
        $this->assertSame(1, $result['other']);
        $this->assertFalse($result['panthNewsletterSubscribed']);
    }
}
