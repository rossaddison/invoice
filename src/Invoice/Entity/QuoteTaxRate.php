<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: QTR::class)]

class QuoteTaxRate
{
    #[BelongsTo(target: Quote::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Quote $quote = null;

    #[BelongsTo(target: TaxRate::class, nullable: false)]
    private ?TaxRate $tax_rate = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)',
        nullable: false)]
        private ?int $quote_id = null,
        #[Column(type: 'integer(11)',
        nullable: false)]
        private ?int $tax_rate_id = null,
        #[Column(type: 'integer(1)',
        nullable: false, default: 0)]
        private ?int $include_item_tax = null,
        #[Column(type: 'decimal(20,2)',
        nullable: false, default: 0.00)]
        private ?float $quote_tax_rate_amount = 0.00)
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

    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    public function setTaxRate(?TaxRate $tax_rate): void
    {
        $this->tax_rate = $tax_rate;
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

    public function getTaxRateId(): string
    {
        return (string) $this->tax_rate_id;
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    public function getIncludeItemTax(): ?int
    {
        return $this->include_item_tax;
    }

    public function setIncludeItemTax(int $include_item_tax): void
    {
        $this->include_item_tax = $include_item_tax;
    }

    public function getQuoteTaxRateAmount(): ?float
    {
        return $this->quote_tax_rate_amount;
    }

    public function setQuoteTaxRateAmount(float $quote_tax_rate_amount): void
    {
        $this->quote_tax_rate_amount = $quote_tax_rate_amount;
    }
}
