<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product;

use App\Infrastructure\Persistence\{Client\Client, Family\Family,
    ProductClient\ProductClient, TaxRate\TaxRate, Unit\Unit, Trait\RequireId};
use App\Invoice\Product\ProductRepository as PR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table\Index;
use Doctrine\Common\Collections\ArrayCollection;
use App\Infrastructure\Persistence\Product\Trait\ProductTrait1;
use App\Infrastructure\Persistence\Product\Trait\ProductTrait2;
use App\Infrastructure\Persistence\Product\Trait\ProductTrait3;
use App\Infrastructure\Persistence\Product\Trait\ProductTrait4;

#[Entity(repository: PR::class)]
// Priority 1 — sort targets and filters (FK relations)
#[Index(columns: ['family_id'])]
#[Index(columns: ['tax_rate_id'])]
#[Index(columns: ['unit_id'])]
// Priority 2 — nullable FK
#[Index(columns: ['unit_peppol_id'])]
class Product
{
    use RequireId;
    use ProductTrait1;
    use ProductTrait2;
    use ProductTrait3;
    use ProductTrait4;
    
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
}
