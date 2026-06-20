<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CompanyPrivate\Trait;

use App\Infrastructure\Persistence\Company\Company;
use DateTime;
use DateTimeImmutable;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait CompanyPrivateTrait1
{

    public function isActiveToday(): bool
    {
        $today = new \DateTimeImmutable('today');
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        if ($startDate === null || $endDate === null) {
            return false;
        }

        return $today >= $startDate && $today <= $endDate;
    }

    //get relation $company
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    //set relation $company
    public function setCompany(?Company $company): void
    {
        $this->company = $company;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'CompanyPrivate');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqCompanyId(): int
    {
        return $this->requireId($this->company_id, 'Company');
    }

    public function setCompanyId(int $company_id): void
    {
        $this->company_id = $company_id;
    }

    public function getVatId(): string
    {
        return (string) $this->vat_id;
    }

    public function setVatId(string $vat_id): void
    {
        $this->vat_id = $vat_id;
    }

    public function getTaxCode(): ?string
    {
        return $this->tax_code;
    }

    public function setTaxCode(string $tax_code): void
    {
        $this->tax_code = $tax_code;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(string $iban): void
    {
        $this->iban = $iban;
    }

    public function getBacsSortCode(): ?string
    {
        return $this->bacs_sort_code;
    }

    public function setBacsSortCode(string $bacs_sort_code): void
    {
        $this->bacs_sort_code = $bacs_sort_code;
    }
}
