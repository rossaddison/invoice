<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\UserInv\Trait;

use DateTimeImmutable;
use App\Infrastructure\Persistence\User\User;
use Yiisoft\Translator\TranslatorInterface as Translator;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait UserInvTrait2
{

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(string $company): void
    {
        $this->company = $company;
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
}
