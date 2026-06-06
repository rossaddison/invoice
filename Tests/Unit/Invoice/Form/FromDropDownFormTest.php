<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\FromDropDown\FromDropDown;
use App\Invoice\FromDropDown\FromDropDownForm;
use PHPUnit\Framework\TestCase;

class FromDropDownFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new FromDropDownForm();

        $this->assertSame('', $form->getEmail());
        $this->assertFalse($form->getInclude());
        $this->assertFalse($form->getDefaultEmail());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new FromDropDownForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new FromDropDown();
        $entity->setEmail('invoices@example.com');
        $entity->setInclude(true);
        $entity->setDefaultEmail(true);

        $form = FromDropDownForm::show($entity);

        $this->assertSame('invoices@example.com', $form->getEmail());
        $this->assertTrue($form->getInclude());
        $this->assertTrue($form->getDefaultEmail());
    }

    public function testShowWithEntityDefaults(): void
    {
        // Entity defaults: email='', include=false, default_email=false
        $entity = new FromDropDown();

        $form = FromDropDownForm::show($entity);

        $this->assertSame('', $form->getEmail());
        $this->assertFalse($form->getInclude());
        $this->assertFalse($form->getDefaultEmail());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new FromDropDown();
        $entity->setEmail('test@example.com');

        $this->assertNotSame(
            FromDropDownForm::show($entity),
            FromDropDownForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new FromDropDown();
        $entity->setEmail('a@b.com');
        $entity->setInclude(true);
        $entity->setDefaultEmail(false);

        $form = FromDropDownForm::show($entity);

        $this->assertIsString($form->getEmail());
        $this->assertIsBool($form->getInclude());
        $this->assertIsBool($form->getDefaultEmail());
    }
}
