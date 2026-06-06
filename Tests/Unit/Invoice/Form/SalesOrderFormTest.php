<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\SalesOrder\SalesOrderForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SalesOrderFormTest extends TestCase
{
    private function buildEntity(): SalesOrder
    {
        $entity = new SalesOrder();
        $entity->setQuoteId(5);
        $entity->setInvId(10);
        $entity->setClientId(3);
        $entity->setGroupId(2);
        return $entity;
    }

    public function testDefaultsAreSet(): void
    {
        $form = new SalesOrderForm();

        $this->assertSame('', $form->getNumber());
        $this->assertSame('', $form->getDateCreated());
        $this->assertNull($form->getQuoteId());
        $this->assertNull($form->getInvId());
        $this->assertNull($form->getGroupId());
        $this->assertNull($form->getClientId());
        $this->assertSame(1, $form->getStatusId());
        $this->assertSame(0.0, $form->getDiscountAmount());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new SalesOrderForm())->getFormName());
    }

    public function testShowPopulatesMandatoryFields(): void
    {
        $entity = $this->buildEntity();
        $entity->setStatusId(2);

        $form = SalesOrderForm::show($entity);

        $this->assertSame(5, $form->getQuoteId());
        $this->assertSame(10, $form->getInvId());
        $this->assertSame(3, $form->getClientId());
        $this->assertSame(2, $form->getGroupId());
        $this->assertSame(2, $form->getStatusId());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateCreated());
    }

    public function testShowPopulatesOptionalTextFields(): void
    {
        $entity = $this->buildEntity();
        $entity->setStatusId(1);
        // SalesOrder constructor sets number, notes, payment_term to ''
        // access getters after show() — they remain ''

        $form = SalesOrderForm::show($entity);

        $this->assertSame('', $form->getNumber());
        $this->assertSame('', $form->getNotes());
        $this->assertSame('', $form->getPaymentTerm());
        $this->assertSame(0.0, $form->getDiscountAmount());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = $this->buildEntity();
        $entity->setStatusId(1);

        $this->assertNotSame(
            SalesOrderForm::show($entity),
            SalesOrderForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = $this->buildEntity();
        $entity->setStatusId(3);

        $form = SalesOrderForm::show($entity);

        $this->assertIsInt($form->getQuoteId());
        $this->assertIsInt($form->getInvId());
        $this->assertIsInt($form->getClientId());
        $this->assertIsInt($form->getGroupId());
        $this->assertIsInt($form->getStatusId());
        $this->assertIsFloat($form->getDiscountAmount());
    }
}
