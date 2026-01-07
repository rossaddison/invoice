<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository::class)]
class QuoteItemAllowanceCharge
{
    #[BelongsTo(target: AllowanceCharge::class, nullable: false, fkAction: 'NO ACTION')]
    private ?AllowanceCharge $allowance_charge = null;

    #[BelongsTo(target: QuoteItem::class, nullable: false, fkAction: 'NO ACTION')]
    private ?QuoteItem $quote_item = null;

    #[BelongsTo(target: Quote::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Quote $quote = null;

    public function __construct(#[Column(type: 'primary')]
        private ?int $id = null, #[Column(type: 'integer(11)', nullable: false)]
        private ?int $quote_id = null, #[Column(type: 'integer(11)', nullable: false)]
        private ?int $quote_item_id = null, #[Column(type: 'integer(11)', nullable: false)]
        private ?int $allowance_charge_id = null, #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $amount = null, #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $vat_or_tax = null)
    {
    }

    public function getAllowanceCharge(): ?AllowanceCharge
    {
        return $this->allowance_charge;
    }

    public function setAllowanceCharge(?AllowanceCharge $allowance_charge): void
    {
        $this->allowance_charge = $allowance_charge;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function getQuoteItem(): ?QuoteItem
    {
        return $this->quote_item;
    }

    public function setQuoteItem(?QuoteItem $quote_item): void
    {
        $this->quote_item = $quote_item;
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

    public function getQuote_item_id(): string
    {
        return (string) $this->quote_item_id;
    }

    public function setQuote_item_id(int $quote_item_id): void
    {
        $this->quote_item_id = $quote_item_id;
    }

    public function getAllowance_charge_id(): string
    {
        return (string) $this->allowance_charge_id;
    }

    public function setAllowance_charge_id(int $allowance_charge_id): void
    {
        $this->allowance_charge_id = $allowance_charge_id;
    }

    public function getAmount(): string
    {
        return (string) $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getVatOrTax(): string
    {
        return (string) $this->vat_or_tax;
    }

    public function setVatOrTax(float $vatOrTax): void
    {
        $this->vat_or_tax = $vatOrTax;
    }
}
