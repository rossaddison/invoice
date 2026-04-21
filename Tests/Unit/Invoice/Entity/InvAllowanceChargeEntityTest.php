<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use PHPUnit\Framework\TestCase;

class InvAllowanceChargeEntityTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $iac = new InvAllowanceCharge();
        $this->assertSame('', $iac->getId());
        $this->assertSame('', $iac->getInvId());
        $this->assertSame('', $iac->getAllowanceChargeId());
        $this->assertSame('', $iac->getAmount());
        $this->assertSame('', $iac->getVatOrTax());
        $this->assertNull($iac->getAllowanceCharge());
    }

    public function testConstructorWithAllParameters(): void
    {
        $iac = new InvAllowanceCharge(
            id: 1,
            inv_id: 10,
            allowance_charge_id: 3,
            amount: 75.00,
            vat_or_tax: 15.00,
        );

        $this->assertSame('1', $iac->getId());
        $this->assertSame('10', $iac->getInvId());
        $this->assertSame('3', $iac->getAllowanceChargeId());
        $this->assertSame('75', $iac->getAmount());
        $this->assertSame('15', $iac->getVatOrTax());
    }

    public function testSetIdAndGetId(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setId(50);
        $this->assertSame('50', $iac->getId());
    }

    public function testGetIdReturnsStringType(): void
    {
        $iac = new InvAllowanceCharge(id: 42);
        $this->assertIsString($iac->getId());
    }

    public function testInvIdSetterAndGetter(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setInvId(99);
        $this->assertSame('99', $iac->getInvId());
    }

    public function testInvIdIsReturnedAsString(): void
    {
        $iac = new InvAllowanceCharge(inv_id: 7);
        $this->assertIsString($iac->getInvId());
        $this->assertSame('7', $iac->getInvId());
    }

    public function testAllowanceChargeIdSetterAndGetter(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setAllowanceChargeId(5);
        $this->assertSame('5', $iac->getAllowanceChargeId());
    }

    public function testAllowanceChargeIdIsReturnedAsString(): void
    {
        $iac = new InvAllowanceCharge(allowance_charge_id: 12);
        $this->assertIsString($iac->getAllowanceChargeId());
        $this->assertSame('12', $iac->getAllowanceChargeId());
    }

    public function testAmountSetterAndGetter(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setAmount(250.75);
        $this->assertSame('250.75', $iac->getAmount());
    }

    public function testAmountIsReturnedAsString(): void
    {
        $iac = new InvAllowanceCharge(amount: 100.00);
        $this->assertIsString($iac->getAmount());
    }

    public function testVatOrTaxSetterAndGetter(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setVatOrTax(20.00);
        $this->assertSame('20', $iac->getVatOrTax());
    }

    public function testVatOrTaxIsReturnedAsString(): void
    {
        $iac = new InvAllowanceCharge(vat_or_tax: 5.00);
        $this->assertIsString($iac->getVatOrTax());
    }

    public function testAllowanceChargeRelationSetterAndGetter(): void
    {
        $iac = new InvAllowanceCharge();
        $allowanceCharge = $this->createMock(AllowanceCharge::class);

        $iac->setAllowanceCharge($allowanceCharge);
        $this->assertSame($allowanceCharge, $iac->getAllowanceCharge());

        $iac->setAllowanceCharge(null);
        $this->assertNull($iac->getAllowanceCharge());
    }

    public function testZeroAmounts(): void
    {
        $iac = new InvAllowanceCharge(
            id: 1,
            inv_id: 1,
            allowance_charge_id: 1,
            amount: 0.00,
            vat_or_tax: 0.00,
        );

        $this->assertSame('0', $iac->getAmount());
        $this->assertSame('0', $iac->getVatOrTax());
    }

    public function testMultipleSetIdCalls(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setId(1);
        $iac->setId(99);
        $this->assertSame('99', $iac->getId());
    }
}
