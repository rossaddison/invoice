<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\FamilyCustom\FamilyCustom;
use App\Invoice\FamilyCustom\FamilyCustomForm;
use PHPUnit\Framework\TestCase;

class FamilyCustomFormTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $form = new FamilyCustomForm();

        $this->assertNull($form->reqId());
        $this->assertNull($form->getCustomFieldId());
        $this->assertSame('', $form->getValue());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new FamilyCustomForm())->getFormName());
    }

    public function testShowPopulatesFields(): void
    {
        $entity = new FamilyCustom();
        $entity->setId(7);
        $entity->setCustomFieldId(3);
        $entity->setValue('Electronics');

        $form = FamilyCustomForm::show($entity);

        $this->assertSame(7, $form->reqId());
        $this->assertSame(3, $form->getCustomFieldId());
        $this->assertSame('Electronics', $form->getValue());
    }

    public function testShowWithNullValueCastsToEmptyString(): void
    {
        // FamilyCustom::getValue() returns ?string; null casts to '' via (string)
        $entity = new FamilyCustom();
        $entity->setId(1);
        $entity->setCustomFieldId(2);
        // no setValue call — value defaults to null

        $form = FamilyCustomForm::show($entity);

        $this->assertSame('', $form->getValue());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new FamilyCustom();
        $entity->setId(5);
        $entity->setCustomFieldId(1);
        $entity->setValue('Books');

        $this->assertNotSame(
            FamilyCustomForm::show($entity),
            FamilyCustomForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new FamilyCustom();
        $entity->setId(3);
        $entity->setCustomFieldId(4);
        $entity->setValue('Clothing');

        $form = FamilyCustomForm::show($entity);

        $this->assertIsInt($form->reqId());
        $this->assertIsInt($form->getCustomFieldId());
        $this->assertIsString($form->getValue());
    }
}
