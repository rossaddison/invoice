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

        $this->assertSame('', $form->table);
        $this->assertSame('', $form->label);
        $this->assertSame('', $form->type);
        $this->assertNull($form->location);
        $this->assertNull($form->order);
        $this->assertFalse($form->required);
        $this->assertNull($form->email_min_length);
        $this->assertNull($form->email_max_length);
        $this->assertFalse($form->email_multiple);
        $this->assertSame('', $form->text_area_wrap);
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

        $this->assertSame('inv', $form->table);
        $this->assertSame('Purchase Order Number', $form->label);
        $this->assertSame('TEXT', $form->type);
        $this->assertSame(1, $form->location);
        $this->assertSame(10, $form->order);
        $this->assertTrue($form->required);
        $this->assertSame(0, $form->text_min_length);
        $this->assertSame(50, $form->text_max_length);
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

        $this->assertSame('TEXTAREA', $form->type);
        $this->assertSame(40, $form->text_area_cols);
        $this->assertSame(5, $form->text_area_rows);
        $this->assertSame('hard', $form->text_area_wrap);
        $this->assertFalse($form->required);
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

        $this->assertIsString($form->table);
        $this->assertIsString($form->type);
        $this->assertIsInt($form->location);
        $this->assertIsInt($form->order);
        $this->assertIsBool($form->required);
        $this->assertIsInt($form->email_min_length);
        $this->assertIsInt($form->email_max_length);
    }
}
