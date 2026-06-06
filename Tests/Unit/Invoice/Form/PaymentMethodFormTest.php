<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\PaymentMethod\PaymentMethod;
use App\Invoice\PaymentMethod\PaymentMethodForm;
use PHPUnit\Framework\TestCase;

class PaymentMethodFormTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $form = new PaymentMethodForm();

        $this->assertSame('', $form->getName());
        $this->assertTrue($form->getActive());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new PaymentMethodForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new PaymentMethod();
        $entity->setName('Bank Transfer');
        $entity->setActive(true);

        $form = PaymentMethodForm::show($entity);

        $this->assertSame('Bank Transfer', $form->getName());
        $this->assertTrue($form->getActive());
    }

    public function testShowWithInactiveMethod(): void
    {
        $entity = new PaymentMethod();
        $entity->setName('Cheque');
        $entity->setActive(false);

        $form = PaymentMethodForm::show($entity);

        $this->assertSame('Cheque', $form->getName());
        $this->assertFalse($form->getActive());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new PaymentMethod();
        $entity->setName('Card');
        $entity->setActive(true);

        $this->assertNotSame(
            PaymentMethodForm::show($entity),
            PaymentMethodForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new PaymentMethod();
        $entity->setName('Direct Debit');
        $entity->setActive(true);

        $form = PaymentMethodForm::show($entity);

        $this->assertIsString($form->getName());
        $this->assertIsBool($form->getActive());
    }
}
