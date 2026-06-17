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
        $this->assertSame(0.00, $form->product_price);
        $this->assertSame(1.00, $form->product_price_base_quantity);
        $this->assertSame(0.00, $form->purchase_price);
        $this->assertNull($form->family_id);
        $this->assertNull($form->tax_rate_id);
        $this->assertNull($form->unit_id);
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

        $this->assertSame('SKU-001', $form->product_sku);
        $this->assertSame('Widget Pro', $form->product_name);
        $this->assertSame('A professional widget', $form->product_description);
        $this->assertSame(49.99, $form->product_price);
        $this->assertSame(1.00, $form->product_price_base_quantity);
        $this->assertSame(25.00, $form->purchase_price);
        $this->assertSame('Acme Wholesale', $form->provider_name);
        $this->assertSame(1, $form->tax_rate_id);
        $this->assertSame(2, $form->unit_id);
        $this->assertSame('GB', $form->product_country_of_origin_code);
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

        $this->assertSame('0088', $form->product_sii_schemeid);
        $this->assertSame('4006381333931', $form->product_sii_id);
        $this->assertSame('STI', $form->product_icc_listid);
        $this->assertSame('Colour', $form->product_additional_item_property_name);
        $this->assertSame('Blue', $form->product_additional_item_property_value);
        $this->assertSame(5, $form->unit_peppol_id);
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
