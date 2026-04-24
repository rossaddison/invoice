<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\QuoteAllowanceCharge;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Invoice\Entity\Quote;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository as QACR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: QACR::class)]
class QuoteAllowanceCharge
{
    #[BelongsTo(
        target: AllowanceCharge::class,
        nullable: false,
        fkAction: 'NO ACTION'
    )]
    private ?AllowanceCharge $allowance_charge = null;

    #[BelongsTo(
        target: Quote::class,
        nullable: false,
        fkAction: 'NO ACTION'
    )]
    private ?Quote $quote = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $quote_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $allowance_charge_id = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $amount = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $vat_or_tax = null,
    ) {
    }

    /**
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException(
                'QuoteAllowanceCharge has no ID (not persisted yet)'
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

    public function getQuoteId(): ?int
    {
        return $this->quote_id;
    }

    public function setQuoteId(int $quote_id): void
    {
        $this->quote_id = $quote_id;
    }

    public function getAllowanceChargeId(): ?int
    {
        return $this->allowance_charge_id;
    }

    public function setAllowanceChargeId(int $allowance_charge_id): void
    {
        $this->allowance_charge_id = $allowance_charge_id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getVatOrTax(): ?float
    {
        return $this->vat_or_tax;
    }

    public function setVatOrTax(float $vat_or_tax): void
    {
        $this->vat_or_tax = $vat_or_tax;
    }
}
