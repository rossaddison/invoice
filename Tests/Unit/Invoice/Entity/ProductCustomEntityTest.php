<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductCustom\ProductCustom;
use App\Infrastructure\Persistence\CustomField\CustomField;
use PHPUnit\Framework\TestCase;

class ProductCustomEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $productCustom = new ProductCustom();
        $this->assertFalse($productCustom->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $productCustom = new ProductCustom();
        $this->expectException(\LogicException::class);
        $productCustom->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $productCustom = new ProductCustom();
        $productCustom->setId(12);
        $this->assertTrue($productCustom->isPersisted());
        $this->assertSame(12, $productCustom->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $productCustom = new ProductCustom();
        $productCustom->setId(1);
        $this->assertIsInt($productCustom->reqId());
    }

    public function testConstructorWithDefaults(): void
    {
        $productCustom = new ProductCustom();
        $this->assertSame('', $productCustom->getProductId());
        $this->assertSame('', $productCustom->getCustomFieldId());
        $this->assertSame('', $productCustom->getValue());
        $this->assertNull($productCustom->getProduct());
        $this->assertNull($productCustom->getCustomField());
    }

    public function testConstructorWithAllParameters(): void
    {
        $productCustom = new ProductCustom(
            product_id: 10,
            custom_field_id: 3,
            value: 'red',
        );

        $this->assertSame('10', $productCustom->getProductId());
        $this->assertSame('3', $productCustom->getCustomFieldId());
        $this->assertSame('red', $productCustom->getValue());
    }

    public function testProductIdSetterAndGetter(): void
    {
        $productCustom = new ProductCustom();
        $productCustom->setProductId(50);
        $this->assertSame('50', $productCustom->getProductId());
    }

    public function testProductIdIsReturnedAsString(): void
    {
        $productCustom = new ProductCustom(product_id: 7);
        $this->assertIsString($productCustom->getProductId());
        $this->assertSame('7', $productCustom->getProductId());
    }

    public function testCustomFieldIdSetterAndGetter(): void
    {
        $productCustom = new ProductCustom();
        $productCustom->setCustomFieldId(8);
        $this->assertSame('8', $productCustom->getCustomFieldId());
    }

    public function testCustomFieldIdIsReturnedAsString(): void
    {
        $productCustom = new ProductCustom(custom_field_id: 15);
        $this->assertIsString($productCustom->getCustomFieldId());
        $this->assertSame('15', $productCustom->getCustomFieldId());
    }

    public function testValueSetterAndGetter(): void
    {
        $productCustom = new ProductCustom();
        $productCustom->setValue('blue');
        $this->assertSame('blue', $productCustom->getValue());
    }

    public function testEmptyValue(): void
    {
        $productCustom = new ProductCustom();
        $productCustom->setValue('');
        $this->assertSame('', $productCustom->getValue());
    }

    public function testProductRelationSetterAndGetter(): void
    {
        $productCustom = new ProductCustom();
        $product = $this->createMock(Product::class);

        $productCustom->setProduct($product);
        $this->assertSame($product, $productCustom->getProduct());

        $productCustom->setProduct(null);
        $this->assertNull($productCustom->getProduct());
    }

    public function testCustomFieldRelationSetterAndGetter(): void
    {
        $productCustom = new ProductCustom();
        $customField = $this->createMock(CustomField::class);

        $productCustom->setCustomField($customField);
        $this->assertSame($customField, $productCustom->getCustomField());

        $productCustom->setCustomField(null);
        $this->assertNull($productCustom->getCustomField());
    }

    public function testLongValue(): void
    {
        $productCustom = new ProductCustom();
        $long = str_repeat('x', 2000);
        $productCustom->setValue($long);
        $this->assertSame($long, $productCustom->getValue());
    }
}
