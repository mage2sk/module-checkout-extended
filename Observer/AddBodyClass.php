<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Panth\CheckoutExtended\Helper\Data;

class AddBodyClass implements ObserverInterface
{
    private Data $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->helper->isEnabled()) {
            return;
        }

        $layout = $observer->getData('layout');

        if ($layout) {
            $layout->getUpdate()->addHandle('panth_checkout_extended_active');
        }
    }
}
