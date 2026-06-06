<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\ProductImage\ProductImage;
use App\Invoice\ProductImage\ProductImageForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ProductImageFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new ProductImageForm();

        $this->assertSame('', $form->getFileNameOriginal());
        $this->assertSame('', $form->getFileNameNew());
        $this->assertSame('', $form->getDescription());
        $this->assertSame('', $form->getUploadedDate());
        $this->assertNull($form->getProductId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ProductImageForm())->getFormName());
    }

    public function testShowUsesPassedProductId(): void
    {
        // show() takes ?int $product_id as 2nd param
        $entity = new ProductImage();

        $form = ProductImageForm::show($entity, 11);

        $this->assertSame(11, $form->getProductId());
    }

    public function testShowPopulatesStringFields(): void
    {
        $entity = new ProductImage();
        $entity->setFileNameOriginal('photo.jpg');
        $entity->setFileNameNew('photo_thumb.jpg');
        $entity->setDescription('Product photo');

        $form = ProductImageForm::show($entity, 2);

        $this->assertSame('photo.jpg', $form->getFileNameOriginal());
        $this->assertSame('photo_thumb.jpg', $form->getFileNameNew());
        $this->assertSame('Product photo', $form->getDescription());
    }

    public function testShowUploadedDateIsDateTimeImmutable(): void
    {
        // ProductImage constructor initializes uploaded_date to new DateTimeImmutable()
        $entity = new ProductImage();

        $form = ProductImageForm::show($entity, 1);

        $this->assertInstanceOf(DateTimeImmutable::class, $form->getUploadedDate());
    }

    public function testShowWithNullProductId(): void
    {
        $entity = new ProductImage();

        $form = ProductImageForm::show($entity, null);

        $this->assertNull($form->getProductId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new ProductImage();

        $this->assertNotSame(
            ProductImageForm::show($entity, 1),
            ProductImageForm::show($entity, 1)
        );
    }
}
