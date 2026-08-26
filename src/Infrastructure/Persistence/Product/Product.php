<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product;

use App\Infrastructure\Persistence\{Client\Client, Family\Family,
    ProductClient\ProductClient, TaxRate\TaxRate, Unit\Unit, Trait\RequireId};
use App\Invoice\Enum\ProductType;
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
        #[Column(type: 'string(20)', nullable: false, default: 'product')]
        private string $product_type = ProductType::Product->value,
        #[Column(type: 'decimal(20,2)', nullable: true)]
        private ?float $product_price = 0.00,
        #[Column(type: 'decimal(20,2)', nullable: true)]
        private ?float $purchase_price = 0.00,
        // The public /shop storefront's own sale price — deliberately a
        // separate column from $product_price, not a discount/markup
        // percentage applied to it: staff/B2B invoicing (quotes, sales
        // orders, invoices) always uses $product_price ("wholesale"),
        // the webshop always uses this ("retail") when set. Null/0.00
        // (unset) falls back to $product_price in App\Webshop\Catalog\
        // CatalogQueryService::toListing() — so flagging a product
        // available_on_webshop without also filling this in doesn't
        // silently show a £0.00 listing.
        #[Column(type: 'decimal(20,2)', nullable: true)]
        private ?float $retail_price = 0.00,
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
        // Gates the public /shop catalog (App\Webshop\Catalog\
        // CatalogQueryService::listAll()/find()) — off by default so an
        // existing B2B-only product never appears there just because it
        // has a price. Has no effect on the staff invoice/quote/sales-
        // order product picker, which still shows every priced product
        // regardless (App\Invoice\Product\ProductRepository::
        // findAllPreloadedWithPrice()).
        #[Column(type: 'boolean', nullable: false, default: false)]
        private bool $available_on_webshop = false,
        // Trade (B2B/wholesale) ordering terms surfaced to webshop retail
        // customers via a "Trade Pricing" button on the storefront product
        // page (App\Webshop\Catalog\CatalogQueryService::toListing(),
        // resources/views/shop/catalog/view.php) — null means this product
        // has no trade terms configured, so the button doesn't appear at
        // all. The trade price shown alongside these is always
        // $product_price ("wholesale"), never $retail_price.
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $trade_min_order_qty = null,
        #[Column(type: 'decimal(20,2)', nullable: true)]
        private ?float $trade_min_order_spend = null,
        // Whether this product's stock is tracked at all via
        // App\Infrastructure\Persistence\StockMovement\StockMovement.
        // Defaults true for physical products; a Service-type product (see
        // App\Invoice\Enum\ProductType) should be created with this false —
        // that's a form-layer decision, not enforced here.
        #[Column(type: 'boolean', nullable: false, default: true)]
        private bool $track_stock = true,
        // Denormalized cache of StockMovementRepository::currentBalance()
        // for this product — kept in sync by applying each StockMovement's
        // quantity_delta here in the same transaction as it's written, the
        // same "cached total, ledger detail rows are the source of truth"
        // relationship InvAmount already has to InvItemAmount. Meaningless
        // (and unmaintained) while track_stock is false.
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0)]
        private float $stock_quantity = 0.00,
        // A reserved buffer: physically in stock but never shown or sold to
        // the public. Null means no buffer configured — the webshop then
        // sees the full stock_quantity as available. See
        // ProductTrait4::availableStock() for the single place this is
        // actually applied (customer-facing "stock left" = stock_quantity
        // minus this, floored at 0) and App\Invoice\StockMovement\
        // LowStockNotifier for the staff-facing side: a Telegram alert
        // fires the moment stock_quantity crosses at/below this value,
        // early enough that the buffer itself is still there to fulfil
        // orders from while restocking. Meaningless while track_stock is
        // false, same as stock_quantity itself.
        #[Column(type: 'decimal(20,2)', nullable: true)]
        private ?float $reorder_threshold = null,
    ) {
        $this->client_associations = new ArrayCollection();
    }
}
