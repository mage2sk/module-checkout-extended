<?php

declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin\Cart;

use Magento\Quote\Model\Quote\Item;
use Magento\CatalogInventory\Api\StockRegistryInterface;

/**
 * Plugin to add qty_increments to quote item array data.
 *
 * Ensures the qty_increments value from the stock item is available
 * in the frontend quote item data for the checkout sidebar.
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
     * Add qty_increments to the item array.
     *
     * @param Item $subject
     * @param array $result
     * @param array $arrAttributes
     * @return array
     */
    public function afterToArray(Item $subject, array $result, array $arrAttributes = []): array
    {
        try {
            $productId = $subject->getProduct()->getId();

            if ($productId) {
                $stockItem = $this->stockRegistry->getStockItem($productId);
                $qtyIncrements = $stockItem->getQtyIncrements();
                $result['qty_increments'] = $qtyIncrements > 0 ? (float)$qtyIncrements : 1;
            }
        } catch (\Exception $e) {
            $result['qty_increments'] = 1;
        }

        return $result;
    }
}
