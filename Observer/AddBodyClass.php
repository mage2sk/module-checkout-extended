<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use Panth\CheckoutExtended\Helper\Data;

class AddBodyClass implements ObserverInterface
{
    private Data $helper;

    private RequestInterface $request;

    private PageConfig $pageConfig;

    public function __construct(
        Data $helper,
        RequestInterface $request,
        PageConfig $pageConfig
    ) {
        $this->helper = $helper;
        $this->request = $request;
        $this->pageConfig = $pageConfig;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->helper->isEnabled()) {
            return;
        }

        if ($this->request->getFullActionName() !== 'checkout_index_index') {
            return;
        }

        $layout = $observer->getData('layout');

        if ($layout) {
            $layout->getUpdate()->addHandle('panth_checkout_extended_active');
        }

        foreach (array_filter(explode(' ', trim((string) $this->helper->getCheckoutBodyClass()))) as $cls) {
            $this->pageConfig->addBodyClass($cls);
        }
    }
}
