<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Invoice\CustomField\CustomFieldForm;
use PHPUnit\Framework\TestCase;

class CustomFieldFormTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $form = new CustomFieldForm();

        $this->assertSame('', $form->getTable());
        $this->assertSame('', $form->getLabel());
        $this->assertSame('', $form->getType());
        $this->assertNull($form->getLocation());
        $this->assertNull($form->getOrder());
        $this->assertFalse($form->getRequired());
        $this->assertNull($form->getEmailMinLength());
        $this->assertNull($form->getEmailMaxLength());
        $this->assertFalse($form->getEmailMultiple());
        $this->assertSame('', $form->getTextAreaWrap());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new CustomFieldForm())->getFormName());
    }

    public function testShowPopulatesTextField(): void
    {
        $entity = new CustomField();
        $entity->setTable('inv');
        $entity->setLabel('Purchase Order Number');
        $entity->setType('TEXT');
        $entity->setLocation(1);
        $entity->setOrder(10);
        $entity->setRequired(true);
        $entity->setTextMinLength(0);
        $entity->setTextMaxLength(50);

        $form = CustomFieldForm::show($entity);

        $this->assertSame('inv', $form->getTable());
        $this->assertSame('Purchase Order Number', $form->getLabel());
        $this->assertSame('TEXT', $form->getType());
        $this->assertSame(1, $form->getLocation());
        $this->assertSame(10, $form->getOrder());
        $this->assertTrue($form->getRequired());
        $this->assertSame(0, $form->getTextMinLength());
        $this->assertSame(50, $form->getTextMaxLength());
    }

    public function testShowPopulatesTextAreaField(): void
    {
        $entity = new CustomField();
        $entity->setTable('client');
        $entity->setLabel('Notes');
        $entity->setType('TEXTAREA');
        $entity->setLocation(2);
        $entity->setOrder(20);
        $entity->setRequired(false);
        $entity->setTextAreaCols(40);
        $entity->setTextAreaRows(5);
        $entity->setTextAreaWrap('hard');

        $form = CustomFieldForm::show($entity);

        $this->assertSame('TEXTAREA', $form->getType());
        $this->assertSame(40, $form->getTextAreaCols());
        $this->assertSame(5, $form->getTextAreaRows());
        $this->assertSame('hard', $form->getTextAreaWrap());
        $this->assertFalse($form->getRequired());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new CustomField();
        $entity->setTable('inv');
        $entity->setType('TEXT');
        $entity->setLocation(1);
        $entity->setOrder(1);

        $this->assertNotSame(
            CustomFieldForm::show($entity),
            CustomFieldForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new CustomField();
        $entity->setTable('inv');
        $entity->setLabel('Ref');
        $entity->setType('TEXT');
        $entity->setLocation(1);
        $entity->setOrder(5);
        $entity->setRequired(false);
        $entity->setEmailMinLength(0);
        $entity->setEmailMaxLength(100);

        $form = CustomFieldForm::show($entity);

        $this->assertIsString($form->getTable());
        $this->assertIsString($form->getType());
        $this->assertIsInt($form->getLocation());
        $this->assertIsInt($form->getOrder());
        $this->assertIsBool($form->getRequired());
        $this->assertIsInt($form->getEmailMinLength());
        $this->assertIsInt($form->getEmailMaxLength());
    }
}
