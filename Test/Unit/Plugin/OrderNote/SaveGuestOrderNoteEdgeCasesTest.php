<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\OrderNote;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Model\OrderNote\Sanitizer;
use Panth\CheckoutExtended\Plugin\OrderNote\SaveGuestOrderNote;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveGuestOrderNoteEdgeCasesTest extends TestCase
{
    private const CART_ID = 'masked-xyz';

    private $guestCartRepository;
    private $cartRepository;
    private $sanitizer;
    private $helper;
    private $logger;
    private $payment;

    protected function setUp(): void
    {
        $this->guestCartRepository = $this->createStub(GuestCartRepositoryInterface::class);
        $this->cartRepository = $this->createStub(CartRepositoryInterface::class);
        $this->sanitizer = $this->createStub(Sanitizer::class);
        $this->helper = $this->createStub(Data::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->payment = $this->createStub(PaymentInterface::class);
    }

    private function invoke(): array
    {
        $plugin = new SaveGuestOrderNote(
            $this->guestCartRepository,
            $this->cartRepository,
            $this->sanitizer,
            $this->helper,
            $this->logger
        );

        return $plugin->beforeSavePaymentInformationAndPlaceOrder(
            $this->createStub(GuestPaymentInformationManagementInterface::class),
            self::CART_ID,
            'g@example.com',
            $this->payment
        );
    }

    private function quote(int|string $storeId, ?string $note, bool $notify)
    {
        $quote = $this->createMock(OrderNoteQuoteStubInterface::class);
        $quote->method('getStoreId')->willReturn($storeId);
        $quote->method('getCustomerNote')->willReturn($note);
        $quote->method('getCustomerNoteNotify')->willReturn($notify);

        return $quote;
    }

    private function enable(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->willReturn(true);
    }

    public function testModuleDisabledSkipsWithoutExtracting(): void
    {
        $this->helper->method('isEnabled')->willReturn(false);
        $this->helper->method('isOrderNoteEnabled')->willReturn(true);
        $this->sanitizer = $this->createMock(Sanitizer::class);
        $this->sanitizer->expects($this->never())->method('extract');

        $this->assertSame([self::CART_ID, 'g@example.com', $this->payment, null], $this->invoke());
    }

    public function testEmptyNoteClearsExistingGuestNote(): void
    {
        $this->enable();
        $quote = $this->quote('7', 'old', true);
        $quote->expects($this->once())->method('setCustomerNote')->with('');
        $quote->expects($this->once())->method('setCustomerNoteNotify')->with(false);

        $this->sanitizer = $this->createMock(Sanitizer::class);
        $this->sanitizer->method('extract')->willReturn('   ');
        $this->sanitizer->expects($this->once())->method('sanitize')->with('   ', 7)->willReturn('');
        $this->guestCartRepository = $this->createMock(GuestCartRepositoryInterface::class);
        $this->guestCartRepository->expects($this->once())->method('get')->with(self::CART_ID)->willReturn($quote);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->cartRepository->expects($this->once())->method('save')->with($quote);

        $this->invoke();
    }

    public function testSavesWhenOnlyNotifyFlagDiffers(): void
    {
        $this->enable();
        $quote = $this->quote(1, 'Hi', false);
        $quote->expects($this->once())->method('setCustomerNote')->with('Hi');
        $quote->expects($this->once())->method('setCustomerNoteNotify')->with(true);

        $this->sanitizer->method('extract')->willReturn('Hi');
        $this->sanitizer->method('sanitize')->willReturn('Hi');
        $this->guestCartRepository->method('get')->willReturn($quote);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->cartRepository->expects($this->once())->method('save')->with($quote);

        $this->invoke();
    }

    public function testSaveFailureIsLoggedWithCartId(): void
    {
        $this->enable();
        $this->sanitizer->method('extract')->willReturn('note');
        $this->sanitizer->method('sanitize')->willReturn('note');
        $this->guestCartRepository->method('get')->willReturn($this->createStub(OrderNoteQuoteStubInterface::class));
        $this->cartRepository->method('save')->willThrowException(new \RuntimeException('locked'));
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Panth CheckoutExtended: Failed to store the guest order note on the quote.',
                ['cartId' => self::CART_ID, 'exception' => 'locked']
            );

        $this->assertSame(self::CART_ID, $this->invoke()[0]);
    }
}
