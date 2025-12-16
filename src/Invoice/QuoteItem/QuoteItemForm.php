<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItem;

use App\Invoice\Entity\QuoteItem;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\GreaterThan;

final class QuoteItemForm extends FormModel
{
    private ?string $id = '';

    #[Required]
    private ?string $tax_rate_id = '';

    private ?string $product_id = '';

    private ?string $task_id = '';
    private ?string $name = '';

    private ?string $description = '';

    #[GreaterThan(0.00)]
    private ?float $quantity = null;

    private ?float $price = null;

    private ?float $discount_amount = null;

    #[Required]
    private ?int $order = null;
    private ?string $product_unit = '';
    private ?int $product_unit_id = null;

    public function __construct(QuoteItem $quoteItem, private readonly ?string $quote_id)
    {
        $this->id = $quoteItem->getId();
        $this->tax_rate_id = $quoteItem->getTax_rate_id();
        $this->product_id = $quoteItem->getProduct_id();
        $this->task_id = $quoteItem->getTask_id();
        $this->name = $quoteItem->getName();
        $this->description = $quoteItem->getDescription();
        $this->quantity = $quoteItem->getQuantity();
        $this->price = $quoteItem->getPrice();
        $this->discount_amount = $quoteItem->getDiscount_amount();
        $this->order = $quoteItem->getOrder();
        $this->product_unit = $quoteItem->getProduct_unit();
        $this->product_unit_id = (int) $quoteItem->getProduct_unit_id();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getQuote_id(): ?string
    {
        return $this->quote_id;
    }

    public function getTax_rate_id(): ?string
    {
        return $this->tax_rate_id;
    }

    public function getProduct_id(): ?string
    {
        return $this->product_id;
    }

    public function getTask_id(): ?string
    {
        return $this->task_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getDiscount_amount(): ?float
    {
        return $this->discount_amount;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function getProduct_unit(): ?string
    {
        return $this->product_unit;
    }

    public function getProduct_unit_id(): ?int
    {
        return $this->product_unit_id;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
