<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Invoice\Entity\AllowanceCharge;
use App\Invoice\Entity\TaxRate;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

class AllowanceChargeEntityTest extends Unit
{
    private MockObject $taxRate;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->taxRate = $this->createMock(TaxRate::class);
    }

    public function testConstructorWithDefaults(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $this->assertSame('', $allowanceCharge->getId());
        $this->assertFalse($allowanceCharge->getIdentifier());
        $this->assertSame(0, $allowanceCharge->getLevel());
        $this->assertSame('', $allowanceCharge->getReasonCode());
        $this->assertSame('', $allowanceCharge->getReason());
        $this->assertSame(0, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(0, $allowanceCharge->getAmount());
        $this->assertSame(0, $allowanceCharge->getBaseAmount());
        $this->assertNull($allowanceCharge->getTaxRate());
        $this->assertSame('', $allowanceCharge->getTaxRateId());
    }

    public function testConstructorWithAllParameters(): void
    {
        $allowanceCharge = new AllowanceCharge(
            1,                  // id
            true,               // identifier
            1,                  // level
            'REA',              // reason_code
            'Test reason',      // reason
            100,                // multiplier_factor_numeric
            1000,               // amount
            500,                // base_amount
            2                   // tax_rate_id
        );
        
        $this->assertSame('1', $allowanceCharge->getId());
        $this->assertTrue($allowanceCharge->getIdentifier());
        $this->assertSame(1, $allowanceCharge->getLevel());
        $this->assertSame('REA', $allowanceCharge->getReasonCode());
        $this->assertSame('Test reason', $allowanceCharge->getReason());
        $this->assertSame(100, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(1000, $allowanceCharge->getAmount());
        $this->assertSame(500, $allowanceCharge->getBaseAmount());
        $this->assertSame('2', $allowanceCharge->getTaxRateId());
    }

    public function testIdSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setId(123);
        $this->assertSame('123', $allowanceCharge->getId());
        
        $allowanceCharge->setId(456);
        $this->assertSame('456', $allowanceCharge->getId());
    }

    public function testIdentifierSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setIdentifier(true);
        $this->assertTrue($allowanceCharge->getIdentifier());
        
        $allowanceCharge->setIdentifier(false);
        $this->assertFalse($allowanceCharge->getIdentifier());
    }

    public function testLevelSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setLevel(0); // Overall
        $this->assertSame(0, $allowanceCharge->getLevel());
        
        $allowanceCharge->setLevel(1); // InvoiceLine
        $this->assertSame(1, $allowanceCharge->getLevel());
    }

    public function testReasonCodeSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setReasonCode('REA');
        $this->assertSame('REA', $allowanceCharge->getReasonCode());
        
        $allowanceCharge->setReasonCode('DIS');
        $this->assertSame('DIS', $allowanceCharge->getReasonCode());
    }

    public function testReasonSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setReason('Volume discount');
        $this->assertSame('Volume discount', $allowanceCharge->getReason());
        
        $allowanceCharge->setReason('Early payment discount');
        $this->assertSame('Early payment discount', $allowanceCharge->getReason());
    }

    public function testMultiplierFactorNumericSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setMultiplierFactorNumeric(150);
        $this->assertSame(150, $allowanceCharge->getMultiplierFactorNumeric());
        
        $allowanceCharge->setMultiplierFactorNumeric(200);
        $this->assertSame(200, $allowanceCharge->getMultiplierFactorNumeric());
    }

    public function testAmountSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setAmount(1500);
        $this->assertSame(1500, $allowanceCharge->getAmount());
        
        $allowanceCharge->setAmount(2000);
        $this->assertSame(2000, $allowanceCharge->getAmount());
    }

    public function testBaseAmountSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setBaseAmount(1000);
        $this->assertSame(1000, $allowanceCharge->getBaseAmount());
        
        $allowanceCharge->setBaseAmount(1500);
        $this->assertSame(1500, $allowanceCharge->getBaseAmount());
    }

    public function testTaxRateSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setTaxrate($this->taxRate);
        $this->assertSame($this->taxRate, $allowanceCharge->getTaxRate());
        
        $allowanceCharge->setTaxrate(null);
        $this->assertNull($allowanceCharge->getTaxRate());
    }

    public function testTaxRateIdSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setTaxRateId(5);
        $this->assertSame('5', $allowanceCharge->getTaxRateId());
        
        $allowanceCharge->setTaxRateId(10);
        $this->assertSame('10', $allowanceCharge->getTaxRateId());
    }

    public function testReasonCodeFormats(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $reasonCodes = ['REA', 'DIS', 'CHG', 'SUP', 'TAX'];
        
        foreach ($reasonCodes as $code) {
            $allowanceCharge->setReasonCode($code);
            $this->assertSame($code, $allowanceCharge->getReasonCode());
        }
    }

    public function testLongReasonText(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $longReason = str_repeat('This is a very long reason text for the allowance charge. ', 20);
        
        $allowanceCharge->setReason($longReason);
        $this->assertSame($longReason, $allowanceCharge->getReason());
    }

    public function testSpecialCharactersInReason(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $specialReason = 'Discount: 10% off for €1000+ orders (valid until 31/12/2024)!';
        
        $allowanceCharge->setReason($specialReason);
        $this->assertSame($specialReason, $allowanceCharge->getReason());
    }

    public function testUnicodeCharactersInReason(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $unicodeReason = 'Rabatt für große Bestellungen 世界中の顧客 €100 減額';
        
        $allowanceCharge->setReason($unicodeReason);
        $this->assertSame($unicodeReason, $allowanceCharge->getReason());
    }

    public function testNegativeAmounts(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setAmount(-500);
        $this->assertSame(-500, $allowanceCharge->getAmount());
        
        $allowanceCharge->setBaseAmount(-300);
        $this->assertSame(-300, $allowanceCharge->getBaseAmount());
        
        $allowanceCharge->setMultiplierFactorNumeric(-150);
        $this->assertSame(-150, $allowanceCharge->getMultiplierFactorNumeric());
    }

    public function testLargeAmounts(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setAmount(999999999);
        $this->assertSame(999999999, $allowanceCharge->getAmount());
        
        $allowanceCharge->setBaseAmount(888888888);
        $this->assertSame(888888888, $allowanceCharge->getBaseAmount());
        
        $allowanceCharge->setMultiplierFactorNumeric(777777777);
        $this->assertSame(777777777, $allowanceCharge->getMultiplierFactorNumeric());
    }

    public function testZeroAmounts(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setAmount(0);
        $this->assertSame(0, $allowanceCharge->getAmount());
        
        $allowanceCharge->setBaseAmount(0);
        $this->assertSame(0, $allowanceCharge->getBaseAmount());
        
        $allowanceCharge->setMultiplierFactorNumeric(0);
        $this->assertSame(0, $allowanceCharge->getMultiplierFactorNumeric());
    }

    public function testCompleteAllowanceChargeSetup(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Setup a complete discount scenario
        $allowanceCharge->setId(1);
        $allowanceCharge->setIdentifier(true);
        $allowanceCharge->setLevel(0); // Overall
        $allowanceCharge->setReasonCode('DIS');
        $allowanceCharge->setReason('Volume discount for bulk purchase');
        $allowanceCharge->setMultiplierFactorNumeric(10); // 10%
        $allowanceCharge->setAmount(100); // €100 discount
        $allowanceCharge->setBaseAmount(1000); // Based on €1000
        $allowanceCharge->setTaxRateId(1);
        $allowanceCharge->setTaxrate($this->taxRate);
        
        $this->assertSame('1', $allowanceCharge->getId());
        $this->assertTrue($allowanceCharge->getIdentifier());
        $this->assertSame(0, $allowanceCharge->getLevel());
        $this->assertSame('DIS', $allowanceCharge->getReasonCode());
        $this->assertSame('Volume discount for bulk purchase', $allowanceCharge->getReason());
        $this->assertSame(10, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(100, $allowanceCharge->getAmount());
        $this->assertSame(1000, $allowanceCharge->getBaseAmount());
        $this->assertSame('1', $allowanceCharge->getTaxRateId());
        $this->assertSame($this->taxRate, $allowanceCharge->getTaxRate());
    }

    public function testEmptyStringValues(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setReasonCode('');
        $allowanceCharge->setReason('');
        
        $this->assertSame('', $allowanceCharge->getReasonCode());
        $this->assertSame('', $allowanceCharge->getReason());
    }

    public function testGetterMethodsConsistency(): void
    {
        $allowanceCharge = new AllowanceCharge(1, true, 1, 'CHG', 'Test charge', 150, 750, 500, 3);
        
        // Multiple calls should return same values
        $this->assertSame($allowanceCharge->getId(), $allowanceCharge->getId());
        $this->assertSame($allowanceCharge->getIdentifier(), $allowanceCharge->getIdentifier());
        $this->assertSame($allowanceCharge->getLevel(), $allowanceCharge->getLevel());
        $this->assertSame($allowanceCharge->getReasonCode(), $allowanceCharge->getReasonCode());
        $this->assertSame($allowanceCharge->getReason(), $allowanceCharge->getReason());
        $this->assertSame($allowanceCharge->getMultiplierFactorNumeric(), $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame($allowanceCharge->getAmount(), $allowanceCharge->getAmount());
        $this->assertSame($allowanceCharge->getBaseAmount(), $allowanceCharge->getBaseAmount());
        $this->assertSame($allowanceCharge->getTaxRateId(), $allowanceCharge->getTaxRateId());
    }

    public function testIdentifierToggling(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Test toggling identifier
        $this->assertFalse($allowanceCharge->getIdentifier());
        $allowanceCharge->setIdentifier(!$allowanceCharge->getIdentifier());
        $this->assertTrue($allowanceCharge->getIdentifier());
        $allowanceCharge->setIdentifier(!$allowanceCharge->getIdentifier());
        $this->assertFalse($allowanceCharge->getIdentifier());
    }

    public function testCalculationScenario(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Test calculation: multiplier_factor_numeric x base_amount = amount
        $baseAmount = 1000;
        $multiplier = 15; // 15%
        $expectedAmount = 150; // 15% of 1000
        
        $allowanceCharge->setBaseAmount($baseAmount);
        $allowanceCharge->setMultiplierFactorNumeric($multiplier);
        $allowanceCharge->setAmount($expectedAmount);
        
        $this->assertSame($baseAmount, $allowanceCharge->getBaseAmount());
        $this->assertSame($multiplier, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame($expectedAmount, $allowanceCharge->getAmount());
    }

    public function testFixedAmountScenario(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Test fixed amount scenario (multiplier = 0 or 1, no calculation)
        $fixedAmount = 50;
        
        $allowanceCharge->setAmount($fixedAmount);
        $allowanceCharge->setMultiplierFactorNumeric(1);
        $allowanceCharge->setBaseAmount(0); // Not used for fixed amounts
        
        $this->assertSame($fixedAmount, $allowanceCharge->getAmount());
        $this->assertSame(1, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(0, $allowanceCharge->getBaseAmount());
    }

    public function testIdStringConversion(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Test that ID is always returned as string
        $allowanceCharge->setId(0);
        $this->assertSame('0', $allowanceCharge->getId());
        $this->assertIsString($allowanceCharge->getId());
        
        $allowanceCharge->setId(999);
        $this->assertSame('999', $allowanceCharge->getId());
        $this->assertIsString($allowanceCharge->getId());
    }

    public function testTaxRateIdStringConversion(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Test that TaxRateId is always returned as string
        $allowanceCharge->setTaxRateId(0);
        $this->assertSame('0', $allowanceCharge->getTaxRateId());
        $this->assertIsString($allowanceCharge->getTaxRateId());
        
        $allowanceCharge->setTaxRateId(123);
        $this->assertSame('123', $allowanceCharge->getTaxRateId());
        $this->assertIsString($allowanceCharge->getTaxRateId());
    }
}
