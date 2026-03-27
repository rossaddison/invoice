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
        $this->tax_rate_id = $quoteItem->getTaxRateId();
        $this->product_id = $quoteItem->getProductId();
        $this->task_id = $quoteItem->getTaskId();
        $this->name = $quoteItem->getName();
        $this->description = $quoteItem->getDescription();
        $this->quantity = $quoteItem->getQuantity();
        $this->price = $quoteItem->getPrice();
        $this->discount_amount = $quoteItem->getDiscountAmount();
        $this->order = $quoteItem->getOrder();
        $this->product_unit = $quoteItem->getProductUnit();
        $this->product_unit_id = (int) $quoteItem->getProductUnitId();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getQuoteId(): ?string
    {
        return $this->quote_id;
    }

    public function getTaxRateId(): ?string
    {
        return $this->tax_rate_id;
    }

    public function getProductId(): ?string
    {
        return $this->product_id;
    }

    public function getTaskId(): ?string
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

    public function getDiscountAmount(): ?float
    {
        return $this->discount_amount;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function getProductUnit(): ?string
    {
        return $this->product_unit;
    }

    public function getProductUnitId(): ?int
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
