<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\ProductCustom\ProductCustom;
use App\Invoice\ProductCustom\ProductCustomForm;
use PHPUnit\Framework\TestCase;

class ProductCustomFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new ProductCustomForm();

        $this->assertNull($form->getProductId());
        $this->assertNull($form->getCustomFieldId());
        $this->assertSame('', $form->getValue());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ProductCustomForm())->getFormName());
    }

    public function testShowPopulatesIds(): void
    {
        $entity = new ProductCustom();
        $entity->setProductId(6);
        $entity->setCustomFieldId(4);

        $form = ProductCustomForm::show($entity);

        $this->assertSame(6, $form->getProductId());
        $this->assertSame(4, $form->getCustomFieldId());
    }

    public function testShowPopulatesValue(): void
    {
        $entity = new ProductCustom();
        $entity->setProductId(3);
        $entity->setCustomFieldId(2);
        $entity->setValue('Custom product tag');

        $form = ProductCustomForm::show($entity);

        $this->assertSame('Custom product tag', $form->getValue());
    }

    public function testShowWithEmptyValueCopiesEntityDefault(): void
    {
        // ProductCustom::getValue() defaults to ''
        $entity = new ProductCustom();
        $entity->setProductId(1);
        $entity->setCustomFieldId(1);

        $form = ProductCustomForm::show($entity);

        $this->assertSame('', $form->getValue());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new ProductCustom();
        $entity->setProductId(1);
        $entity->setCustomFieldId(1);

        $this->assertNotSame(
            ProductCustomForm::show($entity),
            ProductCustomForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new ProductCustom();
        $entity->setProductId(2);
        $entity->setCustomFieldId(3);

        $form = ProductCustomForm::show($entity);

        $this->assertIsInt($form->getProductId());
        $this->assertIsInt($form->getCustomFieldId());
        $this->assertIsString($form->getValue());
    }
}
