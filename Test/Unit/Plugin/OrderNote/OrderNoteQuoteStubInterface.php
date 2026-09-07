<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Plugin\OrderNote;

use Magento\Quote\Api\Data\CartInterface;

interface OrderNoteQuoteStubInterface extends CartInterface
{
    public function getCustomerNote();

    public function getCustomerNoteNotify();

    public function setCustomerNote($note);

    public function setCustomerNoteNotify($flag);
}
