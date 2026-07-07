<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin\Newsletter;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\CheckoutExtended\Helper\Data;
use Psr\Log\LoggerInterface;

class CustomerSubscriber
{
    public function __construct(
        private readonly SubscriptionManagerInterface $subscriptionManager,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        private readonly Data $helper
    ) {
    }

    public function afterSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagementInterface $subject,
        int|string $result,
        int $cartId,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): int|string {
        if (!$this->helper->isNewsletterEnabled()) {
            return $result;
        }

        $extensionAttributes = $paymentMethod->getExtensionAttributes();

        if ($extensionAttributes === null) {
            return $result;
        }

        $shouldSubscribe = (bool) $extensionAttributes->getPanthSubscribeNewsletter();

        if (!$shouldSubscribe) {
            return $result;
        }

        try {
            $quote = $this->cartRepository->get($cartId);

            $quoteStoreId = method_exists($quote, 'getStoreId') ? (int) $quote->getStoreId() : 0;
            $storeId = $quoteStoreId > 0
                ? $quoteStoreId
                : (int) $this->storeManager->getStore()->getId();
            $customerId = (int) $quote->getCustomerId();

            if ($customerId > 0) {
                $this->subscriptionManager->subscribeCustomer($customerId, $storeId);
            } else {
                $this->subscriptionManager->subscribe((string) $quote->getCustomerEmail(), $storeId);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Panth CheckoutExtended: Failed to subscribe customer to newsletter.',
                ['cartId' => $cartId, 'exception' => $e->getMessage()]
            );
        }

        return $result;
    }
}
