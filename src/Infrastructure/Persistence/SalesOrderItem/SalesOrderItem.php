<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SalesOrderItem;

use App\Infrastructure\Persistence\{
    TaxRate\TaxRate, Product\Product, SalesOrder\SalesOrder,
    Task\Task, Trait\RequireId};
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;
use App\Infrastructure\Persistence\SalesOrderItem\Trait\SalesOrderItemTrait1;
use App\Infrastructure\Persistence\SalesOrderItem\Trait\SalesOrderItemTrait2;
use App\Infrastructure\Persistence\SalesOrderItem\Trait\SalesOrderItemTrait3;

#[Entity(repository: SOIR::class)]
class SalesOrderItem
{
    use RequireId;
    use SalesOrderItemTrait1;
    use SalesOrderItemTrait2;
    use SalesOrderItemTrait3;
     
    #[Column(type: 'date', nullable: false)]
    private mixed $date_added;

    #[BelongsTo(
        target: SalesOrder::class,
        nullable: false,
        fkAction: 'NO ACTION'
    )]
    private ?SalesOrder $sales_order = null;

    #[BelongsTo(
        target: TaxRate::class,
        nullable: false,
        fkAction: 'NO ACTION'
    )]
    private ?TaxRate $tax_rate = null;

    #[BelongsTo(
        target: Product::class,
        nullable: true,
        fkAction: 'NO ACTION'
    )]
    private ?Product $product = null;

    #[BelongsTo(
        target: Task::class,
        nullable: true,
        fkAction: 'NO ACTION'
    )]
    private ?Task $task = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $peppol_po_itemid = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $peppol_po_lineid = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $description = '',
        #[Column(type: 'decimal(20,2)', nullable: false, default: 1.00)]
        private ?float $quantity = 1.00,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $price = 0.00,
        #[Column(type: 'decimal(20,2)', nullable: true, default: 0.00)]
        private ?float $discount_amount = 0.00,
        #[Column(type: 'integer(2)', nullable: true, default: 0)]
        private ?int $order = null,
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $product_unit = '',
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $sales_order_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $quote_item_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $tax_rate_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $product_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $task_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $product_unit_id = null,
    ) {
        $this->date_added = new DateTimeImmutable();
    }
}
