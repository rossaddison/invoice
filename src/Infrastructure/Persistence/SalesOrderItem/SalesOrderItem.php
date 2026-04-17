<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SalesOrderItem;

use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\Entity\Product;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\Entity\Task;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;

#[Entity(repository: SOIR::class)]
class SalesOrderItem
{
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

    /**
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException(
                'SalesOrderItem has no ID (not persisted yet)'
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

    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    public function setTaxRate(?TaxRate $taxrate): void
    {
        $this->tax_rate = $taxrate;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): void
    {
        $this->task = $task;
    }

    public function getSalesOrder(): ?SalesOrder
    {
        return $this->sales_order;
    }

    public function setSalesOrder(?SalesOrder $sales_order): void
    {
        $this->sales_order = $sales_order;
    }

    public function getSalesOrderId(): ?int
    {
        return $this->sales_order_id;
    }

    public function setSalesOrderId(int $sales_order_id): void
    {
        $this->sales_order_id = $sales_order_id;
    }

    public function getQuoteItemId(): ?int
    {
        return $this->quote_item_id;
    }

    public function setQuoteItemId(int $quote_item_id): void
    {
        $this->quote_item_id = $quote_item_id;
    }

    public function getPeppolPoItemid(): ?string
    {
        return $this->peppol_po_itemid;
    }

    public function setPeppolPoItemid(string $peppol_po_itemid): void
    {
        $this->peppol_po_itemid = $peppol_po_itemid;
    }

    public function getPeppolPoLineid(): ?string
    {
        return $this->peppol_po_lineid;
    }

    public function setPeppolPoLineid(string $peppol_po_lineid): void
    {
        $this->peppol_po_lineid = $peppol_po_lineid;
    }

    public function getTaxRateId(): ?int
    {
        return $this->tax_rate_id;
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    public function getProductId(): ?int
    {
        return $this->product_id;
    }

    public function setProductId(int $product_id): void
    {
        $this->product_id = $product_id;
    }

    public function getTaskId(): ?int
    {
        return $this->task_id;
    }

    public function setTaskId(int $task_id): void
    {
        $this->task_id = $task_id;
    }

    public function getDateAdded(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->date_added */
        return $this->date_added;
    }

    public function setDateAdded(DateTimeImmutable $date_added): void
    {
        $this->date_added = $date_added;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getDiscountAmount(): ?float
    {
        return $this->discount_amount;
    }

    public function setDiscountAmount(float $discount_amount): void
    {
        $this->discount_amount = $discount_amount;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(?int $order): void
    {
        $this->order = $order;
    }

    public function getProductUnit(): ?string
    {
        return $this->product_unit;
    }

    public function setProductUnit(string $product_unit): void
    {
        $this->product_unit = $product_unit;
    }

    public function getProductUnitId(): ?int
    {
        return $this->product_unit_id;
    }

    public function setProductUnitId(int $product_unit_id): void
    {
        $this->product_unit_id = $product_unit_id;
    }
}
