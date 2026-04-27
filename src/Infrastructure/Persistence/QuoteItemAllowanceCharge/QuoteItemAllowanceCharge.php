<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\QuoteItemAllowanceCharge;

use App\Infrastructure\Persistence\{Quote\Quote, QuoteItem\QuoteItem,
    AllowanceCharge\AllowanceCharge, Trait\RequireId};
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: ACQIR::class)]
class QuoteItemAllowanceCharge
{
    use RequireId;
    
    #[BelongsTo(target: AllowanceCharge::class, nullable: false, fkAction: 'NO ACTION')]
    private ?AllowanceCharge $allowance_charge = null;

    #[BelongsTo(target: QuoteItem::class, nullable: false, fkAction: 'NO ACTION')]
    private ?QuoteItem $quote_item = null;

    #[BelongsTo(target: Quote::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Quote $quote = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)',
        nullable: false)]
        private ?int $quote_id = null,
        #[Column(type: 'integer(11)',
        nullable: false)]
        private ?int $quote_item_id = null,
        #[Column(type: 'integer(11)',
        nullable: false)]
        private ?int $allowance_charge_id = null,
        #[Column(type: 'decimal(20,2)',
        nullable: false, default: 0.00)]
        private ?float $amount = null,
        #[Column(type: 'decimal(20,2)',
        nullable: false, default: 0.00)]
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
    
    public function reqId(): int
    {
        return $this->requireId($this->id, 'QuoteItemAllowanceCharge');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqQuoteId(): int
    {
        return $this->requireId($this->quote_id, 'Quote');
    }

    public function setQuoteId(int $quote_id): void
    {
        $this->quote_id = $quote_id;
    }

    public function reqQuoteItemId(): int
    {
        return $this->requireId($this->quote_item_id, 'Quote Item');
    }

    public function setQuoteItemId(int $quote_item_id): void
    {
        $this->quote_item_id = $quote_item_id;
    }

    public function reqAllowanceChargeId(): int
    {
        return $this->requireId($this->allowance_charge_id, 'AllowanceCharge');
    }

    public function setAllowanceChargeId(int $allowance_charge_id): void
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
