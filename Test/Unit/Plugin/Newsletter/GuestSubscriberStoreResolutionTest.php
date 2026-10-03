<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\Newsletter;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\DataObject;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Plugin\Newsletter\GuestSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GuestSubscriberStoreResolutionTest extends TestCase
{
    private const CART_ID = 'masked-abc';
    private const EMAIL = 'visitor@example.com';

    private $subscriptionManager;
    private $storeManager;
    private $logger;
    private $helper;
    private $guestCartRepository;
    private $payment;

    protected function setUp(): void
    {
        $this->subscriptionManager = $this->createMock(SubscriptionManagerInterface::class);
        $this->storeManager = $this->createStub(StoreManagerInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->helper = $this->createStub(Data::class);
        $this->guestCartRepository = $this->createStub(GuestCartRepositoryInterface::class);
        $this->payment = $this->createStub(PaymentInterface::class);
        $this->payment->method('getExtensionAttributes')
            ->willReturn(new DataObject(['panth_subscribe_newsletter' => true]));
    }

    private function call(bool $withRepository = true): int|string
    {
        $plugin = new GuestSubscriber(
            $this->subscriptionManager,
            $this->storeManager,
            $this->logger,
            $this->helper,
            $withRepository ? $this->guestCartRepository : null
        );

        return $plugin->afterSavePaymentInformationAndPlaceOrder(
            $this->createStub(GuestPaymentInformationManagementInterface::class),
            '000000077',
            self::CART_ID,
            self::EMAIL,
            $this->payment
        );
    }

    private function currentStore(int $id): void
    {
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn($id);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);
    }

    public function testModuleDisabledShortCircuits(): void
    {
        $this->helper->method('isEnabled')->willReturn(false);
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->subscriptionManager->expects($this->never())->method('subscribe');

        $this->assertSame('000000077', $this->call());
    }

    public function testQuoteStoreIdFromGuestCartIsUsed(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->guestCartRepository = $this->createMock(GuestCartRepositoryInterface::class);
        $this->guestCartRepository->expects($this->once())
            ->method('get')
            ->with(self::CART_ID)
            ->willReturn(new DataObject(['store_id' => '3']));
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->expects($this->never())->method('getStore');
        $this->subscriptionManager->expects($this->once())->method('subscribe')->with(self::EMAIL, 3);

        $this->assertSame('000000077', $this->call());
    }

    public function testZeroQuoteStoreFallsBackToCurrentStore(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->guestCartRepository->method('get')->willReturn(new DataObject(['store_id' => 0]));
        $this->currentStore(6);
        $this->subscriptionManager->expects($this->once())->method('subscribe')->with(self::EMAIL, 6);

        $this->assertSame('000000077', $this->call());
    }

    public function testGuestCartLookupFailureFallsBackToCurrentStoreSilently(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->guestCartRepository->method('get')->willThrowException(new \RuntimeException('expired'));
        $this->currentStore(2);
        $this->subscriptionManager->expects($this->once())->method('subscribe')->with(self::EMAIL, 2);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects($this->never())->method('error');

        $this->assertSame('000000077', $this->call());
    }

    public function testStoreManagerFailureIsLoggedWithEmail(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isNewsletterEnabled')->willReturn(true);
        $this->storeManager->method('getStore')->willThrowException(new \RuntimeException('no store'));
        $this->subscriptionManager->expects($this->never())->method('subscribe');
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Panth CheckoutExtended: Failed to subscribe guest to newsletter.',
                ['email' => self::EMAIL, 'exception' => 'no store']
            );

        $this->assertSame('000000077', $this->call(false));
    }
}
