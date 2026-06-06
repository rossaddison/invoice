<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\InvTaxRate\InvTaxRate;
use App\Invoice\InvTaxRate\InvTaxRateForm;
use PHPUnit\Framework\TestCase;

class InvTaxRateFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new InvTaxRateForm();

        $this->assertSame('', $form->getInvId());
        $this->assertSame('', $form->getTaxRateId());
        $this->assertNull($form->getIncludeItemTax());
        $this->assertNull($form->getInvTaxRateAmount());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new InvTaxRateForm())->getFormName());
    }

    public function testShowCastsIdsToString(): void
    {
        $entity = new InvTaxRate();
        $entity->setInvId(7);
        $entity->setTaxRateId(3);

        $form = InvTaxRateForm::show($entity);

        $this->assertSame('7', $form->getInvId());
        $this->assertSame('3', $form->getTaxRateId());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new InvTaxRate();
        $entity->setInvId(8);
        $entity->setTaxRateId(2);
        $entity->setIncludeItemTax(1);
        $entity->setInvTaxRateAmount(150.50);

        $form = InvTaxRateForm::show($entity);

        $this->assertSame('8', $form->getInvId());
        $this->assertSame('2', $form->getTaxRateId());
        $this->assertSame(1, $form->getIncludeItemTax());
        $this->assertSame(150.50, $form->getInvTaxRateAmount());
    }

    public function testShowWithEntityDefaultAmountIsZero(): void
    {
        // InvTaxRate defaults inv_tax_rate_amount to 0.00
        $entity = new InvTaxRate();
        $entity->setInvId(1);
        $entity->setTaxRateId(1);

        $form = InvTaxRateForm::show($entity);

        $this->assertSame(0.0, $form->getInvTaxRateAmount());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new InvTaxRate();
        $entity->setInvId(1);
        $entity->setTaxRateId(2);

        $this->assertNotSame(
            InvTaxRateForm::show($entity),
            InvTaxRateForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new InvTaxRate();
        $entity->setInvId(4);
        $entity->setTaxRateId(5);

        $form = InvTaxRateForm::show($entity);

        $this->assertIsString($form->getInvId());
        $this->assertIsString($form->getTaxRateId());
        $this->assertIsFloat($form->getInvTaxRateAmount());
    }
}
