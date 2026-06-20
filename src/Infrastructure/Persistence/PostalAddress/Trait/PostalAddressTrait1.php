<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PostalAddress\Trait;

use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait PostalAddressTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'PostalAddress');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getStreetName(): string
    {
        return $this->street_name;
    }

    public function setStreetName(string $street_name): void
    {
        $this->street_name = $street_name;
    }

    public function getAdditionalStreetName(): string
    {
        return $this->additional_street_name;
    }

    public function setAdditionalStreetName(string $additional_street_name): void
    {
        $this->additional_street_name = $additional_street_name;
    }

    public function getBuildingNumber(): string
    {
        return $this->building_number;
    }

    public function setBuildingNumber(string $building_number): void
    {
        $this->building_number = $building_number;
    }

    public function getCityName(): string
    {
        return $this->city_name;
    }

    public function setCityName(string $city_name): void
    {
        $this->city_name = $city_name;
    }

    public function getPostalzone(): string
    {
        return $this->postalzone;
    }

    public function setPostalzone(string $postalzone): void
    {
        $this->postalzone = $postalzone;
    }

    public function getCountrysubentity(): string
    {
        return $this->countrysubentity;
    }
}
