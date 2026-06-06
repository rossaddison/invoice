<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\ProductProperty\ProductProperty;
use App\Invoice\ProductProperty\ProductPropertyForm;
use PHPUnit\Framework\TestCase;

class ProductPropertyFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new ProductPropertyForm();

        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getValue());
        $this->assertNull($form->getProduct());
        $this->assertNull($form->getProductId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ProductPropertyForm())->getFormName());
    }

    public function testShowUsesPassedProductId(): void
    {
        // show() takes ?int $product_id as 2nd param
        $entity = new ProductProperty();
        $entity->setName('Colour');
        $entity->setValue('Red');

        $form = ProductPropertyForm::show($entity, 7);

        $this->assertSame(7, $form->getProductId());
        $this->assertSame('Colour', $form->getName());
        $this->assertSame('Red', $form->getValue());
    }

    public function testShowWithEntityDefaults(): void
    {
        // ProductProperty defaults: name='', value=''
        $entity = new ProductProperty();

        $form = ProductPropertyForm::show($entity, 3);

        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getValue());
    }

    public function testShowProductRelationIsNull(): void
    {
        // Product relation is not loaded in tests
        $entity = new ProductProperty();

        $form = ProductPropertyForm::show($entity, 1);

        $this->assertNull($form->getProduct());
    }

    public function testShowWithNullProductId(): void
    {
        $entity = new ProductProperty();

        $form = ProductPropertyForm::show($entity, null);

        $this->assertNull($form->getProductId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new ProductProperty();

        $this->assertNotSame(
            ProductPropertyForm::show($entity, 1),
            ProductPropertyForm::show($entity, 1)
        );
    }
}
