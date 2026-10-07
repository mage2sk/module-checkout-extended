<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Config;

use PHPUnit\Framework\TestCase;

class DefaultConfigTest extends TestCase
{
    private function defaults(): \SimpleXMLElement
    {
        $file = dirname(__DIR__, 3) . '/etc/config.xml';
        $this->assertTrue(is_file($file));
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string) file_get_contents($file));
        libxml_use_internal_errors($previous);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        return $xml;
    }

    public function testNewsletterCheckboxIsNotPreCheckedByDefault(): void
    {
        $node = $this->defaults()->xpath('/config/default/panth_checkout_extended/newsletter/default_checked');

        $this->assertCount(1, $node);
        $this->assertSame('0', trim((string) $node[0]));
    }

    public function testNewsletterCheckboxItselfStaysEnabledByDefault(): void
    {
        $node = $this->defaults()->xpath('/config/default/panth_checkout_extended/newsletter/enabled');

        $this->assertCount(1, $node);
        $this->assertSame('1', trim((string) $node[0]));
    }
}
