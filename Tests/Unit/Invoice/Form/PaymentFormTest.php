<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Payment\Payment;
use App\Invoice\Payment\PaymentForm;
use PHPUnit\Framework\TestCase;

class PaymentFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new PaymentForm();

        $this->assertSame('', $form->getFormName());
        $this->assertNull($form->getPaymentMethodId());
        $this->assertNull($form->getAmount());
        $this->assertSame('', $form->getNote());
        $this->assertNull($form->getInvId());
        $this->assertNull($form->getInv());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new PaymentForm())->getFormName());
    }

    public function testShowPopulatesFromMockedPayment(): void
    {
        /** @var Payment&\PHPUnit\Framework\MockObject\MockObject $payment */
        $payment = $this->createMock(Payment::class);
        $payment->method('reqPaymentMethodId')->willReturn(2);
        $payment->method('getPaymentDate')->willReturn('2026-01-10');
        $payment->method('getAmount')->willReturn(250.00);
        $payment->method('getNote')->willReturn('BACS transfer');
        $payment->method('reqInvId')->willReturn(15);
        $payment->method('getInv')->willReturn(null);

        $form = PaymentForm::show($payment);

        $this->assertSame(2, $form->getPaymentMethodId());
        $this->assertSame('2026-01-10', $form->getPaymentDate());
        $this->assertSame(250.00, $form->getAmount());
        $this->assertSame('BACS transfer', $form->getNote());
        $this->assertSame(15, $form->getInvId());
        $this->assertNull($form->getInv());
    }

    public function testShowWithZeroAmount(): void
    {
        /** @var Payment&\PHPUnit\Framework\MockObject\MockObject $payment */
        $payment = $this->createMock(Payment::class);
        $payment->method('reqPaymentMethodId')->willReturn(1);
        $payment->method('getPaymentDate')->willReturn('2026-02-01');
        $payment->method('getAmount')->willReturn(0.00);
        $payment->method('getNote')->willReturn('');
        $payment->method('reqInvId')->willReturn(1);
        $payment->method('getInv')->willReturn(null);

        $form = PaymentForm::show($payment);

        $this->assertSame(0.00, $form->getAmount());
    }

    public function testShowWithLargeAmount(): void
    {
        /** @var Payment&\PHPUnit\Framework\MockObject\MockObject $payment */
        $payment = $this->createMock(Payment::class);
        $payment->method('reqPaymentMethodId')->willReturn(3);
        $payment->method('getPaymentDate')->willReturn('2026-03-15');
        $payment->method('getAmount')->willReturn(99999.99);
        $payment->method('getNote')->willReturn('Annual payment');
        $payment->method('reqInvId')->willReturn(100);
        $payment->method('getInv')->willReturn(null);

        $form = PaymentForm::show($payment);

        $this->assertSame(99999.99, $form->getAmount());
        $this->assertSame('Annual payment', $form->getNote());
    }

    public function testShowReturnsNewInstance(): void
    {
        /** @var Payment&\PHPUnit\Framework\MockObject\MockObject $payment */
        $payment = $this->createMock(Payment::class);
        $payment->method('reqPaymentMethodId')->willReturn(1);
        $payment->method('getPaymentDate')->willReturn('2026-01-01');
        $payment->method('getAmount')->willReturn(100.00);
        $payment->method('getNote')->willReturn('');
        $payment->method('reqInvId')->willReturn(1);
        $payment->method('getInv')->willReturn(null);

        $this->assertNotSame(PaymentForm::show($payment), PaymentForm::show($payment));
    }
}
