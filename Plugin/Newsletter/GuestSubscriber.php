<?php

declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin\Newsletter;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\CheckoutExtended\Helper\Data;
use Psr\Log\LoggerInterface;

/**
 * Subscribes guest customers to the newsletter after order placement
 * when the checkout newsletter checkbox is checked.
 */
class GuestSubscriber
{
    /**
     * @param SubscriptionManagerInterface $subscriptionManager
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param GuestCartRepositoryInterface|null $guestCartRepository Optional (additive, DI-injected)
     *        so the subscription is recorded against the QUOTE's store view rather than
     *        the resolved current store (important for multi-store and REST/GraphQL API calls).
     */
    public function __construct(
        private readonly SubscriptionManagerInterface $subscriptionManager,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        private readonly Data $helper,
        private readonly ?GuestCartRepositoryInterface $guestCartRepository = null
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
            $storeId = $this->resolveStoreId($cartId);
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

    /**
     * Resolve the store id the subscription should be recorded against.
     *
     * Prefers the QUOTE's store id (correct for multi-store fronts and headless
     * API calls where the resolved "current" store may differ from the cart's).
     * Falls back to the current store when the quote/store id is unavailable.
     *
     * @param string $cartId Masked guest cart id
     */
    private function resolveStoreId(string $cartId): int
    {
        if ($this->guestCartRepository !== null) {
            try {
                $quoteStoreId = (int) $this->guestCartRepository->get($cartId)->getStoreId();
                if ($quoteStoreId > 0) {
                    return $quoteStoreId;
                }
            } catch (\Exception $e) {
                // Fall through to the current store below.
            }
        }

        return (int) $this->storeManager->getStore()->getId();
    }
}
