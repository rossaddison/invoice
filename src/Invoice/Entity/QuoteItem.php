<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\QuoteItem\QuoteItemRepository::class)]
class QuoteItem
{
    #[Column(type: 'date', nullable: false)]
    private mixed $date_added;

    #[BelongsTo(target: Quote::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Quote $quote = null;

    #[BelongsTo(target: TaxRate::class, nullable: false, fkAction: 'NO ACTION')]
    private ?TaxRate $tax_rate = null;

    // Task relation - nullable because quote items can be either products or tasks
    #[BelongsTo(target: Product::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Product $product = null;

    // Task relation - nullable because quote items can be either products or tasks
    #[BelongsTo(target: Task::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Task $task = null;

    public function __construct(
        #[Column(type: 'primary')]
        public ?int $id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $description = '',
        #[Column(type: 'decimal(20,2)', nullable: false, default: 1.00)]
        private ?float $quantity = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $price = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $discount_amount = null,
        #[Column(type: 'integer(2)', nullable: false, default: 0)]
        private ?int $order = null,
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $product_unit = '',
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $quote_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $tax_rate_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $product_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $product_unit_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $task_id = null,
    ) {
        $this->date_added = new DateTimeImmutable();
    }

    //relation $tax_rate
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

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    //set relation $product
    public function setQuote(?Quote $quote): void
    {
        $this->quote = $quote;
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

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getQuote_id(): string
    {
        return (string) $this->quote_id;
    }

    public function setQuote_id(int $quote_id): void
    {
        $this->quote_id = $quote_id;
    }

    public function getTax_rate_id(): string
    {
        return (string) $this->tax_rate_id;
    }

    public function setTax_rate_id(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    public function getProduct_id(): string
    {
        return (string) $this->product_id;
    }

    public function setProduct_id(int $product_id): void
    {
        $this->product_id = $product_id;
    }

    public function getDate_added(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->date_added */
        return $this->date_added;
    }

    public function setDate_added(DateTime $date_added): void
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

    public function getDiscount_amount(): ?float
    {
        return $this->discount_amount;
    }

    public function setDiscount_amount(float $discount_amount): void
    {
        $this->discount_amount = $discount_amount;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function getProduct_unit(): ?string
    {
        return $this->product_unit;
    }

    public function setProduct_unit(string $product_unit): void
    {
        $this->product_unit = $product_unit;
    }

    public function getProduct_unit_id(): string
    {
        return (string) $this->product_unit_id;
    }

    public function setProduct_unit_id(int $product_unit_id): void
    {
        $this->product_unit_id = $product_unit_id;
    }

    public function getTask_id(): string
    {
        return (string) $this->task_id;
    }

    public function setTask_id(int $task_id): void
    {
        $this->task_id = $task_id;
    }
}
