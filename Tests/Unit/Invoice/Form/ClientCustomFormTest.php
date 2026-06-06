<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\ClientCustom\ClientCustom;
use App\Invoice\ClientCustom\ClientCustomForm;
use PHPUnit\Framework\TestCase;

class ClientCustomFormTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $form = new ClientCustomForm();

        $this->assertNull($form->getClientId());
        $this->assertNull($form->getCustomFieldId());
        $this->assertSame('', $form->getValue());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ClientCustomForm())->getFormName());
    }

    public function testShowPopulatesFields(): void
    {
        $entity = new ClientCustom();
        $entity->setClientId(8);
        $entity->setCustomFieldId(3);
        $entity->setValue('PO-2026-001');

        $form = ClientCustomForm::show($entity);

        $this->assertSame(8, $form->getClientId());
        $this->assertSame(3, $form->getCustomFieldId());
        $this->assertSame('PO-2026-001', $form->getValue());
    }

    public function testShowWithNullValueCastsToEmptyString(): void
    {
        // ClientCustom::getValue() returns ?string; null casts to '' via (string)
        $entity = new ClientCustom();
        $entity->setClientId(1);
        $entity->setCustomFieldId(2);

        $form = ClientCustomForm::show($entity);

        $this->assertSame('', $form->getValue());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new ClientCustom();
        $entity->setClientId(5);
        $entity->setCustomFieldId(1);
        $entity->setValue('val');

        $this->assertNotSame(
            ClientCustomForm::show($entity),
            ClientCustomForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new ClientCustom();
        $entity->setClientId(3);
        $entity->setCustomFieldId(7);
        $entity->setValue('reference-123');

        $form = ClientCustomForm::show($entity);

        $this->assertIsInt($form->getClientId());
        $this->assertIsInt($form->getCustomFieldId());
        $this->assertIsString($form->getValue());
    }
}
