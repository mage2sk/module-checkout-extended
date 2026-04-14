<?php

declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin\Newsletter;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Subscribes logged-in customers to the newsletter after order placement
 * when the checkout newsletter checkbox is checked.
 */
class CustomerSubscriber
{
    public function __construct(
        private readonly SubscriptionManagerInterface $subscriptionManager,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * After placing a customer order, subscribe the customer if opted in.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagementInterface $subject,
        int|string $result,
        int $cartId,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): int|string {
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
            $email = $quote->getCustomerEmail();
            $storeId = (int) $this->storeManager->getStore()->getId();
            $this->subscriptionManager->subscribe($email, $storeId);
        } catch (\Exception $e) {
            // Newsletter subscription failure should not break the order flow.
            $this->logger->error(
                'Panth CheckoutExtended: Failed to subscribe customer to newsletter.',
                ['cartId' => $cartId, 'exception' => $e->getMessage()]
            );
        }

        return $result;
    }
}
