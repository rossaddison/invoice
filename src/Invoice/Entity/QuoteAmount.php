<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use App\Invoice\QuoteAmount\QuoteAmountRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: QuoteAmountRepository::class)]
class QuoteAmount
{
    #[BelongsTo(target: Quote::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Quote $quote = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,

        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $quote_id = null,

        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $item_subtotal = 0.00,

        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $item_tax_total = 0.00,

        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private float $packhandleship_total = 0.00,

        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private float $packhandleship_tax = 0.00,

        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $tax_total = 0.00,

        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $total = 0.00)
    {
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getQuoteId(): string
    {
        return (string) $this->quote_id;
    }

    public function setQuoteId(int $quote_id): void
    {
        $this->quote_id = $quote_id;
    }

    public function getItemSubtotal(): ?float
    {
        return $this->item_subtotal;
    }

    public function setItemSubtotal(float $item_subtotal): void
    {
        $this->item_subtotal = $item_subtotal;
    }

    public function getItemTaxTotal(): ?float
    {
        return $this->item_tax_total;
    }

    public function setItemTaxTotal(float $item_tax_total): void
    {
        $this->item_tax_total = $item_tax_total;
    }

    // Holds QuoteAllowanceCharge accumulative totals
    public function getPackhandleshipTotal(): float
    {
        return $this->packhandleship_total;
    }

    public function setPackhandleshipTotal(float $packhandleship_total): void
    {
        $this->packhandleship_total = $packhandleship_total;
    }

    // Holds QuoteAllowanceCharge accumulative tax totals
    // See src/Invoice/Helpers/NumberHelper function calculateQuote
    // which recalculates this total when the quote is redirected
    // to the view after adding/deleting/editing an qac
    public function getPackhandleshipTax(): float
    {
        return $this->packhandleship_tax;
    }

    public function setPackhandleshipTax(float $packhandleship_tax): void
    {
        $this->packhandleship_tax = $packhandleship_tax;
    }

    public function getTaxTotal(): ?float
    {
        return $this->tax_total;
    }

    public function setTaxTotal(float $tax_total): void
    {
        $this->tax_total = $tax_total;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }
}
