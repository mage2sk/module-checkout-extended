<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Model\OrderNote;

use Panth\CheckoutExtended\Helper\Data;

class Sanitizer
{
    private Data $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function sanitize(?string $note, $storeId = null): string
    {
        if ($note === null) {
            return '';
        }

        $clean = strip_tags($note);
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean) ?? $clean;
        $clean = trim($clean);

        if ($clean === '') {
            return '';
        }

        $max = $this->helper->getOrderNoteMaxLength($storeId);

        if (mb_strlen($clean) > $max) {
            $clean = rtrim(mb_substr($clean, 0, $max));
        }

        return $clean;
    }

    public function extract($paymentMethod): ?string
    {
        if (!is_object($paymentMethod) || !method_exists($paymentMethod, 'getExtensionAttributes')) {
            return null;
        }

        $extensionAttributes = $paymentMethod->getExtensionAttributes();

        if ($extensionAttributes === null || !method_exists($extensionAttributes, 'getPanthOrderNote')) {
            return null;
        }

        $value = $extensionAttributes->getPanthOrderNote();

        return $value === null ? null : (string) $value;
    }
}
