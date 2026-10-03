<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\History;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Observer\AddOrderNoteHistory;
use PHPUnit\Framework\TestCase;

class AddOrderNoteHistoryEdgeCasesTest extends TestCase
{
    private $helper;

    protected function setUp(): void
    {
        $this->helper = $this->createStub(Data::class);
    }

    private function observerFor($order): Observer
    {
        return new Observer(['event' => new Event(['order' => $order])]);
    }

    private function order(?string $note, $histories)
    {
        $order = $this->createMock(Order::class);
        $order->method('getStoreId')->willReturn('6');
        $order->method('getCustomerNote')->willReturn($note);
        $order->method('getStatusHistories')->willReturn($histories);

        return $order;
    }

    public function testSkipsWithoutCheckingOrderNoteFlagWhenModuleDisabled(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->helper->expects($this->once())->method('isEnabled')->with(6)->willReturn(false);
        $this->helper->expects($this->never())->method('isOrderNoteEnabled');

        $order = $this->order('note', null);
        $order->expects($this->never())->method('addCommentToStatusHistory');

        (new AddOrderNoteHistory($this->helper))->execute($this->observerFor($order));
    }

    public function testIgnoresNonOrderPayload(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->helper->expects($this->never())->method('isEnabled');

        (new AddOrderNoteHistory($this->helper))->execute($this->observerFor(new DataObject(['customer_note' => 'x'])));
    }

    public function testTrimsNoteBeforeAddingComment(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->willReturn(true);

        $order = $this->order("  Gate code 1234 \n", []);
        $order->expects($this->once())
            ->method('addCommentToStatusHistory')
            ->with('Customer note: Gate code 1234', false, false);

        (new AddOrderNoteHistory($this->helper))->execute($this->observerFor($order));
    }

    public function testSkipsNonObjectAndMethodlessHistoryEntries(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->willReturn(true);

        $order = $this->order('Leave by the door', ['Leave by the door', new \stdClass(), null]);
        $order->expects($this->once())
            ->method('addCommentToStatusHistory')
            ->with('Customer note: Leave by the door', false, false);

        (new AddOrderNoteHistory($this->helper))->execute($this->observerFor($order));
    }

    public function testMatchesHistoryCommentAfterTrimming(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->willReturn(true);

        $history = $this->createStub(History::class);
        $history->method('getComment')->willReturn("  Leave by the door\n");

        $order = $this->order('Leave by the door', [new \stdClass(), $history]);
        $order->expects($this->never())->method('addCommentToStatusHistory');

        (new AddOrderNoteHistory($this->helper))->execute($this->observerFor($order));
    }
}
