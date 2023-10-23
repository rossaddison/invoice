<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

// Usage: Search 'new Address' under PeppolHelper
class Address implements XmlSerializable {

    private ?string $streetName;
    private ?string $additionalStreetName;
    private ?string $buildingNumber;
    private ?string $cityName;
    private ?string $postalZone;
    private ?string $countrySubentity;
    private ?Country $country;
    private bool $ubl_cr_155;
    private bool $ubl_cr_218;
    private bool $ubl_cr_367;
    public function __construct(?string $streetName, ?string $additionalStreetName, ?string $buildingNumber, ?string $cityName, ?string $postalZone, ?string $countrySubEntity, ?Country $country, bool $ubl_cr_155 = false, bool $ubl_cr_218 = false, bool $ubl_cr_367 = false) {
        $this->streetName = $streetName;
        $this->additionalStreetName = $additionalStreetName;
        $this->buildingNumber = $buildingNumber;
        $this->cityName = $cityName;
        $this->postalZone = $postalZone;
        $this->countrySubentity = $countrySubEntity;
        $this->country = $country;
        
        //https://docs.peppol.eu/poacc/billing/3.0/rules/ubl-tc434/UBL-CR-155/
        //not(cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:BuildingNumber)
        $this->ubl_cr_155 = $ubl_cr_155;
                
        //https://docs.peppol.eu/poacc/billing/3.0/rules/ubl-tc434/UBL-CR-218/
        //not(cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:BuildingNumber)
        $this->ubl_cr_218 = $ubl_cr_218;
        
        //https://docs.peppol.eu/poacc/billing/3.0/rules/ubl-tc434/UBL-CR-367/
        //not(cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:BuildingNumber)
        $this->ubl_cr_367 = $ubl_cr_367;
    }

    // The getters are used in StoreCoveHelper
    public function getStreetName(): ?string {
        return $this->streetName;
    }

    public function getAdditionalStreetName(): ?string {
        return $this->additionalStreetName;
    }

    public function getBuildingNumber(): ?string {
        return $this->buildingNumber;
    }

    public function getCityName(): ?string {
        return $this->cityName;
    }

    public function getPostalZone(): ?string {
        return $this->postalZone;
    }

    public function getCountrySubEntity(): ?string {
        return $this->countrySubentity;
    }

    public function getCountry(): ?Country {
        return $this->country;
    }

    /**
     *
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void {
        if ($this->streetName !== null) {
            $writer->write([
                Schema::CBC . 'StreetName' => $this->streetName
            ]);
        }
        if ($this->additionalStreetName !== null) {
            $writer->write([
                Schema::CBC . 'AdditionalStreetName' => $this->additionalStreetName
            ]);
        }
        if ($this->buildingNumber !== null 
            && $this->ubl_cr_218 === false
            && $this->ubl_cr_155 === false 
            && $this->ubl_cr_367 === false) {
            $writer->write([
                Schema::CBC . 'BuildingNumber' => $this->buildingNumber
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
