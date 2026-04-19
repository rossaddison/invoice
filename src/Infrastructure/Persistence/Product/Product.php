<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product;

use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\Product\ProductRepository as PR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Doctrine\Common\Collections\ArrayCollection;

#[Entity(repository: PR::class)]
class Product
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    #[BelongsTo(target: Family::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Family $family = null;

    #[BelongsTo(target: TaxRate::class, nullable: false, fkAction: 'NO ACTION')]
    private ?TaxRate $tax_rate = null;

    #[BelongsTo(target: Unit::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Unit $unit = null;

    /**
     * @var ArrayCollection<array-key, ProductClient>
     */
    #[HasMany(target: ProductClient::class)]
    private ArrayCollection $client_associations;

    public function __construct(
        #[Column(type: 'text', nullable: true)]
        private ?string $product_sku = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $product_sii_schemeid = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $product_sii_id = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $product_icc_listid = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $product_icc_listversionid = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $product_icc_id = '',
        #[Column(type: 'string(2)', nullable: true)]
        private ?string $product_country_of_origin_code = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $product_name = '',
        #[Column(type: 'longText', nullable: false)]
        private ?string $product_description = '',
        #[Column(type: 'decimal(20,2)', nullable: true)]
        private ?float $product_price = 0.00,
        #[Column(type: 'decimal(20,2)', nullable: true)]
        private ?float $purchase_price = 0.00,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 1)]
        private float $product_price_base_quantity = 1.00,
        #[Column(type: 'text', nullable: true)]
        private ?string $provider_name = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $product_additional_item_property_name = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $product_additional_item_property_value = '',
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $tax_rate_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $unit_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $unit_peppol_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $family_id = null,
    ) {
        $this->client_associations = new ArrayCollection();
    }

    /**
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException(
                'Product has no ID (not persisted yet)'
            );
        }
        return $this->id;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getProductId(): string
    {
        return (string) $this->id;
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

    public function getFamilyId(): string
    {
        return (string) $this->family_id;
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

    public function getTaxRateId(): string
    {
        return (string) $this->tax_rate_id;
    }

    public function setUnitId(int $unit_id): void
    {
        $this->unit_id = $unit_id;
    }

    public function getUnitId(): string
    {
        return (string) $this->unit_id;
    }

    public function setUnitPeppolId(int $unit_peppol_id): void
    {
        $this->unit_peppol_id = $unit_peppol_id;
    }

    public function getUnitPeppolId(): string
    {
        return (string) $this->unit_peppol_id;
    }
}
