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
trait DeliveryLocationTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'DeliveryLocation');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqClientId(): int
    {
        return $this->requireId($this->client_id, 'Client');
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getAddress1(): ?string
    {
        return $this->address_1;
    }

    public function setAddress1(string $address_1): void
    {
        $this->address_1 = $address_1;
    }

    public function getAddress2(): ?string
    {
        return $this->address_2;
    }

    public function setAddress2(string $address_2): void
    {
        $this->address_2 = $address_2;
    }

    public function getBuildingNumber(): ?string
    {
        return $this->building_number;
    }

    public function setBuildingNumber(string $building_number): void
    {
        $this->building_number = $building_number;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getState(): ?string
    {
        return $this->state;
    }
}
