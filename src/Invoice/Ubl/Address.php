<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

// Usage: Search 'new Address' under PeppolHelper
class Address implements XmlSerializable
{
    public function __construct(private readonly ?string $streetName, private readonly ?string $additionalStreetName, private readonly ?string $buildingNumber, private readonly ?string $cityName, private readonly ?string $postalZone, private readonly ?string $countrySubentity, private readonly ?Country $country, private readonly bool $ubl_cr_155 = false, private readonly bool $ubl_cr_218 = false, private readonly bool $ubl_cr_367 = false) {}

    // The getters are used in StoreCoveHelper
    public function getStreetName(): ?string
    {
        return $this->streetName;
    }

    public function getAdditionalStreetName(): ?string
    {
        return $this->additionalStreetName;
    }

    public function getBuildingNumber(): ?string
    {
        return $this->buildingNumber;
    }

    public function getCityName(): ?string
    {
        return $this->cityName;
    }

    public function getPostalZone(): ?string
    {
        return $this->postalZone;
    }

    public function getCountrySubEntity(): ?string
    {
        return $this->countrySubentity;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    /**
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->streetName !== null) {
            $writer->write([
                Schema::CBC . 'StreetName' => $this->streetName,
            ]);
        }
        if ($this->additionalStreetName !== null) {
            $writer->write([
                Schema::CBC . 'AdditionalStreetName' => $this->additionalStreetName,
            ]);
        }
        if ($this->buildingNumber !== null
            && $this->ubl_cr_218 === false
            && $this->ubl_cr_155 === false
            && $this->ubl_cr_367 === false) {
            $writer->write([
                Schema::CBC . 'BuildingNumber' => $this->buildingNumber,
            ]);
        }
        if ($this->cityName !== null) {
            $writer->write([
                Schema::CBC . 'CityName' => $this->cityName,
            ]);
        }
        if ($this->postalZone !== null) {
            $writer->write([
                Schema::CBC . 'PostalZone' => $this->postalZone,
            ]);
        }
        if ($this->countrySubentity !== null) {
            $writer->write([
                Schema::CBC . 'CountrySubentity' => $this->countrySubentity,
            ]);
        }
        if ($this->country !== null) {
            $writer->write([
                Schema::CAC . 'Country' => $this->country,
            ]);
        }
    }
}
