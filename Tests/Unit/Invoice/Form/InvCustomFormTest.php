<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\InvCustom\InvCustom;
use App\Invoice\InvCustom\InvCustomForm;
use PHPUnit\Framework\TestCase;

class InvCustomFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new InvCustomForm();

        $this->assertNull($form->getInvId());
        $this->assertNull($form->getCustomFieldId());
        $this->assertSame('', $form->getValue());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new InvCustomForm())->getFormName());
    }

    public function testShowUsesPassedInvId(): void
    {
        // show() takes inv_id as 2nd parameter, NOT from entity
        $entity = new InvCustom();
        $entity->setCustomFieldId(3);

        $form = InvCustomForm::show($entity, 42);

        $this->assertSame(42, $form->getInvId());
        $this->assertSame(3, $form->getCustomFieldId());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new InvCustom();
        $entity->setCustomFieldId(5);
        $entity->setValue('Custom text value');

        $form = InvCustomForm::show($entity, 7);

        $this->assertSame(7, $form->getInvId());
        $this->assertSame(5, $form->getCustomFieldId());
        $this->assertSame('Custom text value', $form->getValue());
    }

    public function testShowWithNullValueCopiesEntityDefault(): void
    {
        // InvCustom::getValue() returns ?string defaulting to ''
        $entity = new InvCustom();
        $entity->setCustomFieldId(2);

        $form = InvCustomForm::show($entity, 1);

        $this->assertSame('', $form->getValue());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new InvCustom();
        $entity->setCustomFieldId(1);

        $this->assertNotSame(
            InvCustomForm::show($entity, 1),
            InvCustomForm::show($entity, 1)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new InvCustom();
        $entity->setCustomFieldId(4);
        $entity->setValue('Test');

        $form = InvCustomForm::show($entity, 9);

        $this->assertIsInt($form->getInvId());
        $this->assertIsInt($form->getCustomFieldId());
        $this->assertIsString($form->getValue());
    }
}
