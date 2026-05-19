<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvItem;

use App\Infrastructure\Persistence\{
    Inv\Inv, Product\Product, Task\Task, TaxRate\TaxRate, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\InvItem\InvItemRepository::class)]
class InvItem
{
    use RequireId;
    
    #[Column(type: 'date', nullable: false)]
    private mixed $date_added;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date;

    #[BelongsTo(target: TaxRate::class, nullable: false, fkAction: 'NO ACTION')]
    private ?TaxRate $tax_rate = null;

    #[BelongsTo(target: Product::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Product $product = null;

    #[BelongsTo(target: Task::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Task $task = null;

    #[BelongsTo(target: Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;

    public function __construct(
        #[Column(type: 'primary')]
        public ?int $id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
        #[Column(type: 'longText', nullable: true)]
        private ?string $description = '',
        #[Column(type: 'decimal(10,2)', nullable: false, default: 1)]
        private ?float $quantity = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $price = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $discount_amount = null,
        #[Column(type: 'integer(2)', nullable: true, default: 0)]
        private ?int $order = null,
        #[Column(type: 'boolean', nullable: false)]
        private ?bool $is_recurring = false,
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $product_unit = '',
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $so_item_id = null,
        #[Column(type: 'integer(11)', nullable: false, default: 0)]
        private ?int $tax_rate_id = null,
        #[Column(type: 'integer(11)', nullable: true, default: null)]
        private ?int $product_id = null,
        #[Column(type: 'integer(11)', nullable: true, default: null)]
        private ?int $task_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $product_unit_id = null,
        #[Column(type: 'integer(2)', nullable: true, default: 0)]
        private ?int $belongs_to_vat_invoice = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $delivery_id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $peppol_po_itemid = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $peppol_po_lineid = '',
        #[Column(type: 'longText', nullable: true)]
        private ?string $note = null,
    ) {
        $this->date_added = new DateTimeImmutable();
        $this->date = new DateTimeImmutable();
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'InvItem');
    }
    
    public function hasIdentity(): bool
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

    //set relation $taxrate
    public function setTaxRate(?TaxRate $taxrate): void
    {
        $this->tax_rate = $taxrate;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    //set relation $product
    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    //set relation $task
    public function setTask(?Task $task): void
    {
        $this->task = $task;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function reqInvId(): int
    {
        return $this->requireId($this->inv_id, 'Inv');
    }

    public function setInvId(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    // not all inv_item's have a corresponding so_item so could be null
    public function getSoItemId(): ?int
    {
        return $this->so_item_id;
    }

    public function setSoItemId(int $so_item_id): void
    {
        $this->so_item_id = $so_item_id;
    }

    public function reqTaxRateId(): int
    {
        return $this->requireId($this->tax_rate_id, 'TaxRate');
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    public function getDateAdded(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->date_added */
        return $this->date_added;
    }

    public function setDateAdded(DateTime $date_added): void
    {
        $this->date_added = $date_added;
    }

    // product can be mutually excluded by task => possible null value
    public function getProductId(): ?int
    {
        return $this->product_id;
    }

    public function setProductId(int $product_id): void
    {
        $this->product_id = $product_id;
    }

    // task can be mutually excluded by product => possible null value
    public function getTaskId(): ?int
    {
        return $this->task_id;
    }

    public function setTaskId(int $task_id): void
    {
        $this->task_id = $task_id;
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

    // For Charges and Allowances see the extension table InvItemAllowanceCharges
    // which extends this entity by means of inv_item_id

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function getIsRecurring(): ?bool
    {
        return $this->is_recurring;
    }

    public function setIsRecurring(bool $is_recurring): void
    {
        $this->is_recurring = $is_recurring;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
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

    public function setBelongsToVatInvoice(int $belongs_to_vat_invoice): void
    {
        $this->belongs_to_vat_invoice = $belongs_to_vat_invoice;
    }

    public function getBelongsToVatInvoice(): string
    {
        return (string) $this->belongs_to_vat_invoice;
    }

    public function getDeliveryId(): ?int
    {
        return $this->delivery_id;
    }

    public function setDeliveryId(int $delivery_id): void
    {
        $this->delivery_id = $delivery_id;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
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
}
