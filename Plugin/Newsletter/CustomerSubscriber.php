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
        private readonly LoggerInterface $logger,
        private readonly Data $helper
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

            // Record the subscription against the QUOTE's store view rather than the
            // resolved current store. This is correct for multi-store fronts and for
            // REST/GraphQL order placement where the "current" store may differ from
            // the cart's. Fall back to the current store only if the quote has none.
            $quoteStoreId = method_exists($quote, 'getStoreId') ? (int) $quote->getStoreId() : 0;
            $storeId = $quoteStoreId > 0
                ? $quoteStoreId
                : (int) $this->storeManager->getStore()->getId();
            $customerId = (int) $quote->getCustomerId();

            if ($customerId > 0) {
                // Subscribe by customer id so the newsletter_subscriber row is linked
                // to the account and shows up under My Account → Newsletter Subscriptions.
                $this->subscriptionManager->subscribeCustomer($customerId, $storeId);
            } else {
                // Fall back to email-based subscription if no customer id is available.
                $this->subscriptionManager->subscribe((string) $quote->getCustomerEmail(), $storeId);
            }
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
