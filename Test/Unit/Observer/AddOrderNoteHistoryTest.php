<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\History;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Observer\AddOrderNoteHistory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AddOrderNoteHistoryTest extends TestCase
{
    private const STORE_ID = 4;

    private Data $helper;

    private AddOrderNoteHistory $observer;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->observer = new AddOrderNoteHistory($this->helper);
    }

    public function testAddsHiddenStatusHistoryCommentWhenOrderCarriesNote(): void
    {
        $this->helper->method('isEnabled')->with(self::STORE_ID)->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->with(self::STORE_ID)->willReturn(true);

        $order = $this->mockOrder('Leave at the side door');
        $order->expects($this->once())
            ->method('addCommentToStatusHistory')
            ->with('Customer note: Leave at the side door', false, false);

        $this->observer->execute($this->observerFor($order));
    }

    #[DataProvider('blankNoteProvider')]
    public function testSkipsWhenNoteIsBlank(?string $note): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->willReturn(true);

        $order = $this->mockOrder($note);
        $order->expects($this->never())->method('addCommentToStatusHistory');

        $this->observer->execute($this->observerFor($order));
    }

    public static function blankNoteProvider(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'whitespace' => ["  \n"],
        ];
    }

    public function testSkipsWhenThePaymentAlreadyRecordedTheNoteInHistory(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->willReturn(true);

        $existing = $this->createMock(History::class);
        $existing->method('getComment')->willReturn('Leave at the side door');

        $order = $this->mockOrder('Leave at the side door', [$existing]);
        $order->expects($this->never())->method('addCommentToStatusHistory');

        $this->observer->execute($this->observerFor($order));
    }

    public function testAddsCommentWhenHistoryHoldsOtherComments(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->willReturn(true);

        $other = $this->createMock(History::class);
        $other->method('getComment')->willReturn('Authorized amount of $17.00');

        $order = $this->mockOrder('Leave at the side door', [$other]);
        $order->expects($this->once())
            ->method('addCommentToStatusHistory')
            ->with('Customer note: Leave at the side door', false, false);

        $this->observer->execute($this->observerFor($order));
    }

    public function testSkipsWhenFeatureDisabled(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('isOrderNoteEnabled')->willReturn(false);

        $order = $this->mockOrder('note');
        $order->expects($this->never())->method('addCommentToStatusHistory');

        $this->observer->execute($this->observerFor($order));
    }

    public function testIgnoresEventsWithoutAnOrder(): void
    {
        $this->helper->expects($this->never())->method('isEnabled');

        $this->observer->execute($this->observerFor(null));
    }

    private function mockOrder(?string $note, ?array $histories = null)
    {
        $order = $this->createMock(Order::class);
        $order->method('getStoreId')->willReturn(self::STORE_ID);
        $order->method('getCustomerNote')->willReturn($note);
        $order->method('getStatusHistories')->willReturn($histories);

        return $order;
    }

    private function observerFor($order): Observer
    {
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('order')->willReturn($order);

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        return $observer;
    }
}
