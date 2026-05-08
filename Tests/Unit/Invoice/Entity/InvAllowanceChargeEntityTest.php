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
        $this->assertFalse($iac->hasIdentity());
        $this->assertNull($iac->getAmount());
        $this->assertNull($iac->getVatOrTax());
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

        $this->assertSame(1, $iac->reqId());
        $this->assertSame(10, $iac->reqInvId());
        $this->assertSame(3, $iac->reqAllowanceChargeId());
        $this->assertSame(75.0, $iac->getAmount());
        $this->assertSame(15.0, $iac->getVatOrTax());
    }

    public function testSetIdAndGetId(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setId(50);
        $this->assertSame(50, $iac->reqId());
    }

    public function testGetIdReturnsIntType(): void
    {
        $iac = new InvAllowanceCharge(id: 42);
        $this->assertIsInt($iac->reqId());
    }

    public function testInvIdSetterAndGetter(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setInvId(99);
        $this->assertSame(99, $iac->reqInvId());
    }

    public function testInvIdIsReturnedAsInt(): void
    {
        $iac = new InvAllowanceCharge(inv_id: 7);
        $this->assertIsInt($iac->reqInvId());
        $this->assertSame(7, $iac->reqInvId());
    }

    public function testAllowanceChargeIdSetterAndGetter(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setAllowanceChargeId(5);
        $this->assertSame(5, $iac->reqAllowanceChargeId());
    }

    public function testAllowanceChargeIdIsReturnedAsInt(): void
    {
        $iac = new InvAllowanceCharge(allowance_charge_id: 12);
        $this->assertIsInt($iac->reqAllowanceChargeId());
        $this->assertSame(12, $iac->reqAllowanceChargeId());
    }

    public function testAmountSetterAndGetter(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setAmount(250.75);
        $this->assertSame(250.75, $iac->getAmount());
    }

    public function testAmountIsReturnedAsFloat(): void
    {
        $iac = new InvAllowanceCharge(amount: 100.00);
        $this->assertIsFloat($iac->getAmount());
    }

    public function testVatOrTaxSetterAndGetter(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setVatOrTax(20.00);
        $this->assertSame(20.0, $iac->getVatOrTax());
    }

    public function testVatOrTaxIsReturnedAsFloat(): void
    {
        $iac = new InvAllowanceCharge(vat_or_tax: 5.00);
        $this->assertIsFloat($iac->getVatOrTax());
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

        $this->assertSame(0.0, $iac->getAmount());
        $this->assertSame(0.0, $iac->getVatOrTax());
    }

    public function testMultipleSetIdCalls(): void
    {
        $iac = new InvAllowanceCharge();
        $iac->setId(1);
        $iac->setId(99);
        $this->assertSame(99, $iac->reqId());
    }
}
