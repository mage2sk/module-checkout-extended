<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Model\OrderNote;

use Magento\Framework\DataObject;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Model\OrderNote\Sanitizer;
use PHPUnit\Framework\TestCase;

class SanitizerEdgeCasesTest extends TestCase
{
    private function sanitizer(int $max = 100): Sanitizer
    {
        $helper = $this->createStub(Data::class);
        $helper->method('getOrderNoteMaxLength')->willReturn($max);

        return new Sanitizer($helper);
    }

    public function testControlCharactersAreStrippedButNewlinesKept(): void
    {
        $input = 'Ring' . chr(0) . ' bell' . chr(7) . "\nTwice" . chr(127);

        $this->assertSame("Ring bell\nTwice", $this->sanitizer()->sanitize($input));
    }

    public function testMarkupOnlyNoteBecomesEmptyWithoutReadingMaxLength(): void
    {
        $helper = $this->createMock(Data::class);
        $helper->expects($this->never())->method('getOrderNoteMaxLength');

        $this->assertSame('', (new Sanitizer($helper))->sanitize('<b></b>  '));
    }

    public function testMultibyteTruncationCountsCharactersAndTrimsTrailingSpace(): void
    {
        $input = "\u{00e9}\u{00e9}\u{00e9} \u{00e9}\u{00e9}";

        $this->assertSame("\u{00e9}\u{00e9}\u{00e9}", $this->sanitizer(4)->sanitize($input));
    }

    public function testNoteAtExactMaxLengthIsUntouched(): void
    {
        $this->assertSame('abcde', $this->sanitizer(5)->sanitize('abcde'));
    }

    public function testExtractRejectsNonObjects(): void
    {
        $sanitizer = $this->sanitizer();

        $this->assertNull($sanitizer->extract('note'));
        $this->assertNull($sanitizer->extract(['getExtensionAttributes' => 'x']));
    }

    public function testExtractRejectsObjectWithoutExtensionAttributesMethod(): void
    {
        $this->assertNull($this->sanitizer()->extract(new \stdClass()));
    }

    public function testExtractRejectsExtensionWithoutOrderNoteGetter(): void
    {
        $payment = new class {
            public function getExtensionAttributes(): object
            {
                return new \stdClass();
            }
        };

        $this->assertNull($this->sanitizer()->extract($payment));
    }

    public function testExtractReadsFromAnyObjectExposingTheGetter(): void
    {
        $payment = new class {
            public function getExtensionAttributes(): DataObject
            {
                return new class extends DataObject {
                    public function getPanthOrderNote(): float
                    {
                        return 1.5;
                    }
                };
            }
        };

        $this->assertSame('1.5', $this->sanitizer()->extract($payment));
    }
}
