<?php

declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin\Cart;

use Magento\Quote\Model\Quote\Item;
use Magento\CatalogInventory\Api\StockRegistryInterface;

/**
 * Plugin to add qty_increments, sku and product_url to quote item array data.
 *
 * Ensures the qty_increments value from the stock item, the item sku and the
 * product URL are available in the frontend quote item data (window.checkoutConfig
 * .quoteItemData, keyed by item_id) for the checkout sidebar.
 */
class QuoteItemPlugin
{
    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(StockRegistryInterface $stockRegistry)
    {
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Add qty_increments, sku and product_url to the item array.
     *
     * @param Item $subject
     * @param array $result
     * @param array $arrAttributes
     * @return array
     */
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
