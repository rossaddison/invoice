<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\SalesOrderCustom\SalesOrderCustom;
use App\Invoice\SalesOrderCustom\SalesOrderCustomForm;
use PHPUnit\Framework\TestCase;

class SalesOrderCustomFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new SalesOrderCustomForm();

        $this->assertNull($form->getSalesOrderId());
        $this->assertNull($form->getCustomFieldId());
        $this->assertSame('', $form->getValue());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new SalesOrderCustomForm())->getFormName());
    }

    public function testShowPopulatesIds(): void
    {
        $entity = new SalesOrderCustom();
        $entity->setSalesOrderId(9);
        $entity->setCustomFieldId(4);

        $form = SalesOrderCustomForm::show($entity);

        $this->assertSame(9, $form->getSalesOrderId());
        $this->assertSame(4, $form->getCustomFieldId());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new SalesOrderCustom();
        $entity->setSalesOrderId(11);
        $entity->setCustomFieldId(7);
        $entity->setValue('Sales order custom text');

        $form = SalesOrderCustomForm::show($entity);

        $this->assertSame(11, $form->getSalesOrderId());
        $this->assertSame(7, $form->getCustomFieldId());
        $this->assertSame('Sales order custom text', $form->getValue());
    }

    public function testShowWithEmptyValueCopiesEntityDefault(): void
    {
        // SalesOrderCustom::getValue() returns string defaulting to ''
        $entity = new SalesOrderCustom();
        $entity->setSalesOrderId(1);
        $entity->setCustomFieldId(2);

        $form = SalesOrderCustomForm::show($entity);

        $this->assertSame('', $form->getValue());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new SalesOrderCustom();
        $entity->setSalesOrderId(1);
        $entity->setCustomFieldId(1);

        $this->assertNotSame(
            SalesOrderCustomForm::show($entity),
            SalesOrderCustomForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new SalesOrderCustom();
        $entity->setSalesOrderId(3);
        $entity->setCustomFieldId(6);
        $entity->setValue('Test value');

        $form = SalesOrderCustomForm::show($entity);

        $this->assertIsInt($form->getSalesOrderId());
        $this->assertIsInt($form->getCustomFieldId());
        $this->assertIsString($form->getValue());
    }
}
