<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Panth\CheckoutExtended\Helper\Data;

class AddOrderNoteHistory implements ObserverInterface
{
    private Data $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getData('order');

        if (!$order instanceof Order) {
            return;
        }

        $storeId = (int) $order->getStoreId();

        if (!$this->helper->isEnabled($storeId) || !$this->helper->isOrderNoteEnabled($storeId)) {
            return;
        }

        $note = trim((string) $order->getCustomerNote());

        if ($note === '' || $this->alreadyInHistory($order, $note)) {
            return;
        }

        $order->addCommentToStatusHistory((string) __('Customer note: %1', $note), false, false);
    }

    private function alreadyInHistory(Order $order, string $note): bool
    {
        $histories = $order->getStatusHistories();

        if (!is_array($histories)) {
            return false;
        }

        foreach ($histories as $history) {
            if (!is_object($history) || !method_exists($history, 'getComment')) {
                continue;
            }

            if (trim((string) $history->getComment()) === $note) {
                return true;
            }
        }

        return false;
    }
}
