<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\Cart;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Quote\Model\Quote\Item;
use Panth\CheckoutExtended\Plugin\Cart\QuoteItemPlugin;
use PHPUnit\Framework\TestCase;

class QuoteItemPluginEdgeCasesTest extends TestCase
{
    public function testProductWithoutIdSkipsStockLookupButKeepsUrl(): void
    {
        $registry = $this->createMock(StockRegistryInterface::class);
        $registry->expects($this->never())->method('getStockItem');

        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn(null);
        $product->method('getProductUrl')->willReturn('https://shop.test/new.html');

        $item = $this->createStub(Item::class);
        $item->method('getProduct')->willReturn($product);
        $item->method('getSku')->willReturn(null);

        $result = (new QuoteItemPlugin($registry))->afterToArray($item, ['item_id' => 3]);

        $this->assertSame(
            ['item_id' => 3, 'sku' => '', 'product_url' => 'https://shop.test/new.html'],
            $result
        );
    }

    public function testFractionalIncrementIsReturnedAsFloat(): void
    {
        $stockItem = $this->createStub(StockItemInterface::class);
        $stockItem->method('getQtyIncrements')->willReturn('0.5');

        $registry = $this->createMock(StockRegistryInterface::class);
        $registry->expects($this->once())->method('getStockItem')->with(12)->willReturn($stockItem);

        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn(12);
        $product->method('getProductUrl')->willReturn('');

        $item = $this->createStub(Item::class);
        $item->method('getProduct')->willReturn($product);
        $item->method('getSku')->willReturn('SKU-12');

        $result = (new QuoteItemPlugin($registry))->afterToArray($item, []);

        $this->assertSame(0.5, $result['qty_increments']);
        $this->assertSame('SKU-12', $result['sku']);
        $this->assertSame('', $result['product_url']);
    }
}
