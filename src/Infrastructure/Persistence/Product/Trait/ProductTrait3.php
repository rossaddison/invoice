<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\Product\ProductRepository as PR;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @method int requireId(?int $id, string $context)
 */
trait ProductTrait3
{

    public function getProductPriceBaseQuantity(): float
    {
        return $this->product_price_base_quantity;
    }

    public function setProductPriceBaseQuantity(
        float $product_price_base_quantity
    ): void {
        $this->product_price_base_quantity = $product_price_base_quantity;
    }

    public function getProviderName(): ?string
    {
        return $this->provider_name;
    }

    public function setProviderName(?string $provider_name): void
    {
        $this->provider_name = $provider_name;
    }

    public function getProductAdditionalItemPropertyName(): ?string
    {
        return $this->product_additional_item_property_name;
    }

    public function setProductAdditionalItemPropertyName(
        ?string $product_additional_item_property_name
    ): void {
        $this->product_additional_item_property_name =
            $product_additional_item_property_name;
    }

    public function getProductAdditionalItemPropertyValue(): ?string
    {
        return $this->product_additional_item_property_value;
    }

    public function setProductAdditionalItemPropertyValue(
        ?string $product_additional_item_property_value
    ): void {
        $this->product_additional_item_property_value =
            $product_additional_item_property_value;
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    public function reqTaxRateId(): int
    {
        return $this->requireId($this->tax_rate_id, 'TaxRate');
    }

    public function setUnitId(int $unit_id): void
    {
        $this->unit_id = $unit_id;
    }

    public function reqUnitId(): int
    {
        return $this->requireId($this->unit_id, 'Unit');
    }

    public function setUnitPeppolId(int $unit_peppol_id): void
    {
        $this->unit_peppol_id = $unit_peppol_id;
    }

    public function getUnitPeppolId(): ?int
    {
        return $this->unit_peppol_id;
    }

    // Step 1: Create an empty ArrayCollection
    public function setProductClients(): void
    {
        $this->client_associations = new ArrayCollection();
    }

    // Step 2: Add a productClient to this collection
    public function addProductClient(ProductClient $productClient): void
    {
        $this->client_associations[] = $productClient;
    }
}
