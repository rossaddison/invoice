<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\TaxRate\TaxRateRepository::class)]
class TaxRate
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(#[Column(type: 'string(2)', nullable: true)]
        private ?string $tax_rate_code = '', #[Column(type: 'string(2)', nullable: true)]
        private ?string $peppol_tax_rate_code = '', #[Column(type: 'string(30)', nullable: false, default: 'standard')]
        private string $storecove_tax_type = '', #[Column(type: 'text', nullable: true)]
        private ?string $tax_rate_name = '', #[Column(type: 'decimal(5,2)', nullable: false, default_value: 0.00)]
        private ?float $tax_rate_percent = 0.00, #[Column(type: 'bool', default: false)]
        private bool $tax_rate_default = false)
    {
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->id = $tax_rate_id;
    }

    public function getTaxRateId(): ?int
    {
        return $this->id;
    }

    public function getTaxRateName(): ?string
    {
        return $this->tax_rate_name;
    }

    public function setTaxRateName(string $tax_rate_name): void
    {
        $this->tax_rate_name = $tax_rate_name;
    }

    public function getTaxRateCode(): ?string
    {
        return $this->tax_rate_code;
    }

    public function setTaxRateCode(string $tax_rate_code): void
    {
        $this->tax_rate_code = $tax_rate_code;
    }

    public function getPeppolTaxRateCode(): ?string
    {
        return $this->peppol_tax_rate_code;
    }

    public function setPeppolTaxRateCode(string $peppol_tax_rate_code): void
    {
        $this->peppol_tax_rate_code = $peppol_tax_rate_code;
    }

    public function getStorecoveTaxType(): string
    {
        return $this->storecove_tax_type;
    }

    public function setStorecoveTaxType(string $storecove_tax_type): void
    {
        $this->storecove_tax_type = $storecove_tax_type;
    }

    public function getTaxRatePercent(): ?float
    {
        return $this->tax_rate_percent;
    }

    public function setTaxRatePercent(float $tax_rate_percent): void
    {
        $this->tax_rate_percent = $tax_rate_percent;
    }

    public function getTaxRateDefault(): bool
    {
        return $this->tax_rate_default;
    }

    public function setTaxRateDefault(bool $tax_rate_default): void
    {
        $this->tax_rate_default = $tax_rate_default;
    }

    public function isNewRecord(): bool
    {
        return null === $this->getTaxRateId();
    }
}
