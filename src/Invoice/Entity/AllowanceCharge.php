<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\AllowanceCharge\AllowanceChargeRepository::class)]

class AllowanceCharge
{
    #[BelongsTo(target: TaxRate::class, nullable: false, fkAction: 'NO ACTION')]
    private ?TaxRate $tax_rate = null;

    public function __construct(#[Column(type: 'primary')]
        private ?int $id = null, #[Column(type: 'bool', typecast: 'bool', default: false, nullable: false)]
        private bool $identifier = false, #[Column(type: 'string(3)', nullable: false)]
        private string $reason_code = '', #[Column(type: 'longText)', nullable: false)]
        private string $reason = '', #[Column(type: 'integer(11)', nullable: false)]
        private int $multiplier_factor_numeric = 0, #[Column(type: 'integer(11)', nullable: false)]
        private int $amount = 0, #[Column(type: 'integer(11)', nullable: false)]
        private int $base_amount = 0, #[Column(type: 'integer(11)', nullable: false)]
        private ?int $tax_rate_id = null) {}

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getIdentifier(): bool
    {
        return $this->identifier;
    }

    public function setIdentifier(bool $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getReasonCode(): string
    {
        return $this->reason_code;
    }

    public function setReasonCode(string $reason_code): void
    {
        $this->reason_code = $reason_code;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getMultiplierFactorNumeric(): int
    {
        return $this->multiplier_factor_numeric;
    }

    public function setMultiplierFactorNumeric(int $multiplier_factor_numeric): void
    {
        $this->multiplier_factor_numeric = $multiplier_factor_numeric;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getBaseAmount(): int
    {
        return $this->base_amount;
    }

    public function setBaseAmount(int $base_amount): void
    {
        $this->base_amount = $base_amount;
    }

    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    public function setTaxrate(?TaxRate $taxrate): void
    {
        $this->tax_rate = $taxrate;
    }

    public function getTaxRateId(): string
    {
        return (string) $this->tax_rate_id;
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }
}
