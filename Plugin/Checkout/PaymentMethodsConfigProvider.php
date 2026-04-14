<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin\Checkout;

use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\CheckoutExtended\Helper\Data;
use Psr\Log\LoggerInterface;

class PaymentMethodsConfigProvider
{
    private PaymentMethodListInterface $paymentMethodList;
    private StoreManagerInterface $storeManager;
    private Data $helper;
    private LoggerInterface $logger;
    private CustomerSession $customerSession;
    private SubscriberFactory $subscriberFactory;

    public function __construct(
        PaymentMethodListInterface $paymentMethodList,
        StoreManagerInterface $storeManager,
        Data $helper,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        SubscriberFactory $subscriberFactory
    ) {
        $this->paymentMethodList    = $paymentMethodList;
        $this->storeManager         = $storeManager;
        $this->helper               = $helper;
        $this->logger               = $logger;
        $this->customerSession      = $customerSession;
        $this->subscriberFactory    = $subscriberFactory;
    }

    public function afterGetConfig(DefaultConfigProvider $subject, array $result): array
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }

        // Expose newsletter subscription status — must run before early return
        try {
            $result['panthNewsletterSubscribed'] = false;
            $customer = $this->customerSession->getCustomer();
            if ($customer && $customer->getId()) {
                $subscriber = $this->subscriberFactory->create()->loadByEmail($customer->getEmail());
                $result['panthNewsletterSubscribed'] = $subscriber->isSubscribed();
            }
        } catch (\Exception $e) {
            $result['panthNewsletterSubscribed'] = false;
        }

        if (!empty($result['paymentMethods'])) {
            return $result;
        }

        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
            $methods = $this->paymentMethodList->getActiveList($storeId);
            $paymentMethods = [];

            foreach ($methods as $method) {
                $paymentMethods[] = [
                    'code' => $method->getCode(),
                    'title' => $method->getTitle(),
                ];
            }

            $result['paymentMethods'] = $paymentMethods;
        } catch (\Exception $e) {
            $this->logger->error('Panth CheckoutExtended: Failed to load payment methods: ' . $e->getMessage());
        }

        return $result;
    }
}
