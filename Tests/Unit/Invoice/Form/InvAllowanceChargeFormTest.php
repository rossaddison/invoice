<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeForm;
use PHPUnit\Framework\TestCase;

class InvAllowanceChargeFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new InvAllowanceChargeForm();

        $this->assertNull($form->getAllowanceChargeId());
        $this->assertNull($form->getAmount());
        $this->assertNull($form->getVatOrTax());
        $this->assertNull($form->getInvId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new InvAllowanceChargeForm())->getFormName());
    }

    public function testShowUsesPassedInvId(): void
    {
        // show() takes ?int $inv_id as 2nd param; allowance_charge_id from entity
        $entity = new InvAllowanceCharge();
        $entity->setAllowanceChargeId(7);

        $form = InvAllowanceChargeForm::show($entity, 15);

        $this->assertSame(7, $form->getAllowanceChargeId());
        $this->assertSame(15, $form->getInvId());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new InvAllowanceCharge();
        $entity->setAllowanceChargeId(4);
        $entity->setAmount(50.00);
        $entity->setVatOrTax(10.00);

        $form = InvAllowanceChargeForm::show($entity, 20);

        $this->assertSame(4, $form->getAllowanceChargeId());
        $this->assertSame(50.0, $form->getAmount());
        $this->assertSame(10.0, $form->getVatOrTax());
        $this->assertSame(20, $form->getInvId());
    }

    public function testShowWithNullInvId(): void
    {
        $entity = new InvAllowanceCharge();
        $entity->setAllowanceChargeId(1);

        $form = InvAllowanceChargeForm::show($entity, null);

        $this->assertNull($form->getInvId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new InvAllowanceCharge();
        $entity->setAllowanceChargeId(1);

        $this->assertNotSame(
            InvAllowanceChargeForm::show($entity, 1),
            InvAllowanceChargeForm::show($entity, 1)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new InvAllowanceCharge();
        $entity->setAllowanceChargeId(2);
        $entity->setAmount(25.50);
        $entity->setVatOrTax(5.00);

        $form = InvAllowanceChargeForm::show($entity, 3);

        $this->assertIsInt($form->getAllowanceChargeId());
        $this->assertIsFloat($form->getAmount());
        $this->assertIsFloat($form->getVatOrTax());
        $this->assertIsInt($form->getInvId());
    }
}
