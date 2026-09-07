<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Plugin\OrderNote;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Model\OrderNote\Sanitizer;
use Psr\Log\LoggerInterface;

class SaveOrderNote
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly Sanitizer $sanitizer,
        private readonly Data $helper,
        private readonly LoggerInterface $logger
    ) {
    }

    public function beforeSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagementInterface $subject,
        $cartId,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): array {
        $this->store($cartId, $paymentMethod);

        return [$cartId, $paymentMethod, $billingAddress];
    }

    public function beforeSavePaymentInformation(
        PaymentInformationManagementInterface $subject,
        $cartId,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): array {
        $this->store($cartId, $paymentMethod);

        return [$cartId, $paymentMethod, $billingAddress];
    }

    private function store($cartId, PaymentInterface $paymentMethod): void
    {
        if (!$this->helper->isEnabled() || !$this->helper->isOrderNoteEnabled()) {
            return;
        }

        $raw = $this->sanitizer->extract($paymentMethod);

        if ($raw === null) {
            return;
        }

        try {
            $quote = $this->cartRepository->get((int) $cartId);
            $note = $this->sanitizer->sanitize($raw, (int) $quote->getStoreId());
            $notify = $note !== '';

            if ((string) $quote->getCustomerNote() === $note && (bool) $quote->getCustomerNoteNotify() === $notify) {
                return;
            }

            $quote->setCustomerNote($note);
            $quote->setCustomerNoteNotify($notify);
            $this->cartRepository->save($quote);
        } catch (\Exception $e) {
            $this->logger->error(
                'Panth CheckoutExtended: Failed to store the order note on the quote.',
                ['cartId' => $cartId, 'exception' => $e->getMessage()]
            );
        }
    }
}
