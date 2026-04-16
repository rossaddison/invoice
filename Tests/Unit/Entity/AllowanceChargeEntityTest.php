<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
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
        
        $this->assertFalse($allowanceCharge->isPersisted());
        $this->assertFalse($allowanceCharge->getIdentifier());
        $this->assertSame(0, $allowanceCharge->getLevel());
        $this->assertSame('', $allowanceCharge->getReasonCode());
        $this->assertSame('', $allowanceCharge->getReason());
        $this->assertSame(0, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(0, $allowanceCharge->getAmount());
        $this->assertSame(0, $allowanceCharge->getBaseAmount());
        $this->assertNull($allowanceCharge->getTaxRate());
        $this->assertSame(0, $allowanceCharge->getTaxRateId());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('AllowanceCharge has no ID (not persisted yet)');
        $allowanceCharge->reqId();
    }

    public function testConstructorWithAllParameters(): void
    {
        $allowanceCharge = new AllowanceCharge(
            1,
            true,
            1,
            'REA',
            'Test reason',
            100,
            1000,
            500,
            2
        );
        
        $this->assertTrue($allowanceCharge->isPersisted());
        $this->assertSame(1, $allowanceCharge->reqId());
        $this->assertTrue($allowanceCharge->getIdentifier());
        $this->assertSame(1, $allowanceCharge->getLevel());
        $this->assertSame('REA', $allowanceCharge->getReasonCode());
        $this->assertSame('Test reason', $allowanceCharge->getReason());
        $this->assertSame(100, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(1000, $allowanceCharge->getAmount());
        $this->assertSame(500, $allowanceCharge->getBaseAmount());
        $this->assertSame(2, $allowanceCharge->getTaxRateId());
    }

    public function testIdSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setId(123);
        $this->assertTrue($allowanceCharge->isPersisted());
        $this->assertSame(123, $allowanceCharge->reqId());
        
        $allowanceCharge->setId(456);
        $this->assertSame(456, $allowanceCharge->reqId());
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
        
        $allowanceCharge->setLevel(0);
        $this->assertSame(0, $allowanceCharge->getLevel());
        
        $allowanceCharge->setLevel(1);
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
        $this->assertSame(5, $allowanceCharge->getTaxRateId());
        
        $allowanceCharge->setTaxRateId(10);
        $this->assertSame(10, $allowanceCharge->getTaxRateId());
    }

    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $this->assertFalse($allowanceCharge->isPersisted());
    }

    public function testIsPersistedReturnsTrueAfterSetId(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setId(1);
        $this->assertTrue($allowanceCharge->isPersisted());
    }

    public function testReasonCodeFormats(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        foreach (['REA', 'DIS', 'CHG', 'SUP', 'TAX'] as $code) {
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
        
        $allowanceCharge->setId(1);
        $allowanceCharge->setIdentifier(true);
        $allowanceCharge->setLevel(0);
        $allowanceCharge->setReasonCode('DIS');
        $allowanceCharge->setReason('Volume discount for bulk purchase');
        $allowanceCharge->setMultiplierFactorNumeric(10);
        $allowanceCharge->setAmount(100);
        $allowanceCharge->setBaseAmount(1000);
        $allowanceCharge->setTaxRateId(1);
        $allowanceCharge->setTaxrate($this->taxRate);
        
        $this->assertTrue($allowanceCharge->isPersisted());
        $this->assertSame(1, $allowanceCharge->reqId());
        $this->assertTrue($allowanceCharge->getIdentifier());
        $this->assertSame(0, $allowanceCharge->getLevel());
        $this->assertSame('DIS', $allowanceCharge->getReasonCode());
        $this->assertSame('Volume discount for bulk purchase', $allowanceCharge->getReason());
        $this->assertSame(10, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(100, $allowanceCharge->getAmount());
        $this->assertSame(1000, $allowanceCharge->getBaseAmount());
        $this->assertSame(1, $allowanceCharge->getTaxRateId());
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
        
        $this->assertSame($allowanceCharge->reqId(), $allowanceCharge->reqId());
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
        
        $this->assertFalse($allowanceCharge->getIdentifier());
        $allowanceCharge->setIdentifier(!$allowanceCharge->getIdentifier());
        $this->assertTrue($allowanceCharge->getIdentifier());
        $allowanceCharge->setIdentifier(!$allowanceCharge->getIdentifier());
        $this->assertFalse($allowanceCharge->getIdentifier());
    }

    public function testCalculationScenario(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $baseAmount = 1000;
        $multiplier = 15;
        $expectedAmount = 150;
        
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
        
        $allowanceCharge->setAmount(50);
        $allowanceCharge->setMultiplierFactorNumeric(1);
        $allowanceCharge->setBaseAmount(0);
        
        $this->assertSame(50, $allowanceCharge->getAmount());
        $this->assertSame(1, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(0, $allowanceCharge->getBaseAmount());
    }

    public function testIdIntType(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setId(999);
        $this->assertSame(999, $allowanceCharge->reqId());
        $this->assertIsInt($allowanceCharge->reqId());
    }

    public function testTaxRateIdStringConversion(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setTaxRateId(0);
        $this->assertSame(0, $allowanceCharge->getTaxRateId());
        $this->assertIsInt($allowanceCharge->getTaxRateId());
        
        $allowanceCharge->setTaxRateId(123);
        $this->assertSame(123, $allowanceCharge->getTaxRateId());
        $this->assertIsInt($allowanceCharge->getTaxRateId());
    }
}