<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ClientPeppol\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait ClientPeppolTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'ClientPeppol');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function reqClientId(): int
    {
        return $this->requireId($this->client_id, 'Client');
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getEndpointid(): string
    {
        return $this->endpointid;
    }

    public function setEndpointid(string $input): void
    {
        $this->endpointid = $input;
    }

    public function getEndpointidSchemeid(): string
    {
        return $this->endpointid_schemeid;
    }

    public function setEndpointidSchemeid(string $input): void
    {
        $this->endpointid_schemeid = $input;
    }

    public function getIdentificationid(): string
    {
        return $this->identificationid;
    }

    public function setIdentificationid(string $input): void
    {
        $this->identificationid = $input;
    }

    public function getIdentificationidSchemeid(): string
    {
        return $this->identificationid_schemeid;
    }

    public function setIdentificationidSchemeid(string $input): void
    {
        $this->identificationid_schemeid = $input;
    }

    public function getTaxschemecompanyid(): string
    {
        return $this->taxschemecompanyid;
    }
}
