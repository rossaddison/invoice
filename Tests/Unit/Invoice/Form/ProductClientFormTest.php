<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\ProductClient\ProductClient;
use App\Invoice\ProductClient\ProductClientForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ProductClientFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new ProductClientForm();

        $this->assertNull($form->getProductId());
        $this->assertNull($form->getClientId());
        $this->assertSame('', $form->getCreatedAt());
        $this->assertSame('', $form->getUpdatedAt());
        $this->assertSame('', $form->getNewClientName());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ProductClientForm())->getFormName());
    }

    public function testShowUsesPassedIds(): void
    {
        // show() takes ?int $product_id and ?int $client_id as 2nd/3rd params
        $entity = new ProductClient();

        $form = ProductClientForm::show($entity, 5, 3);

        $this->assertSame(5, $form->getProductId());
        $this->assertSame(3, $form->getClientId());
    }

    public function testShowNullIdsFallBackToZero(): void
    {
        // ?int $product_id ?? 0 and ?int $client_id ?? 0
        $entity = new ProductClient();

        $form = ProductClientForm::show($entity, null, null);

        $this->assertSame(0, $form->getProductId());
        $this->assertSame(0, $form->getClientId());
    }

    public function testShowCreatedAtIsDateTimeImmutable(): void
    {
        // ProductClient constructor initializes created_at and updated_at to DateTimeImmutable
        $entity = new ProductClient();

        $form = ProductClientForm::show($entity, 1, 1);

        $this->assertInstanceOf(DateTimeImmutable::class, $form->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getUpdatedAt());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new ProductClient();

        $this->assertNotSame(
            ProductClientForm::show($entity, 1, 1),
            ProductClientForm::show($entity, 1, 1)
        );
    }
}
