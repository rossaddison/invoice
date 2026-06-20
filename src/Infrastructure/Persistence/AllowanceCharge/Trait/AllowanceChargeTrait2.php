<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AllowanceCharge\Trait;

use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;

/**
 * @method int requireId(?int $id, string $context)
 */
trait AllowanceChargeTrait2
{

    public function setBaseAmount(int $base_amount): void
    {
        $this->base_amount = $base_amount;
    }

    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    public function setTaxRate(?TaxRate $taxRate): void
    {
        $this->tax_rate = $taxRate;
    }

    public function getTaxRateId(): int
    {
        return $this->requireId($this->tax_rate_id, 'TaxRate');
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }
}
