<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeForm;
use PHPUnit\Framework\TestCase;

class InvItemAllowanceChargeFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new InvItemAllowanceChargeForm();

        $this->assertNull($form->getInvId());
        $this->assertNull($form->getAllowanceChargeId());
        $this->assertNull($form->getAmount());
        $this->assertNull($form->getVatOrTax());
        $this->assertNull($form->getInvItemId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new InvItemAllowanceChargeForm())->getFormName());
    }

    public function testShowUsesPassedInvItemId(): void
    {
        // show() takes ?int $inv_item_id as 2nd param; inv_id and allowance_charge_id from entity
        $entity = new InvItemAllowanceCharge();
        $entity->setInvId(4);
        $entity->setAllowanceChargeId(2);

        $form = InvItemAllowanceChargeForm::show($entity, 8);

        $this->assertSame(4, $form->getInvId());
        $this->assertSame(2, $form->getAllowanceChargeId());
        $this->assertSame(8, $form->getInvItemId());
    }

    public function testShowWithEntityDefaultAmountsAreZero(): void
    {
        // Entity getAmount()/getVatOrTax() return (string) null = ''; form casts to (float) '' = 0.0
        $entity = new InvItemAllowanceCharge();
        $entity->setInvId(1);
        $entity->setAllowanceChargeId(1);

        $form = InvItemAllowanceChargeForm::show($entity, null);

        $this->assertSame(0.0, $form->getAmount());
        $this->assertSame(0.0, $form->getVatOrTax());
        $this->assertNull($form->getInvItemId());
    }

    public function testShowPopulatesAmounts(): void
    {
        $entity = new InvItemAllowanceCharge();
        $entity->setInvId(3);
        $entity->setAllowanceChargeId(5);
        $entity->setAmount(75.00);
        $entity->setVatOrTax(15.00);

        $form = InvItemAllowanceChargeForm::show($entity, 10);

        $this->assertSame(75.0, $form->getAmount());
        $this->assertSame(15.0, $form->getVatOrTax());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new InvItemAllowanceCharge();
        $entity->setInvId(1);
        $entity->setAllowanceChargeId(1);

        $this->assertNotSame(
            InvItemAllowanceChargeForm::show($entity, 1),
            InvItemAllowanceChargeForm::show($entity, 1)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new InvItemAllowanceCharge();
        $entity->setInvId(2);
        $entity->setAllowanceChargeId(3);
        $entity->setAmount(50.00);

        $form = InvItemAllowanceChargeForm::show($entity, 4);

        $this->assertIsInt($form->getInvId());
        $this->assertIsInt($form->getAllowanceChargeId());
        $this->assertIsFloat($form->getAmount());
        $this->assertIsInt($form->getInvItemId());
    }
}
