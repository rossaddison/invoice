<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\CustomValue\CustomValue;
use App\Invoice\CustomValue\CustomValueForm;
use PHPUnit\Framework\TestCase;

class CustomValueFormTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $form = new CustomValueForm();

        $this->assertNull($form->getCustomFieldId());
        $this->assertSame('', $form->getValue());
        $this->assertNull($form->getCustomField());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new CustomValueForm())->getFormName());
    }

    public function testShowPopulatesFields(): void
    {
        $entity = new CustomValue();
        $entity->setCustomFieldId(4);
        $entity->setValue('High Priority');

        $form = CustomValueForm::show($entity);

        $this->assertSame(4, $form->getCustomFieldId());
        $this->assertSame('High Priority', $form->getValue());
        $this->assertNull($form->getCustomField());
    }

    public function testShowWithEmptyValue(): void
    {
        $entity = new CustomValue();
        $entity->setCustomFieldId(2);
        $entity->setValue('');

        $form = CustomValueForm::show($entity);

        $this->assertSame('', $form->getValue());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new CustomValue();
        $entity->setCustomFieldId(1);
        $entity->setValue('v');

        $this->assertNotSame(
            CustomValueForm::show($entity),
            CustomValueForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new CustomValue();
        $entity->setCustomFieldId(5);
        $entity->setValue('draft');

        $form = CustomValueForm::show($entity);

        $this->assertIsInt($form->getCustomFieldId());
        $this->assertIsString($form->getValue());
    }
}
