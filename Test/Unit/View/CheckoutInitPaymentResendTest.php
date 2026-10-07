<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\View;

use PHPUnit\Framework\TestCase;

class CheckoutInitPaymentResendTest extends TestCase
{
    private const TEMPLATE = 'view/frontend/templates/checkout_init.phtml';

    private string $template = '';

    protected function setUp(): void
    {
        $path = dirname(__DIR__, 3) . '/' . self::TEMPLATE;
        $this->assertTrue(is_file($path), self::TEMPLATE . ' is missing');
        $this->template = (string) file_get_contents($path);
    }

    private function saveDoneCallback(): string
    {
        $start = strpos($this->template, 'setShippingInformationAction().done(function () {');
        $this->assertNotFalse($start, 'Shipping save callback not found');
        $end = strpos($this->template, '}).fail(function () {', (int) $start);
        $this->assertNotFalse($end, 'Shipping save fail handler not found');

        return substr($this->template, (int) $start, (int) $end - (int) $start);
    }

    public function testSelectedPaymentIsSentAgainAfterShippingIsSaved(): void
    {
        $callback = $this->saveDoneCallback();
        $this->assertStringContainsString('var selectedPayment = quote.paymentMethod();', $callback);
        $this->assertStringContainsString('if (selectedPayment && selectedPayment.method) {', $callback);
        $this->assertStringContainsString('selectPaymentMethodAction(selectedPayment);', $callback);
    }

    public function testPaymentActionIsLoadedWhenNeededSoItsMixinsApply(): void
    {
        $callback = $this->saveDoneCallback();
        $this->assertStringContainsString(
            "require(['Magento_Checkout/js/action/select-payment-method'], function (selectPaymentMethodAction) {",
            $callback
        );

        $head = substr($this->template, 0, (int) strpos($this->template, 'var saving = false'));
        $this->assertStringNotContainsString('select-payment-method', $head);
    }

    public function testPaymentIsSentOnlyAfterTheSavedFlagIsSet(): void
    {
        $callback = $this->saveDoneCallback();
        $flag = strpos($callback, 'shippingInfoSaved = true;');
        $resend = strpos($callback, 'selectPaymentMethodAction(selectedPayment);');
        $this->assertNotFalse($flag);
        $this->assertNotFalse($resend);
        $this->assertGreaterThan($flag, $resend);
    }

    public function testFailedSaveDoesNotResendPayment(): void
    {
        $start = strpos($this->template, '}).fail(function () {');
        $end = strpos($this->template, '}).always(function () {', (int) $start);
        $fail = substr($this->template, (int) $start, (int) $end - (int) $start);
        $this->assertStringNotContainsString('selectPaymentMethodAction', $fail);
        $this->assertStringContainsString('lastSavedFingerprint = null;', $fail);
    }
}
