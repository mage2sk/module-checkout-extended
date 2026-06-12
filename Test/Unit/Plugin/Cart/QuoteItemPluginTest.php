<?php

declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\Cart;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Quote\Model\Quote\Item;
use Panth\CheckoutExtended\Plugin\Cart\QuoteItemPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Panth\CheckoutExtended\Plugin\Cart\QuoteItemPlugin
 */
class QuoteItemPluginTest extends TestCase
{
    /**
     * @var StockRegistryInterface|MockObject
     */
    private $stockRegistryMock;

    /**
     * @var Item|MockObject
     */
    private $quoteItemMock;

    /**
     * @var QuoteItemPlugin
     */
    private QuoteItemPlugin $plugin;

    protected function setUp(): void
    {
        $this->stockRegistryMock = $this->createMock(StockRegistryInterface::class);
        $this->quoteItemMock = $this->createMock(Item::class);
        $this->plugin = new QuoteItemPlugin($this->stockRegistryMock);
    }

    /**
     * @return Product|MockObject
     */
    private function createProductMock(int $productId, string $productUrl)
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getId')->willReturn($productId);
        $productMock->method('getProductUrl')->willReturn($productUrl);

        return $productMock;
    }

    public function testAfterToArrayAddsSkuProductUrlAndQtyIncrements(): void
    {
        $productMock = $this->createProductMock(123, 'https://example.com/example-product.html');

        $this->quoteItemMock->method('getProduct')->willReturn($productMock);
        $this->quoteItemMock->method('getSku')->willReturn('EX-PROD-001');
        $this->quoteItemMock->method('getQty')->willReturn(2.0);

        $stockItemMock = $this->createMock(StockItemInterface::class);
        $stockItemMock->method('getQtyIncrements')->willReturn(5.0);

        $this->stockRegistryMock->expects($this->once())
            ->method('getStockItem')
            ->with(123)
            ->willReturn($stockItemMock);

        $result = $this->plugin->afterToArray($this->quoteItemMock, ['item_id' => '10', 'qty' => 2]);

        $this->assertSame('EX-PROD-001', $result['sku']);
        $this->assertSame('https://example.com/example-product.html', $result['product_url']);
        $this->assertSame(5.0, $result['qty_increments']);

        // Pre-existing keys remain untouched
        $this->assertSame('10', $result['item_id']);
        $this->assertSame(2, $result['qty']);
    }

    public function testAfterToArrayDefaultsQtyIncrementsToOneWhenNotPositive(): void
    {
        $productMock = $this->createProductMock(55, 'https://example.com/other.html');

        $this->quoteItemMock->method('getProduct')->willReturn($productMock);
        $this->quoteItemMock->method('getSku')->willReturn('OTHER-SKU');

        $stockItemMock = $this->createMock(StockItemInterface::class);
        $stockItemMock->method('getQtyIncrements')->willReturn(0.0);

        $this->stockRegistryMock->method('getStockItem')->willReturn($stockItemMock);

        $result = $this->plugin->afterToArray($this->quoteItemMock, []);

        $this->assertSame(1, $result['qty_increments']);
    }

    public function testAfterToArrayHandlesNullProductGracefully(): void
    {
        $this->quoteItemMock->method('getProduct')->willReturn(null);
        $this->quoteItemMock->method('getSku')->willReturn('ORPHAN-SKU');

        $this->stockRegistryMock->expects($this->never())->method('getStockItem');

        $result = $this->plugin->afterToArray($this->quoteItemMock, ['item_id' => '11']);

        $this->assertSame('ORPHAN-SKU', $result['sku']);
        $this->assertArrayNotHasKey('product_url', $result);
        $this->assertArrayNotHasKey('qty_increments', $result);
        $this->assertSame('11', $result['item_id']);
    }

    public function testAfterToArrayCatchesExceptionAndDefaultsQtyIncrements(): void
    {
        $productMock = $this->createProductMock(99, 'https://example.com/broken.html');

        $this->quoteItemMock->method('getProduct')->willReturn($productMock);

        $this->stockRegistryMock->method('getStockItem')
            ->willThrowException(new \Exception('Stock item not found'));

        $result = $this->plugin->afterToArray($this->quoteItemMock, ['item_id' => '12']);

        $this->assertSame(1, $result['qty_increments']);
        $this->assertSame('12', $result['item_id']);
    }

    public function testAfterToArrayPreservesExistingQtyIncrementsOnException(): void
    {
        $productMock = $this->createProductMock(99, 'https://example.com/broken.html');

        $this->quoteItemMock->method('getProduct')->willReturn($productMock);

        $this->stockRegistryMock->method('getStockItem')
            ->willThrowException(new \Exception('Stock item not found'));

        $result = $this->plugin->afterToArray($this->quoteItemMock, ['qty_increments' => 3.0]);

        $this->assertSame(3.0, $result['qty_increments']);
    }
}
