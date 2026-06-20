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
trait ClientTrait3
{

    public function getClientState(): ?string
    {
        return $this->client_state;
    }

    public function setClientState(string $client_state): void
    {
        $this->client_state = $client_state;
    }

    public function getClientZip(): ?string
    {
        return $this->client_zip;
    }

    public function setClientZip(string $client_zip): void
    {
        $this->client_zip = $client_zip;
    }

    public function getClientCountry(): ?string
    {
        return $this->client_country;
    }

    public function setClientCountry(string $client_country): void
    {
        $this->client_country = $client_country;
    }

    public function getClientPhone(): ?string
    {
        return $this->client_phone;
    }

    public function setClientPhone(string $client_phone): void
    {
        $this->client_phone = $client_phone;
    }

    public function getClientFax(): ?string
    {
        return $this->client_fax;
    }

    public function setClientFax(string $client_fax): void
    {
        $this->client_fax = $client_fax;
    }

    public function getClientWeb(): ?string
    {
        return $this->client_web;
    }

    public function setClientWeb(string $client_web): void
    {
        $this->client_web = $client_web;
    }

    public function getClientVatId(): string
    {
        return $this->client_vat_id;
    }

    public function setClientVatId(string $client_vat_id): void
    {
        $this->client_vat_id = $client_vat_id;
    }

    public function getClientTaxCode(): ?string
    {
        return $this->client_tax_code;
    }

    public function setClientTaxCode(string $client_tax_code): void
    {
        $this->client_tax_code = $client_tax_code;
    }
}
