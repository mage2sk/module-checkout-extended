<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\Newsletter;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\DataObject;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Plugin\Newsletter\CustomerSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CustomerSubscriberStoreResolutionTest extends TestCase
{
    private const CART_ID = 11;

    private $subscriptionManager;
    private $cartRepository;
    private $storeManager;
    private $logger;
    private $helper;
    private $payment;

    protected function setUp(): void
    {
        $this->subscriptionManager = $this->createStub(SubscriptionManagerInterface::class);
        $this->cartRepository = $this->createStub(CartRepositoryInterface::class);
        $this->storeManager = $this->createStub(StoreManagerInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->helper = $this->createStub(Data::class);
        $this->payment = $this->createStub(PaymentInterface::class);
        $this->payment->method('getExtensionAttributes')
            ->willReturn(new DataObject(['panth_subscribe_newsletter' => '1']));
    }

    private function quote(int|string $storeId, int|string $customerId, string $email = ''): object
    {
        return new class ($storeId, $customerId, $email) {
            public function __construct(
                private readonly int|string $storeId,
                private readonly int|string $customerId,
                private readonly string $email
            ) {
            }

            public function getStoreId(): int|string
            {
                return $this->storeId;
            }

            public function getCustomerId(): int|string
            {
                return $this->customerId;
            }

            public function getCustomerEmail(): string
            {
                return $this->email;
            }
        };
    }

    private function call(): int|string
    {
        $plugin = new CustomerSubscriber(
            $this->subscriptionManager,
            $this->cartRepository,
            $this->storeManager,
            $this->logger,
            $this->helper
        );

        return $plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->createStub(PaymentInformationManagementInterface::class),
            55,
            self::CART_ID,
            $this->payment
        );
    }

    public function testModuleDisabledShortCircuits(): void
    {
        $this->helper->method('isEnabled')->willReturn(false);
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->cartRepository->expects($this->never())->method('get');

        $this->assertSame(55, $this->call());
    }

    public function testQuoteStoreIdIsPreferredOverCurrentStore(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->cartRepository->expects($this->once())
            ->method('get')
            ->with(self::CART_ID)
            ->willReturn($this->quote('4', '21'));
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->expects($this->never())->method('getStore');
        $this->subscriptionManager = $this->createMock(SubscriptionManagerInterface::class);
        $this->subscriptionManager->expects($this->once())->method('subscribeCustomer')->with(21, 4);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects($this->never())->method('error');

        $this->assertSame(55, $this->call());
    }

    public function testZeroQuoteStoreFallsBackToCurrentStoreForGuestEmail(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->cartRepository->method('get')->willReturn($this->quote(0, 0, 'a@b.test'));
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(8);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);
        $this->subscriptionManager = $this->createMock(SubscriptionManagerInterface::class);
        $this->subscriptionManager->expects($this->never())->method('subscribeCustomer');
        $this->subscriptionManager->expects($this->once())->method('subscribe')->with('a@b.test', 8);

        $this->assertSame(55, $this->call());
    }

    public function testRepositoryFailureIsLoggedWithCartId(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->cartRepository->method('get')->willThrowException(new \RuntimeException('missing quote'));
        $this->subscriptionManager = $this->createMock(SubscriptionManagerInterface::class);
        $this->subscriptionManager->expects($this->never())->method('subscribe');
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Panth CheckoutExtended: Failed to subscribe customer to newsletter.',
                ['cartId' => self::CART_ID, 'exception' => 'missing quote']
            );

        $this->assertSame(55, $this->call());
    }
}
