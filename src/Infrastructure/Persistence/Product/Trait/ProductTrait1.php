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
trait ProductTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Product');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFamily(): ?Family
    {
        return $this->family;
    }

    public function setFamily(?Family $family): void
    {
        $this->family = $family;
    }

    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    public function setTaxrate(?TaxRate $taxrate): void
    {
        $this->tax_rate = $taxrate;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(?Unit $unit): void
    {
        $this->unit = $unit;
    }

    public function setFamilyId(int $family_id): void
    {
        $this->family_id = $family_id;
    }

    public function getProductSku(): ?string
    {
        return $this->product_sku;
    }

    public function setProductSku(?string $product_sku): void
    {
        $this->product_sku = $product_sku;
    }

    public function getProductSiiSchemeid(): ?string
    {
        return $this->product_sii_schemeid;
    }

    public function setProductSiiSchemeid(?string $product_sii_schemeid): void
    {
        $this->product_sii_schemeid = $product_sii_schemeid;
    }

    public function getProductSiiId(): ?string
    {
        return $this->product_sii_id;
    }

    public function setProductSiiId(?string $product_sii_id): void
    {
        $this->product_sii_id = $product_sii_id;
    }
}
