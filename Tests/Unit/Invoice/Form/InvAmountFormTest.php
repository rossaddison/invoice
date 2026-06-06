<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\InvAmount\InvAmountForm;
use PHPUnit\Framework\TestCase;

class InvAmountFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new InvAmountForm();

        $this->assertNull($form->getInvId());
        $this->assertNull($form->getSign());
        $this->assertNull($form->getItemSubtotal());
        $this->assertNull($form->getItemTaxTotal());
        $this->assertNull($form->getPackhandleshipTotal());
        $this->assertNull($form->getPackhandleshipTax());
        $this->assertNull($form->getTaxTotal());
        $this->assertNull($form->getTotal());
        $this->assertNull($form->getPaid());
        $this->assertNull($form->getBalance());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new InvAmountForm())->getFormName());
    }

    public function testShowPopulatesFields(): void
    {
        $entity = new InvAmount();
        $entity->setInvId(10);
        $entity->setSign(1);
        $entity->setItemSubtotal(100.00);
        $entity->setItemTaxTotal(20.00);
        $entity->setPackhandleshipTotal(5.00);
        $entity->setPackhandleshipTax(1.00);
        $entity->setTaxTotal(20.00);
        $entity->setTotal(120.00);
        $entity->setPaid(60.00);
        $entity->setBalance(60.00);

        $form = InvAmountForm::show($entity);

        $this->assertSame(10, $form->getInvId());
        $this->assertSame(1, $form->getSign());
        $this->assertSame(100.0, $form->getItemSubtotal());
        $this->assertSame(20.0, $form->getItemTaxTotal());
        $this->assertSame(5.0, $form->getPackhandleshipTotal());
        $this->assertSame(1.0, $form->getPackhandleshipTax());
        $this->assertSame(20.0, $form->getTaxTotal());
        $this->assertSame(120.0, $form->getTotal());
        $this->assertSame(60.0, $form->getPaid());
        $this->assertSame(60.0, $form->getBalance());
    }

    public function testShowWithEntityDefaultsUsesEntityValues(): void
    {
        // Entity defaults: sign=1, all amounts=0.00
        $entity = new InvAmount();
        $entity->setInvId(5);

        $form = InvAmountForm::show($entity);

        $this->assertSame(5, $form->getInvId());
        $this->assertSame(1, $form->getSign());
        $this->assertSame(0.0, $form->getItemSubtotal());
        $this->assertSame(0.0, $form->getTaxTotal());
        $this->assertSame(0.0, $form->getBalance());
    }

    public function testGetRulesContainsRequiredFields(): void
    {
        $rules = (new InvAmountForm())->getRules();

        $this->assertArrayHasKey('inv_id', $rules);
        $this->assertArrayHasKey('item_subtotal', $rules);
        $this->assertArrayHasKey('total', $rules);
        $this->assertCount(7, $rules);
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new InvAmount();
        $entity->setInvId(1);

        $this->assertNotSame(
            InvAmountForm::show($entity),
            InvAmountForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new InvAmount();
        $entity->setInvId(3);

        $form = InvAmountForm::show($entity);

        $this->assertIsInt($form->getInvId());
        $this->assertIsInt($form->getSign());
        $this->assertIsFloat($form->getItemSubtotal());
        $this->assertIsFloat($form->getBalance());
    }
}
