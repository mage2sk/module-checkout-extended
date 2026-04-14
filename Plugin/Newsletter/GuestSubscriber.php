<?php

declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin\Newsletter;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Subscribes guest customers to the newsletter after order placement
 * when the checkout newsletter checkbox is checked.
 */
class GuestSubscriber
{
    public function __construct(
        private readonly SubscriptionManagerInterface $subscriptionManager,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * After placing a guest order, subscribe the email if opted in.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $subject,
        int|string $result,
        string $cartId,
        string $email,
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
            $storeId = (int) $this->storeManager->getStore()->getId();
            $this->subscriptionManager->subscribe($email, $storeId);
        } catch (\Exception $e) {
            // Newsletter subscription failure should not break the order flow.
            $this->logger->error(
                'Panth CheckoutExtended: Failed to subscribe guest to newsletter.',
                ['email' => $email, 'exception' => $e->getMessage()]
            );
        }

        return $result;
    }
}
