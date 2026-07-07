<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Layout\ProcessorInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use Panth\CheckoutExtended\Helper\Data;
use Panth\CheckoutExtended\Observer\AddBodyClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

interface FullActionRequestStubInterface extends RequestInterface
{
    public function getFullActionName();
}

class AddBodyClassTest extends TestCase
{
    private Data $helper;

    private FullActionRequestStubInterface $request;

    private PageConfig $pageConfig;

    private AddBodyClass $observer;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->request = $this->createMock(FullActionRequestStubInterface::class);
        $this->pageConfig = $this->createMock(PageConfig::class);

        $this->observer = new AddBodyClass(
            $this->helper,
            $this->request,
            $this->pageConfig
        );
    }

    public function testDoesNothingWhenModuleIsDisabled(): void
    {
        $this->helper->method('isEnabled')->willReturn(false);

        $this->request->expects($this->never())->method('getFullActionName');
        $this->helper->expects($this->never())->method('getCheckoutBodyClass');
        $this->pageConfig->expects($this->never())->method('addBodyClass');

        $event = $this->createMock(Observer::class);
        $event->expects($this->never())->method('getData');

        $this->observer->execute($event);
    }

    public function testReturnsEarlyWhenNotOnCheckoutPage(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->request->method('getFullActionName')->willReturn('cms_index_index');

        $this->helper->expects($this->never())->method('getCheckoutBodyClass');
        $this->pageConfig->expects($this->never())->method('addBodyClass');

        $event = $this->createMock(Observer::class);
        $event->expects($this->never())->method('getData');

        $this->observer->execute($event);
    }

    public function testAddsLayoutHandleAndEachBodyClassTokenOnCheckoutPage(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->request->method('getFullActionName')->willReturn('checkout_index_index');
        $this->helper->method('getCheckoutBodyClass')
            ->willReturn('panth-checkout-extended panth-checkout-3col panth-sidebar-right');

        $update = $this->createMock(ProcessorInterface::class);
        $update->expects($this->once())
            ->method('addHandle')
            ->with('panth_checkout_extended_active')
            ->willReturnSelf();

        $layout = $this->createMock(LayoutInterface::class);
        $layout->expects($this->once())->method('getUpdate')->willReturn($update);

        $event = $this->createMock(Observer::class);
        $event->method('getData')->with('layout')->willReturn($layout);

        $addedClasses = [];
        $this->pageConfig->expects($this->exactly(3))
            ->method('addBodyClass')
            ->willReturnCallback(
                function (string $className) use (&$addedClasses): PageConfig {
                    $addedClasses[] = $className;

                    return $this->pageConfig;
                }
            );

        $this->observer->execute($event);

        $this->assertSame(
            ['panth-checkout-extended', 'panth-checkout-3col', 'panth-sidebar-right'],
            $addedClasses
        );
    }

    public function testStillAddsBodyClassesWhenObserverCarriesNoLayout(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->request->method('getFullActionName')->willReturn('checkout_index_index');
        $this->helper->method('getCheckoutBodyClass')->willReturn('panth-checkout-extended');

        $event = $this->createMock(Observer::class);
        $event->method('getData')->with('layout')->willReturn(null);

        $this->pageConfig->expects($this->once())
            ->method('addBodyClass')
            ->with('panth-checkout-extended')
            ->willReturnSelf();

        $this->observer->execute($event);
    }

    public function testAddsHandleButNoBodyClassesWhenHelperReturnsBlankString(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->request->method('getFullActionName')->willReturn('checkout_index_index');

        $this->helper->method('getCheckoutBodyClass')->willReturn('   ');

        $update = $this->createMock(ProcessorInterface::class);
        $update->expects($this->once())
            ->method('addHandle')
            ->with('panth_checkout_extended_active')
            ->willReturnSelf();

        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('getUpdate')->willReturn($update);

        $event = $this->createMock(Observer::class);
        $event->method('getData')->with('layout')->willReturn($layout);

        $this->pageConfig->expects($this->never())->method('addBodyClass');

        $this->observer->execute($event);
    }
}
