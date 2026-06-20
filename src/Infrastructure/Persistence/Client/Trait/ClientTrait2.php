<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Client\Trait;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Invoice\Client\ClientRepository;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait ClientTrait2
{

    public function getClientName(): string
    {
        return $this->client_name;
    }

    public function setClientName(string $client_name): void
    {
        $this->client_name = $client_name;
    }

    public function getClientSurname(): ?string
    {
        return $this->client_surname;
    }

    public function setClientSurname(string $client_surname): void
    {
        $this->client_surname = $client_surname;
    }

    public function getClientFrequency(): ?string
    {
        return $this->client_frequency;
    }

    public function setClientFrequency(string $client_frequency): void
    {
        $this->client_frequency = $client_frequency;
    }

    public function getClientGroup(): ?string
    {
        return $this->client_group;
    }

    public function setClientGroup(string $client_group): void
    {
        $this->client_group = $client_group;
    }

    public function getClientAddress1(): ?string
    {
        return $this->client_address_1;
    }

    public function setClientAddress1(string $client_address_1): void
    {
        $this->client_address_1 = $client_address_1;
    }

    public function getClientAddress2(): ?string
    {
        return $this->client_address_2;
    }

    public function setClientAddress2(string $client_address_2): void
    {
        $this->client_address_2 = $client_address_2;
    }

    public function getClientBuildingNumber(): ?string
    {
        return $this->client_building_number;
    }

    public function setClientBuildingNumber(string $client_building_number): void
    {
        $this->client_building_number = $client_building_number;
    }

    public function getClientCity(): ?string
    {
        return $this->client_city;
    }

    public function setClientCity(string $client_city): void
    {
        $this->client_city = $client_city;
    }
}
