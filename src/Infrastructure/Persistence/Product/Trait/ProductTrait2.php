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
trait ProductTrait2
{

    public function getProductIccListid(): ?string
    {
        return $this->product_icc_listid;
    }

    public function setProductIccListid(?string $product_icc_listid): void
    {
        $this->product_icc_listid = $product_icc_listid;
    }

    public function getProductIccListversionid(): ?string
    {
        return $this->product_icc_listversionid;
    }

    public function setProductIccListversionid(
        ?string $product_icc_listversionid
    ): void {
        $this->product_icc_listversionid = $product_icc_listversionid;
    }

    public function getProductIccId(): ?string
    {
        return $this->product_icc_id;
    }

    public function setProductIccId(?string $product_icc_id): void
    {
        $this->product_icc_id = $product_icc_id;
    }

    public function setProductCountryOfOriginCode(
        ?string $product_country_of_origin_code
    ): void {
        $this->product_country_of_origin_code =
            $product_country_of_origin_code;
    }

    public function getProductCountryOfOriginCode(): ?string
    {
        return $this->product_country_of_origin_code;
    }

    public function getProductName(): ?string
    {
        return $this->product_name;
    }

    public function setProductName(string $product_name): void
    {
        $this->product_name = $product_name;
    }

    public function getProductDescription(): ?string
    {
        return $this->product_description;
    }

    public function setProductDescription(?string $product_description): void
    {
        $this->product_description = $product_description;
    }

    public function getProductPrice(): ?float
    {
        return $this->product_price;
    }

    public function setProductPrice(float $product_price): void
    {
        $this->product_price = $product_price;
    }

    public function getPurchasePrice(): ?float
    {
        return $this->purchase_price;
    }

    public function setPurchasePrice(float $purchase_price): void
    {
        $this->purchase_price = $purchase_price;
    }
}
