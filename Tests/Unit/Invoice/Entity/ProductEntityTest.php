<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Product\Product;
use PHPUnit\Framework\TestCase;

class ProductEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $p = new Product();
        $this->assertFalse($p->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $p = new Product();
        $this->expectException(\LogicException::class);
        $p->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $p = new Product();
        $p->setId(1);
        $this->assertTrue($p->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $p = new Product();
        $p->setId(11);
        $this->assertSame(11, $p->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $p = new Product();
        $this->assertSame('', $p->getProductSku());
        $this->assertSame('', $p->getProductName());
        $this->assertSame('', $p->getProductDescription());
        $this->assertSame(0.00, $p->getProductPrice());
        $this->assertSame(0.00, $p->getPurchasePrice());
        $this->assertSame(1.00, $p->getProductPriceBaseQuantity());
        $this->assertSame('', $p->getProviderName());
        $this->assertNull($p->getFamily());
        $this->assertNull($p->getTaxRate());
        $this->assertNull($p->getUnit());
    }

    public function testSetAndGetProductName(): void
    {
        $p = new Product();
        $p->setProductName('Widget Pro');
        $this->assertSame('Widget Pro', $p->getProductName());
    }

    public function testSetAndGetProductSku(): void
    {
        $p = new Product();
        $p->setProductSku('SKU-001');
        $this->assertSame('SKU-001', $p->getProductSku());
    }

    public function testSetAndGetProductDescription(): void
    {
        $p = new Product();
        $p->setProductDescription('A high-quality widget.');
        $this->assertSame('A high-quality widget.', $p->getProductDescription());
    }

    public function testSetAndGetProductPrice(): void
    {
        $p = new Product();
        $p->setProductPrice(49.99);
        $this->assertSame(49.99, $p->getProductPrice());
    }

    public function testSetAndGetPurchasePrice(): void
    {
        $p = new Product();
        $p->setPurchasePrice(25.00);
        $this->assertSame(25.00, $p->getPurchasePrice());
    }

    public function testSetAndGetProductPriceBaseQuantity(): void
    {
        $p = new Product();
        $p->setProductPriceBaseQuantity(10.0);
        $this->assertSame(10.0, $p->getProductPriceBaseQuantity());
    }

    public function testSetAndGetProviderName(): void
    {
        $p = new Product();
        $p->setProviderName('Acme Corp');
        $this->assertSame('Acme Corp', $p->getProviderName());
    }

    public function testSetAndGetSiiFields(): void
    {
        $p = new Product();
        $p->setProductSiiSchemeid('0088');
        $p->setProductSiiId('5790000435951');
        $this->assertSame('0088', $p->getProductSiiSchemeid());
        $this->assertSame('5790000435951', $p->getProductSiiId());
    }

    public function testSetAndGetIccFields(): void
    {
        $p = new Product();
        $p->setProductIccListid('UNCL7143');
        $p->setProductIccListversionid('2013');
        $p->setProductIccId('AA');
        $this->assertSame('UNCL7143', $p->getProductIccListid());
        $this->assertSame('2013', $p->getProductIccListversionid());
        $this->assertSame('AA', $p->getProductIccId());
    }

    public function testSetAndGetCountryOfOriginCode(): void
    {
        $p = new Product();
        $p->setProductCountryOfOriginCode('GB');
        $this->assertSame('GB', $p->getProductCountryOfOriginCode());
    }

    public function testSetAndGetAdditionalItemProperty(): void
    {
        $p = new Product();
        $p->setProductAdditionalItemPropertyName('Colour');
        $p->setProductAdditionalItemPropertyValue('Blue');
        $this->assertSame('Colour', $p->getProductAdditionalItemPropertyName());
        $this->assertSame('Blue', $p->getProductAdditionalItemPropertyValue());
    }

    public function testReqTaxRateIdThrowsWhenNull(): void
    {
        $p = new Product();
        $this->expectException(\LogicException::class);
        $p->reqTaxRateId();
    }

    public function testSetAndReqTaxRateId(): void
    {
        $p = new Product();
        $p->setTaxRateId(2);
        $this->assertSame(2, $p->reqTaxRateId());
    }

    public function testReqUnitIdThrowsWhenNull(): void
    {
        $p = new Product();
        $this->expectException(\LogicException::class);
        $p->reqUnitId();
    }

    public function testSetAndReqUnitId(): void
    {
        $p = new Product();
        $p->setUnitId(3);
        $this->assertSame(3, $p->reqUnitId());
    }

    public function testSetAndGetUnitPeppolId(): void
    {
        $p = new Product();
        $this->assertNull($p->getUnitPeppolId());
        $p->setUnitPeppolId(5);
        $this->assertSame(5, $p->getUnitPeppolId());
    }
}
