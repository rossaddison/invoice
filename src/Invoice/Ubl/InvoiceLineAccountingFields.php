<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

final class InvoiceLineAccountingFields
{
    public function __construct(
        private string $unitCode = UnitCode::UNIT,
        private ?string $unitCodeListId = null,
        private ?string $accountingCostCode = null,
        private ?string $accountingCost = null,
    ) {}

    public function getUnitCode(): string { return $this->unitCode; }

    public function setUnitCode(string $unitCode): self
    {
        $this->unitCode = $unitCode;
        return $this;
    }

    public function getUnitCodeListId(): ?string { return $this->unitCodeListId; }

    public function setUnitCodeListId(?string $unitCodeListId): self
    {
        $this->unitCodeListId = $unitCodeListId;
        return $this;
    }

    public function getAccountingCostCode(): ?string { return $this->accountingCostCode; }

    public function setAccountingCostCode(?string $accountingCostCode): self
    {
        $this->accountingCostCode = $accountingCostCode;
        return $this;
    }

    public function getAccountingCost(): ?string { return $this->accountingCost; }

    public function setAccountingCost(?string $accountingCost): self
    {
        $this->accountingCost = $accountingCost;
        return $this;
    }
}
