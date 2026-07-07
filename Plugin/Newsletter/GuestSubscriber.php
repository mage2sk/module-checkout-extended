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

class GuestSubscriber
{
    public function __construct(
        private readonly SubscriptionManagerInterface $subscriptionManager,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        private readonly Data $helper,
        private readonly ?GuestCartRepositoryInterface $guestCartRepository = null
    ) {
    }

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
            $this->logger->error(
                'Panth CheckoutExtended: Failed to subscribe guest to newsletter.',
                ['email' => $email, 'exception' => $e->getMessage()]
            );
        }

        return $result;
    }

    private function resolveStoreId(string $cartId): int
    {
        if ($this->guestCartRepository !== null) {
            try {
                $quoteStoreId = (int) $this->guestCartRepository->get($cartId)->getStoreId();
                if ($quoteStoreId > 0) {
                    return $quoteStoreId;
                }
            } catch (\Exception $e) {
            }
        }

        return (int) $this->storeManager->getStore()->getId();
    }
}
