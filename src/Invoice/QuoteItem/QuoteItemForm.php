<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItem;

use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\GreaterThan;

final class QuoteItemForm extends FormModel
{
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
    private ?int $quote_id = null;

    public static function show(QuoteItem $quoteItem, int $quoteId): self
    {
        $form = new self();
        $form->tax_rate_id = (string) $quoteItem->reqTaxRateId();
        $form->product_id = null !== ($product = $quoteItem->getProduct()) ? (string) $product->reqId() : null;
        $form->task_id = null !== ($task = $quoteItem->getTask()) ? (string) $task->reqId() : null;
        $form->name = $quoteItem->getName();
        $form->description = $quoteItem->getDescription();
        $form->quantity = $quoteItem->getQuantity();
        $form->price = $quoteItem->getPrice();
        $form->discount_amount = $quoteItem->getDiscountAmount();
        $form->order = $quoteItem->getOrder();
        $form->product_unit = $quoteItem->getProductUnit();
        $form->product_unit_id = $quoteItem->getProductUnitId();
        $form->quote_id = $quoteId;
        return $form;
    }
    
    public function getQuoteId(): ?int
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
