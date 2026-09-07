<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\OrderNote;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Model\OrderNote\Sanitizer;
use Panth\CheckoutExtended\Plugin\OrderNote\SaveGuestOrderNote;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveGuestOrderNoteTest extends TestCase
{
    private const CART_ID = 'masked-cart-id';
    private const EMAIL = 'guest@example.com';
    private const STORE_ID = 3;

    private GuestCartRepositoryInterface $guestCartRepository;

    private CartRepositoryInterface $cartRepository;

    private Sanitizer $sanitizer;

    private Data $helper;

    private LoggerInterface $logger;

    private GuestPaymentInformationManagementInterface $subject;

    private PaymentInterface $payment;

    private AddressInterface $billing;

    private SaveGuestOrderNote $plugin;

    protected function setUp(): void
    {
        $this->guestCartRepository = $this->createMock(GuestCartRepositoryInterface::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->sanitizer = $this->createMock(Sanitizer::class);
        $this->helper = $this->createMock(Data::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subject = $this->createMock(GuestPaymentInformationManagementInterface::class);
        $this->payment = $this->createMock(PaymentInterface::class);
        $this->billing = $this->createMock(AddressInterface::class);

        $this->plugin = new SaveGuestOrderNote(
            $this->guestCartRepository,
            $this->cartRepository,
            $this->sanitizer,
            $this->helper,
            $this->logger
        );
    }

    #[DataProvider('methodProvider')]
    public function testReturnsArgumentsUnchangedWhenFeatureDisabled(string $method): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->willReturn(false);
        $this->guestCartRepository->expects($this->never())->method('get');

        $result = $this->plugin->{$method}($this->subject, self::CART_ID, self::EMAIL, $this->payment, $this->billing);

        $this->assertSame([self::CART_ID, self::EMAIL, $this->payment, $this->billing], $result);
    }

    #[DataProvider('methodProvider')]
    public function testSkipsWhenAttributeAbsent(string $method): void
    {
        $this->enableFeature();
        $this->sanitizer->method('extract')->willReturn(null);
        $this->guestCartRepository->expects($this->never())->method('get');
        $this->cartRepository->expects($this->never())->method('save');

        $result = $this->plugin->{$method}($this->subject, self::CART_ID, self::EMAIL, $this->payment, null);

        $this->assertSame([self::CART_ID, self::EMAIL, $this->payment, null], $result);
    }

    #[DataProvider('methodProvider')]
    public function testStoresSanitisedNoteOnGuestQuote(string $method): void
    {
        $this->enableFeature();
        $quote = $this->mockQuote();

        $this->sanitizer->method('extract')->with($this->payment)->willReturn('Leave at the side door');
        $this->sanitizer->expects($this->once())
            ->method('sanitize')
            ->with('Leave at the side door', self::STORE_ID)
            ->willReturn('Leave at the side door');

        $quote->expects($this->once())->method('setCustomerNote')->with('Leave at the side door');
        $quote->expects($this->once())->method('setCustomerNoteNotify')->with(true);
        $this->cartRepository->expects($this->once())->method('save')->with($quote);

        $result = $this->plugin->{$method}($this->subject, self::CART_ID, self::EMAIL, $this->payment, $this->billing);

        $this->assertSame([self::CART_ID, self::EMAIL, $this->payment, $this->billing], $result);
    }

    #[DataProvider('methodProvider')]
    public function testSkipsSaveWhenGuestQuoteAlreadyCarriesTheNote(string $method): void
    {
        $this->enableFeature();
        $quote = $this->mockQuote('Leave at the side door', true);

        $this->sanitizer->method('extract')->willReturn('Leave at the side door');
        $this->sanitizer->method('sanitize')->willReturn('Leave at the side door');

        $quote->expects($this->never())->method('setCustomerNote');
        $quote->expects($this->never())->method('setCustomerNoteNotify');
        $this->cartRepository->expects($this->never())->method('save');

        $result = $this->plugin->{$method}($this->subject, self::CART_ID, self::EMAIL, $this->payment, $this->billing);

        $this->assertSame([self::CART_ID, self::EMAIL, $this->payment, $this->billing], $result);
    }

    public function testRepositoryFailureIsLoggedAndDoesNotBreakCheckout(): void
    {
        $this->enableFeature();
        $this->sanitizer->method('extract')->willReturn('note');
        $this->guestCartRepository->method('get')->willThrowException(new \RuntimeException('no such cart'));
        $this->cartRepository->expects($this->never())->method('save');
        $this->logger->expects($this->once())->method('error');

        $result = $this->plugin->beforeSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::CART_ID,
            self::EMAIL,
            $this->payment,
            $this->billing
        );

        $this->assertSame([self::CART_ID, self::EMAIL, $this->payment, $this->billing], $result);
    }

    public static function methodProvider(): array
    {
        return [
            'place order' => ['beforeSavePaymentInformationAndPlaceOrder'],
            'save payment information' => ['beforeSavePaymentInformation'],
        ];
    }

    private function enableFeature(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->willReturn(true);
    }

    private function mockQuote(?string $currentNote = null, bool $currentNotify = false)
    {
        $quote = $this->createMock(OrderNoteQuoteStubInterface::class);
        $quote->method('getStoreId')->willReturn(self::STORE_ID);
        $quote->method('getCustomerNote')->willReturn($currentNote);
        $quote->method('getCustomerNoteNotify')->willReturn($currentNotify);
        $this->guestCartRepository->method('get')->with(self::CART_ID)->willReturn($quote);

        return $quote;
    }
}
