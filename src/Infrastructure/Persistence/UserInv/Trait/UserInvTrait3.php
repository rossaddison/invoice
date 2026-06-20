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
trait UserInvTrait3
{

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(string $fax): void
    {
        $this->fax = $fax;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): void
    {
        $this->mobile = $mobile;
    }

    public function setWeb(string $web): void
    {
        $this->web = $web;
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

    public function getAllClients(): ?bool
    {
        return $this->all_clients;
    }

    public function setAllClients(bool $all_clients): void
    {
        $this->all_clients = $all_clients;
    }

    public function getSubscribernumber(): ?string
    {
        return $this->subscribernumber;
    }

    public function setSubscribernumber(string $subscribernumber): void
    {
        $this->subscribernumber = $subscribernumber;
    }
}
