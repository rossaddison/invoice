<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Product\Product;
use App\Invoice\Product\ProductForm;
use PHPUnit\Framework\TestCase;

class ProductFormTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $form = new ProductForm();

        $this->assertSame('ProductForm', $form->getFormName());
        $this->assertSame(0.00, $form->getProductPrice());
        $this->assertSame(1.00, $form->getProductPriceBaseQuantity());
        $this->assertSame(0.00, $form->getPurchasePrice());
        $this->assertNull($form->getId());
        $this->assertNull($form->getTaxRateId());
        $this->assertNull($form->getUnitId());
    }

    public function testGetFormNameReturnsProductForm(): void
    {
        $this->assertSame('ProductForm', (new ProductForm())->getFormName());
    }

    public function testShowPopulatesFromMockedProduct(): void
    {
        /** @var Product&\PHPUnit\Framework\MockObject\Stub $product */
        $product = $this->createStub(Product::class);
        $product->method('getProductSku')->willReturn('SKU-001');
        $product->method('getProductName')->willReturn('Widget Pro');
        $product->method('getProductDescription')->willReturn('A professional widget');
        $product->method('getProductPrice')->willReturn(49.99);
        $product->method('getProductPriceBaseQuantity')->willReturn(1.00);
        $product->method('getPurchasePrice')->willReturn(25.00);
        $product->method('getProviderName')->willReturn('Acme Wholesale');
        $product->method('reqTaxRateId')->willReturn(1);
        $product->method('reqUnitId')->willReturn(2);
        $product->method('getUnitPeppolId')->willReturn(null);
        $product->method('reqId')->willReturn(3);
        $product->method('getProductSiiSchemeid')->willReturn(null);
        $product->method('getProductSiiId')->willReturn(null);
        $product->method('getProductIccListid')->willReturn(null);
        $product->method('getProductIccListversionid')->willReturn(null);
        $product->method('getProductIccId')->willReturn(null);
        $product->method('getProductCountryOfOriginCode')->willReturn('GB');
        $product->method('getProductAdditionalItemPropertyName')->willReturn(null);
        $product->method('getProductAdditionalItemPropertyValue')->willReturn(null);

        $form = ProductForm::show($product);

        $this->assertSame('SKU-001', $form->getProductSku());
        $this->assertSame('Widget Pro', $form->getProductName());
        $this->assertSame('A professional widget', $form->getProductDescription());
        $this->assertSame(49.99, $form->getProductPrice());
        $this->assertSame(1.00, $form->getProductPriceBaseQuantity());
        $this->assertSame(25.00, $form->getPurchasePrice());
        $this->assertSame('Acme Wholesale', $form->getProviderName());
        $this->assertSame(1, $form->getTaxRateId());
        $this->assertSame(2, $form->getUnitId());
        $this->assertSame('GB', $form->getProductCountryOfOriginCode());
    }

    public function testShowWithPeppolFields(): void
    {
        /** @var Product&\PHPUnit\Framework\MockObject\Stub $product */
        $product = $this->createStub(Product::class);
        $product->method('getProductSku')->willReturn('SKU-002');
        $product->method('getProductName')->willReturn('Peppol Item');
        $product->method('getProductDescription')->willReturn('');
        $product->method('getProductPrice')->willReturn(99.00);
        $product->method('getProductPriceBaseQuantity')->willReturn(1.00);
        $product->method('getPurchasePrice')->willReturn(50.00);
        $product->method('getProviderName')->willReturn(null);
        $product->method('reqTaxRateId')->willReturn(2);
        $product->method('reqUnitId')->willReturn(1);
        $product->method('getUnitPeppolId')->willReturn(5);
        $product->method('reqId')->willReturn(10);
        $product->method('getProductSiiSchemeid')->willReturn('0088');
        $product->method('getProductSiiId')->willReturn('4006381333931');
        $product->method('getProductIccListid')->willReturn('STI');
        $product->method('getProductIccListversionid')->willReturn('2013');
        $product->method('getProductIccId')->willReturn('1234567890123');
        $product->method('getProductCountryOfOriginCode')->willReturn(null);
        $product->method('getProductAdditionalItemPropertyName')->willReturn('Colour');
        $product->method('getProductAdditionalItemPropertyValue')->willReturn('Blue');

        $form = ProductForm::show($product);

        $this->assertSame('0088', $form->getProductSiiSchemeid());
        $this->assertSame('4006381333931', $form->getProductSiiId());
        $this->assertSame('STI', $form->getProductIccListid());
        $this->assertSame('Colour', $form->getProductAdditionalItemPropertyName());
        $this->assertSame('Blue', $form->getProductAdditionalItemPropertyValue());
        $this->assertSame(5, $form->getUnitPeppolId());
    }

    public function testShowReturnsNewInstance(): void
    {
        /** @var Product&\PHPUnit\Framework\MockObject\Stub $product */
        $product = $this->createStub(Product::class);
        $product->method('getProductSku')->willReturn('X');
        $product->method('getProductName')->willReturn('X');
        $product->method('getProductDescription')->willReturn('');
        $product->method('getProductPrice')->willReturn(0.00);
        $product->method('getProductPriceBaseQuantity')->willReturn(1.00);
        $product->method('getPurchasePrice')->willReturn(0.00);
        $product->method('getProviderName')->willReturn(null);
        $product->method('reqTaxRateId')->willReturn(1);
        $product->method('reqUnitId')->willReturn(1);
        $product->method('getUnitPeppolId')->willReturn(null);
        $product->method('reqId')->willReturn(1);
        $product->method('getProductSiiSchemeid')->willReturn(null);
        $product->method('getProductSiiId')->willReturn(null);
        $product->method('getProductIccListid')->willReturn(null);
        $product->method('getProductIccListversionid')->willReturn(null);
        $product->method('getProductIccId')->willReturn(null);
        $product->method('getProductCountryOfOriginCode')->willReturn(null);
        $product->method('getProductAdditionalItemPropertyName')->willReturn(null);
        $product->method('getProductAdditionalItemPropertyValue')->willReturn(null);

        $this->assertNotSame(ProductForm::show($product), ProductForm::show($product));
    }
}
