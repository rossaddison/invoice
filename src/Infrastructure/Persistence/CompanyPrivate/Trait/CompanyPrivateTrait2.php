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
trait CompanyPrivateTrait2
{

    public function getBacsAccountNumber(): ?string
    {
        return $this->bacs_account_number;
    }

    public function setBacsAccountNumber(string $bacs_account_number): void
    {
        $this->bacs_account_number = $bacs_account_number;
    }

    public function getGln(): ?string
    {
        return $this->gln;
    }

    public function setGln(string $gln): void
    {
        $this->gln = $gln;
    }

    public function getRcc(): ?string
    {
        return $this->rcc;
    }

    public function setRcc(string $rcc): void
    {
        $this->rcc = $rcc;
    }

    public function getLogoFilename(): ?string
    {
        return $this->logo_filename;
    }

    public function setLogoFilename(string $logo_filename): void
    {
        $this->logo_filename = $logo_filename;
    }

    public function getLogoWidth(): ?int
    {
        return $this->logo_width;
    }

    public function setLogoWidth(int $logo_width): void
    {
        $this->logo_width = $logo_width;
    }

    public function getLogoHeight(): ?int
    {
        return $this->logo_height;
    }

    public function setLogoHeight(int $logo_height): void
    {
        $this->logo_height = $logo_height;
    }

    public function getLogoMargin(): ?int
    {
        return $this->logo_margin;
    }

    public function setLogoMargin(int $logo_margin): void
    {
        $this->logo_margin = $logo_margin;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }
}
