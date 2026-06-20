<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AllowanceCharge\Trait;

use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;

/**
 * @method int requireId(?int $id, string $context)
 */
trait AllowanceChargeTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'AllowanceCharge');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
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

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
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
}
