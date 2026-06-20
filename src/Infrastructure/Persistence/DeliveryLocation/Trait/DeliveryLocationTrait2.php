<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\DeliveryLocation\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use DateTimeImmutable;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait DeliveryLocationTrait2
{

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getGlobalLocationNumber(): ?string
    {
        return $this->global_location_number;
    }

    public function setGlobalLocationNumber(?string $global_location_number): void
    {
        $this->global_location_number = $global_location_number;
    }

    public function getElectronicAddressScheme(): ?string
    {
        return $this->electronic_address_scheme;
    }

    public function setElectronicAddressScheme(
        ?string $electronic_address_scheme
    ): void
    {
        $this->electronic_address_scheme = $electronic_address_scheme;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function setDateCreated(DateTimeImmutable $date_created): void
    {
        $this->date_created = $date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function setDateModified(DateTimeImmutable $date_modified): void
    {
        $this->date_modified = $date_modified;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }
}
