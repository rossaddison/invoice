<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Infrastructure\Persistence\TaxRate\TaxRate;
use Codeception\Test\Unit;

class TaxRateEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $taxRate = new TaxRate();

        $this->assertFalse($taxRate->isPersisted());
        $this->assertSame('', $taxRate->getTaxRateCode());
        $this->assertSame('', $taxRate->getPeppolTaxRateCode());
        $this->assertSame('', $taxRate->getStorecoveTaxType());
        $this->assertSame('', $taxRate->getTaxRateName());
        $this->assertSame(0.00, $taxRate->getTaxRatePercent());
        $this->assertFalse($taxRate->getTaxRateDefault());
    }

    public function testConstructorWithAllParameters(): void
    {
        $taxRate = new TaxRate(
            'S',
            'S',
            'standard',
            'Standard Rate',
            20.00,
            true
        );

        $this->assertFalse($taxRate->isPersisted());
        $this->assertSame('S', $taxRate->getTaxRateCode());
        $this->assertSame('S', $taxRate->getPeppolTaxRateCode());
        $this->assertSame('standard', $taxRate->getStorecoveTaxType());
        $this->assertSame('Standard Rate', $taxRate->getTaxRateName());
        $this->assertSame(20.00, $taxRate->getTaxRatePercent());
        $this->assertTrue($taxRate->getTaxRateDefault());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $taxRate = new TaxRate();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('TaxRate has no ID (not persisted yet)');
        $taxRate->reqId();
    }

    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $taxRate = new TaxRate();

        $this->assertFalse($taxRate->isPersisted());
    }

    public function testIsPersistedReturnsTrueAfterSetTaxRateId(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRateId(1);
        $this->assertTrue($taxRate->isPersisted());
    }

    public function testReqIdReturnsIntAfterSetTaxRateId(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRateId(42);
        $this->assertSame(42, $taxRate->reqId());
        $this->assertIsInt($taxRate->reqId());
    }

    public function testSetTaxRateIdAndReqId(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRateId(1);
        $this->assertSame(1, $taxRate->reqId());

        $taxRate->setTaxRateId(999);
        $this->assertSame(999, $taxRate->reqId());
    }

    public function testTaxRateCodeSetterAndGetter(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRateCode('S');
        $this->assertSame('S', $taxRate->getTaxRateCode());

        $taxRate->setTaxRateCode('Z');
        $this->assertSame('Z', $taxRate->getTaxRateCode());
    }

    public function testPeppolTaxRateCodeSetterAndGetter(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setPeppolTaxRateCode('S');
        $this->assertSame('S', $taxRate->getPeppolTaxRateCode());

        $taxRate->setPeppolTaxRateCode('Z');
        $this->assertSame('Z', $taxRate->getPeppolTaxRateCode());
    }

    public function testStorecoveTaxTypeSetterAndGetter(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setStorecoveTaxType('standard');
        $this->assertSame('standard', $taxRate->getStorecoveTaxType());

        $taxRate->setStorecoveTaxType('exempt');
        $this->assertSame('exempt', $taxRate->getStorecoveTaxType());
    }

    public function testTaxRateNameSetterAndGetter(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRateName('Standard Rate');
        $this->assertSame('Standard Rate', $taxRate->getTaxRateName());

        $taxRate->setTaxRateName('Zero Rate');
        $this->assertSame('Zero Rate', $taxRate->getTaxRateName());
    }

    public function testTaxRatePercentSetterAndGetter(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRatePercent(20.00);
        $this->assertSame(20.00, $taxRate->getTaxRatePercent());

        $taxRate->setTaxRatePercent(5.50);
        $this->assertSame(5.50, $taxRate->getTaxRatePercent());
    }

    public function testTaxRateDefaultSetterAndGetter(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRateDefault(true);
        $this->assertTrue($taxRate->getTaxRateDefault());

        $taxRate->setTaxRateDefault(false);
        $this->assertFalse($taxRate->getTaxRateDefault());
    }

    public function testTaxRatePercentWithZero(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRatePercent(0.00);
        $this->assertSame(0.00, $taxRate->getTaxRatePercent());
    }

    public function testTaxRatePercentWithDecimal(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRatePercent(17.50);
        $this->assertSame(17.50, $taxRate->getTaxRatePercent());
    }

    public function testNullableTaxRateCode(): void
    {
        $taxRate = new TaxRate(null);

        $this->assertNull($taxRate->getTaxRateCode());
    }

    public function testNullablePeppolTaxRateCode(): void
    {
        $taxRate = new TaxRate('S', null);

        $this->assertNull($taxRate->getPeppolTaxRateCode());
    }

    public function testNullableTaxRateName(): void
    {
        $taxRate = new TaxRate('S', 'S', 'standard', null);

        $this->assertNull($taxRate->getTaxRateName());
    }

    public function testNullableTaxRatePercent(): void
    {
        $taxRate = new TaxRate('S', 'S', 'standard', 'Standard', null);

        $this->assertNull($taxRate->getTaxRatePercent());
    }

    public function testTaxRateDefaultToggling(): void
    {
        $taxRate = new TaxRate();

        $this->assertFalse($taxRate->getTaxRateDefault());
        $taxRate->setTaxRateDefault(!$taxRate->getTaxRateDefault());
        $this->assertTrue($taxRate->getTaxRateDefault());
        $taxRate->setTaxRateDefault(!$taxRate->getTaxRateDefault());
        $this->assertFalse($taxRate->getTaxRateDefault());
    }

    public function testEmptyStringValues(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRateCode('');
        $taxRate->setPeppolTaxRateCode('');
        $taxRate->setStorecoveTaxType('');
        $taxRate->setTaxRateName('');

        $this->assertSame('', $taxRate->getTaxRateCode());
        $this->assertSame('', $taxRate->getPeppolTaxRateCode());
        $this->assertSame('', $taxRate->getStorecoveTaxType());
        $this->assertSame('', $taxRate->getTaxRateName());
    }

    public function testUnicodeCharactersInName(): void
    {
        $taxRate = new TaxRate();
        $unicodeName = 'Mehrwertsteuer 世界中の税率 20%';

        $taxRate->setTaxRateName($unicodeName);
        $this->assertSame($unicodeName, $taxRate->getTaxRateName());
    }

    public function testSpecialCharactersInName(): void
    {
        $taxRate = new TaxRate();
        $specialName = 'Tax Rate: 20% (Standard) - Valid from 01/01/2024!';

        $taxRate->setTaxRateName($specialName);
        $this->assertSame($specialName, $taxRate->getTaxRateName());
    }

    public function testGetterMethodsConsistency(): void
    {
        $taxRate = new TaxRate('S', 'S', 'standard', 'Standard Rate', 20.00, true);

        $this->assertSame($taxRate->getTaxRateCode(), $taxRate->getTaxRateCode());
        $this->assertSame($taxRate->getPeppolTaxRateCode(), $taxRate->getPeppolTaxRateCode());
        $this->assertSame($taxRate->getStorecoveTaxType(), $taxRate->getStorecoveTaxType());
        $this->assertSame($taxRate->getTaxRateName(), $taxRate->getTaxRateName());
        $this->assertSame($taxRate->getTaxRatePercent(), $taxRate->getTaxRatePercent());
        $this->assertSame($taxRate->getTaxRateDefault(), $taxRate->getTaxRateDefault());
    }

    public function testCompleteEntitySetup(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRateId(1);
        $taxRate->setTaxRateCode('S');
        $taxRate->setPeppolTaxRateCode('S');
        $taxRate->setStorecoveTaxType('standard');
        $taxRate->setTaxRateName('Standard Rate');
        $taxRate->setTaxRatePercent(20.00);
        $taxRate->setTaxRateDefault(true);

        $this->assertTrue($taxRate->isPersisted());
        $this->assertSame(1, $taxRate->reqId());
        $this->assertSame('S', $taxRate->getTaxRateCode());
        $this->assertSame('S', $taxRate->getPeppolTaxRateCode());
        $this->assertSame('standard', $taxRate->getStorecoveTaxType());
        $this->assertSame('Standard Rate', $taxRate->getTaxRateName());
        $this->assertSame(20.00, $taxRate->getTaxRatePercent());
        $this->assertTrue($taxRate->getTaxRateDefault());
    }

    public function testCommonTaxRates(): void
    {
        $rates = [0.00, 5.00, 9.00, 12.00, 17.50, 20.00, 25.00];

        foreach ($rates as $rate) {
            $taxRate = new TaxRate();
            $taxRate->setTaxRatePercent($rate);
            $this->assertSame($rate, $taxRate->getTaxRatePercent());
        }
    }

    public function testMultipleUpdates(): void
    {
        $taxRate = new TaxRate();

        foreach (['standard', 'exempt', 'zero', 'reduced'] as $type) {
            $taxRate->setStorecoveTaxType($type);
            $this->assertSame($type, $taxRate->getStorecoveTaxType());
        }
    }

    public function testReqIdReturnTypeIsInt(): void
    {
        $taxRate = new TaxRate();

        $taxRate->setTaxRateId(1);
        $this->assertIsInt($taxRate->reqId());
    }
}