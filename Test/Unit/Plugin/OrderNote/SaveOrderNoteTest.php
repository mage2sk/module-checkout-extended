<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\OrderNote;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Model\OrderNote\Sanitizer;
use Panth\CheckoutExtended\Plugin\OrderNote\SaveOrderNote;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveOrderNoteTest extends TestCase
{
    private const CART_ID = 77;
    private const STORE_ID = 2;

    private CartRepositoryInterface $cartRepository;

    private Sanitizer $sanitizer;

    private Data $helper;

    private LoggerInterface $logger;

    private PaymentInformationManagementInterface $subject;

    private PaymentInterface $payment;

    private AddressInterface $billing;

    private SaveOrderNote $plugin;

    protected function setUp(): void
    {
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->sanitizer = $this->createMock(Sanitizer::class);
        $this->helper = $this->createMock(Data::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subject = $this->createMock(PaymentInformationManagementInterface::class);
        $this->payment = $this->createMock(PaymentInterface::class);
        $this->billing = $this->createMock(AddressInterface::class);

        $this->plugin = new SaveOrderNote($this->cartRepository, $this->sanitizer, $this->helper, $this->logger);
    }

    #[DataProvider('methodProvider')]
    public function testReturnsArgumentsUnchangedAndSkipsWhenFeatureDisabled(string $method): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->willReturn(false);
        $this->sanitizer->expects($this->never())->method('extract');
        $this->cartRepository->expects($this->never())->method('get');

        $result = $this->plugin->{$method}($this->subject, self::CART_ID, $this->payment, $this->billing);

        $this->assertSame([self::CART_ID, $this->payment, $this->billing], $result);
    }

    #[DataProvider('methodProvider')]
    public function testSkipsWhenModuleDisabled(string $method): void
    {
        $this->helper->method('isEnabled')->willReturn(false);
        $this->helper->method('isOrderNoteEnabled')->willReturn(true);
        $this->cartRepository->expects($this->never())->method('get');

        $result = $this->plugin->{$method}($this->subject, self::CART_ID, $this->payment, null);

        $this->assertSame([self::CART_ID, $this->payment, null], $result);
    }

    #[DataProvider('methodProvider')]
    public function testSkipsWhenAttributeAbsent(string $method): void
    {
        $this->enableFeature();
        $this->sanitizer->method('extract')->with($this->payment)->willReturn(null);
        $this->cartRepository->expects($this->never())->method('get');
        $this->cartRepository->expects($this->never())->method('save');

        $result = $this->plugin->{$method}($this->subject, self::CART_ID, $this->payment, $this->billing);

        $this->assertSame([self::CART_ID, $this->payment, $this->billing], $result);
    }

    #[DataProvider('methodProvider')]
    public function testStoresSanitisedNoteOnQuote(string $method): void
    {
        $this->enableFeature();
        $quote = $this->mockQuote();

        $this->sanitizer->method('extract')->with($this->payment)->willReturn(' <b>side</b> door ');
        $this->sanitizer->expects($this->once())
            ->method('sanitize')
            ->with(' <b>side</b> door ', self::STORE_ID)
            ->willReturn('side door');

        $quote->expects($this->once())->method('setCustomerNote')->with('side door');
        $quote->expects($this->once())->method('setCustomerNoteNotify')->with(true);
        $this->cartRepository->expects($this->once())->method('save')->with($quote);

        $result = $this->plugin->{$method}($this->subject, (string) self::CART_ID, $this->payment, $this->billing);

        $this->assertSame([(string) self::CART_ID, $this->payment, $this->billing], $result);
    }

    #[DataProvider('methodProvider')]
    public function testSkipsSaveWhenQuoteAlreadyCarriesTheNote(string $method): void
    {
        $this->enableFeature();
        $quote = $this->mockQuote('side door', true);

        $this->sanitizer->method('extract')->willReturn('side door');
        $this->sanitizer->method('sanitize')->willReturn('side door');

        $quote->expects($this->never())->method('setCustomerNote');
        $quote->expects($this->never())->method('setCustomerNoteNotify');
        $this->cartRepository->expects($this->never())->method('save');

        $result = $this->plugin->{$method}($this->subject, self::CART_ID, $this->payment, $this->billing);

        $this->assertSame([self::CART_ID, $this->payment, $this->billing], $result);
    }

    public function testSavesWhenOnlyTheNotifyFlagDiffers(): void
    {
        $this->enableFeature();
        $quote = $this->mockQuote('side door', false);

        $this->sanitizer->method('extract')->willReturn('side door');
        $this->sanitizer->method('sanitize')->willReturn('side door');

        $quote->expects($this->once())->method('setCustomerNoteNotify')->with(true);
        $this->cartRepository->expects($this->once())->method('save')->with($quote);

        $this->plugin->beforeSavePaymentInformation($this->subject, self::CART_ID, $this->payment);
    }

    public function testEmptyNoteClearsQuoteNoteAndNotifyFlag(): void
    {
        $this->enableFeature();
        $quote = $this->mockQuote('earlier note', true);

        $this->sanitizer->method('extract')->willReturn('   ');
        $this->sanitizer->method('sanitize')->willReturn('');

        $quote->expects($this->once())->method('setCustomerNote')->with('');
        $quote->expects($this->once())->method('setCustomerNoteNotify')->with(false);
        $this->cartRepository->expects($this->once())->method('save')->with($quote);

        $this->plugin->beforeSavePaymentInformation($this->subject, self::CART_ID, $this->payment);
    }

    public function testEmptyNoteOnEmptyQuoteDoesNotSave(): void
    {
        $this->enableFeature();
        $quote = $this->mockQuote(null, false);

        $this->sanitizer->method('extract')->willReturn('');
        $this->sanitizer->method('sanitize')->willReturn('');

        $quote->expects($this->never())->method('setCustomerNote');
        $this->cartRepository->expects($this->never())->method('save');

        $this->plugin->beforeSavePaymentInformationAndPlaceOrder($this->subject, self::CART_ID, $this->payment);
    }

    public function testRepositoryFailureIsLoggedAndDoesNotBreakCheckout(): void
    {
        $this->enableFeature();
        $this->sanitizer->method('extract')->willReturn('note');
        $this->cartRepository->method('get')->willThrowException(new \RuntimeException('gone'));
        $this->logger->expects($this->once())->method('error');

        $result = $this->plugin->beforeSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::CART_ID,
            $this->payment,
            $this->billing
        );

        $this->assertSame([self::CART_ID, $this->payment, $this->billing], $result);
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
        $this->cartRepository->method('get')->with(self::CART_ID)->willReturn($quote);

        return $quote;
    }
}
