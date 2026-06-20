<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Company\Trait;

use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait CompanyTrait1
{

    public function getCompanyPrivates(): ArrayCollection
    {
        return $this->companyPrivates;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Company');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCurrent(): ?int
    {
        return $this->current;
    }

    public function setCurrent(int $current): void
    {
        $this->current = $current;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
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

    public function setState(string $state): void
    {
        $this->state = $state;
    }
}
