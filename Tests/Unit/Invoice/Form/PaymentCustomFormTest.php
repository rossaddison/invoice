<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\PaymentCustom\PaymentCustom;
use App\Invoice\PaymentCustom\PaymentCustomForm;
use PHPUnit\Framework\TestCase;

class PaymentCustomFormTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $form = new PaymentCustomForm();

        $this->assertNull($form->getPaymentId());
        $this->assertNull($form->getCustomFieldId());
        $this->assertSame('', $form->getValue());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new PaymentCustomForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new PaymentCustom();
        $entity->setPaymentId(7);
        $entity->setCustomFieldId(2);
        $entity->setValue('bank-transfer-ref');

        $form = PaymentCustomForm::show($entity);

        $this->assertSame(7, $form->getPaymentId());
        $this->assertSame(2, $form->getCustomFieldId());
        $this->assertSame('bank-transfer-ref', $form->getValue());
    }

    public function testShowWithEmptyValue(): void
    {
        $entity = new PaymentCustom();
        $entity->setPaymentId(3);
        $entity->setCustomFieldId(1);
        $entity->setValue('');

        $form = PaymentCustomForm::show($entity);

        $this->assertSame('', $form->getValue());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new PaymentCustom();
        $entity->setPaymentId(1);
        $entity->setCustomFieldId(1);
        $entity->setValue('v');

        $this->assertNotSame(
            PaymentCustomForm::show($entity),
            PaymentCustomForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new PaymentCustom();
        $entity->setPaymentId(4);
        $entity->setCustomFieldId(3);
        $entity->setValue('note');

        $form = PaymentCustomForm::show($entity);

        $this->assertIsInt($form->getPaymentId());
        $this->assertIsInt($form->getCustomFieldId());
        $this->assertIsString($form->getValue());
    }
}
