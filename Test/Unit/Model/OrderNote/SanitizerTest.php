<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Model\OrderNote;

use Magento\Quote\Api\Data\PaymentInterface;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Model\OrderNote\Sanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

interface OrderNotePaymentExtensionStubInterface
{
    public function getPanthOrderNote();
}

class SanitizerTest extends TestCase
{
    private Data $helper;

    private Sanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->helper->method('getOrderNoteMaxLength')->willReturn(20);
        $this->sanitizer = new Sanitizer($this->helper);
    }

    #[DataProvider('sanitizeProvider')]
    public function testSanitize(?string $input, string $expected): void
    {
        $this->assertSame($expected, $this->sanitizer->sanitize($input));
    }

    public static function sanitizeProvider(): array
    {
        return [
            'null' => [null, ''],
            'empty' => ['', ''],
            'whitespace only' => ["  \n\t ", ''],
            'trimmed' => ['  leave at the door ', 'leave at the door'],
            'tags stripped' => ['<b>ring</b> the <em>bell</em>', 'ring the bell'],
            'control chars removed' => ["ring\x00 the\x07 bell", 'ring the bell'],
            'newlines kept' => ["line one\nline two", "line one\nline two"],
            'clamped to max length' => [str_repeat('a', 30), str_repeat('a', 20)],
            'multibyte clamp counts characters' => [str_repeat("\u{00e9}", 25), str_repeat("\u{00e9}", 20)],
            'trailing space after clamp trimmed' => ['aaaaaaaaaaaaaaaaaaa bbbbbbbb', 'aaaaaaaaaaaaaaaaaaa'],
        ];
    }

    public function testSanitizePassesStoreIdToHelper(): void
    {
        $helper = $this->createMock(Data::class);
        $helper->expects($this->once())->method('getOrderNoteMaxLength')->with(3)->willReturn(4);

        $this->assertSame('abcd', (new Sanitizer($helper))->sanitize('abcdef', 3));
    }

    public function testExtractReturnsNullWhenExtensionAttributesMissing(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getExtensionAttributes')->willReturn(null);

        $this->assertNull($this->sanitizer->extract($payment));
        $this->assertNull($this->sanitizer->extract(null));
    }

    #[DataProvider('extractProvider')]
    public function testExtractReadsTheAttribute($value, ?string $expected): void
    {
        $extension = $this->createMock(OrderNotePaymentExtensionStubInterface::class);
        $extension->method('getPanthOrderNote')->willReturn($value);
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getExtensionAttributes')->willReturn($extension);

        $this->assertSame($expected, $this->sanitizer->extract($payment));
    }

    public static function extractProvider(): array
    {
        return [
            'null attribute' => [null, null],
            'empty string' => ['', ''],
            'text' => ['side door', 'side door'],
            'integer cast' => [42, '42'],
        ];
    }
}
