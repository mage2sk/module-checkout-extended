<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin\Cart;

use Magento\Quote\Model\Quote\Item;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class QuoteItemPlugin
{
    private StockRegistryInterface $stockRegistry;

    public function __construct(StockRegistryInterface $stockRegistry)
    {
        $this->stockRegistry = $stockRegistry;
    }

    public function afterToArray(Item $subject, array $result, array $arrAttributes = []): array
    {
        try {
            $product = $subject->getProduct();
            $productId = $product ? $product->getId() : null;

            if ($productId) {
                $stockItem = $this->stockRegistry->getStockItem($productId);
                $qtyIncrements = $stockItem->getQtyIncrements();
                $result['qty_increments'] = $qtyIncrements > 0 ? (float)$qtyIncrements : 1;
            }

            $result['sku'] = (string)$subject->getSku();

            if ($product) {
                $result['product_url'] = (string)$product->getProductUrl();
            }
        } catch (\Exception $e) {
            $result['qty_increments'] = $result['qty_increments'] ?? 1;
        }

        return $result;
    }
}
