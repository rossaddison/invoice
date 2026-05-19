<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\TaxRate\TaxRate;
use PHPUnit\Framework\TestCase;

class TaxRateEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $tr = new TaxRate();
        $this->assertFalse($tr->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $tr = new TaxRate();
        $this->expectException(\LogicException::class);
        $tr->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetTaxRateId(): void
    {
        $tr = new TaxRate();
        $tr->setTaxRateId(1);
        $this->assertTrue($tr->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetTaxRateId(): void
    {
        $tr = new TaxRate();
        $tr->setTaxRateId(5);
        $this->assertSame(5, $tr->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $tr = new TaxRate();
        $this->assertSame('', $tr->getTaxRateCode());
        $this->assertSame('', $tr->getPeppolTaxRateCode());
        $this->assertSame('', $tr->getStorecoveTaxType());
        $this->assertSame('', $tr->getTaxRateName());
        $this->assertSame(0.00, $tr->getTaxRatePercent());
        $this->assertFalse($tr->getTaxRateDefault());
    }

    public function testSetAndGetTaxRateCode(): void
    {
        $tr = new TaxRate();
        $tr->setTaxRateCode('S');
        $this->assertSame('S', $tr->getTaxRateCode());
    }

    public function testSetAndGetPeppolTaxRateCode(): void
    {
        $tr = new TaxRate();
        $tr->setPeppolTaxRateCode('S');
        $this->assertSame('S', $tr->getPeppolTaxRateCode());
    }

    public function testSetAndGetTaxRateName(): void
    {
        $tr = new TaxRate();
        $tr->setTaxRateName('Standard Rate');
        $this->assertSame('Standard Rate', $tr->getTaxRateName());
    }

    public function testSetAndGetTaxRatePercent(): void
    {
        $tr = new TaxRate();
        $tr->setTaxRatePercent(20.00);
        $this->assertSame(20.00, $tr->getTaxRatePercent());
    }

    public function testSetAndGetTaxRateDefault(): void
    {
        $tr = new TaxRate();
        $tr->setTaxRateDefault(true);
        $this->assertTrue($tr->getTaxRateDefault());
    }

    public function testSetAndGetStorecoveTaxType(): void
    {
        $tr = new TaxRate();
        $tr->setStorecoveTaxType('standard');
        $this->assertSame('standard', $tr->getStorecoveTaxType());
    }
}
