<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\PaymentMethod;
use Codeception\Test\Unit;

final class PaymentMethodEntityTest extends Unit
{
    public string $creditCard = 'Credit Card';
    
    public string $bankTransfer = 'Bank Transfer';
    
    public function testConstructorWithDefaults(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $this->assertSame('', $paymentMethod->getId());
        $this->assertSame('', $paymentMethod->getName());
        $this->assertTrue($paymentMethod->getActive());
    }

    public function testConstructorWithAllParameters(): void
    {
        $paymentMethod = new PaymentMethod(1, $this->creditCard, false);
        
        $this->assertSame('1', $paymentMethod->getId());
        $this->assertSame($this->creditCard, $paymentMethod->getName());
        $this->assertFalse($paymentMethod->getActive());
    }

    public function testIdSetterAndGetter(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setId(42);
        
        $this->assertSame('42', $paymentMethod->getId());
    }

    public function testNameSetterAndGetter(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setName($this->bankTransfer);
        
        $this->assertSame($this->bankTransfer, $paymentMethod->getName());
    }

    public function testActiveSetterAndGetter(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setActive(false);
        
        $this->assertFalse($paymentMethod->getActive());
        
        $paymentMethod->setActive(true);
        $this->assertTrue($paymentMethod->getActive());
    }

    public function testCommonPaymentMethods(): void
    {
        $creditCard = new PaymentMethod(1, $this->creditCard, true);
        $this->assertSame($this->creditCard, $creditCard->getName());
        $this->assertTrue($creditCard->getActive());

        $bankTransfer = new PaymentMethod(2, $this->bankTransfer, true);
        $this->assertSame($this->bankTransfer, $bankTransfer->getName());
        $this->assertTrue($bankTransfer->getActive());

        $paypal = new PaymentMethod(3, 'PayPal', true);
        $this->assertSame('PayPal', $paypal->getName());
        $this->assertTrue($paypal->getActive());

        $cash = new PaymentMethod(4, 'Cash', false);
        $this->assertSame('Cash', $cash->getName());
        $this->assertFalse($cash->getActive());
    }

    public function testLongPaymentMethodNames(): void
    {
        $longName = 'Very Long Payment Method Name That Could Potentially Exceed Normal Limits';
        $paymentMethod = new PaymentMethod(1, $longName, true);
        
        $this->assertSame($longName, $paymentMethod->getName());
    }

    public function testSpecialCharactersInName(): void
    {
        $paymentMethod = new PaymentMethod(1, 'Credit/Debit Cards & Online Payments', true);
        
        $this->assertSame('Credit/Debit Cards & Online Payments', $paymentMethod->getName());
    }

    public function testUnicodeInName(): void
    {
        $paymentMethod = new PaymentMethod(1, 'Crédit Card 信用卡', true);
        
        $this->assertSame('Crédit Card 信用卡', $paymentMethod->getName());
    }

    public function testZeroAndLargeIds(): void
    {
        $zeroId = new PaymentMethod(0, 'Zero ID', true);
        $this->assertSame('0', $zeroId->getId());

        $largeId = new PaymentMethod(999999, 'Large ID', true);
        $this->assertSame('999999', $largeId->getId());
    }

    public function testChainedSetterCalls(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setId(100);
        $paymentMethod->setName('Chained Payment');
        $paymentMethod->setActive(false);
        
        $this->assertSame('100', $paymentMethod->getId());
        $this->assertSame('Chained Payment', $paymentMethod->getName());
        $this->assertFalse($paymentMethod->getActive());
    }

    public function testIdStringConversion(): void
    {
        $paymentMethod = new PaymentMethod(123, 'Test', true);
        
        // Verify ID getter returns string even though setter accepts int
        $this->assertIsString($paymentMethod->getId());
        $this->assertSame('123', $paymentMethod->getId());
    }

    public function testActiveStatusToggling(): void
    {
        $paymentMethod = new PaymentMethod(1, 'Test Method', true);
        
        // Test toggling active status
        $this->assertTrue($paymentMethod->getActive());
        
        $paymentMethod->setActive(false);
        $this->assertFalse($paymentMethod->getActive());
        
        $paymentMethod->setActive(true);
        $this->assertTrue($paymentMethod->getActive());
    }

    public function testCompletePaymentMethodSetup(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setId(999);
        $paymentMethod->setName('Complete Setup Method');
        $paymentMethod->setActive(true);
        
        $this->assertSame('999', $paymentMethod->getId());
        $this->assertSame('Complete Setup Method', $paymentMethod->getName());
        $this->assertTrue($paymentMethod->getActive());
    }

    public function testNullNameHandling(): void
    {
        $paymentMethod = new PaymentMethod(1, null, true);
        
        $this->assertNull($paymentMethod->getName());
    }

    public function testEmptyNameHandling(): void
    {
        $paymentMethod = new PaymentMethod(1, '', true);
        
        $this->assertSame('', $paymentMethod->getName());
    }

    public function testActivePropertyTyping(): void
    {
        $paymentMethod = new PaymentMethod(1, 'Test', true);
        
        // Verify active property returns bool
        $this->assertIsBool($paymentMethod->getActive());
        $this->assertTrue($paymentMethod->getActive());
    }
}
