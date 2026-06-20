<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Inv\Trait;

/**
 * @method int requireId(?int $id, string $context)
 */
trait InvTrait2
{

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqUserId(): int
    {
        return $this->requireId($this->user_id, 'User');
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function reqClientId(): int
    {
        return $this->requireId($this->client_id, 'Client');
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function reqGroupId(): int
    {
        return $this->requireId($this->group_id, 'Group');
    }

    public function setGroupId(int $group_id): void
    {
        $this->group_id = $group_id;
    }

    // copying an invoice without a so_id: so_id will be null
    public function getSoId(): ?int
    {
        return $this->so_id;
    }

    public function setSoId(int $so_id): void
    {
        $this->so_id = $so_id;
    }

    // copying an invoice without a quote_id: so quote_id will be null
    public function getQuoteId(): ?int
    {
        return $this->quote_id;
    }

    public function setQuoteId(int $quote_id): void
    {
        $this->quote_id = $quote_id;
    }

    public function getDeliveryId(): ?int
    {
        return $this->delivery_id;
    }

    public function setDeliveryId(int $delivery_id): void
    {
        $this->delivery_id = $delivery_id;
    }

    public function getDeliveryLocationId(): ?int
    {
        return $this->delivery_location_id;
    }

    public function setDeliveryLocationId(int $delivery_location_id): void
    {
        $this->delivery_location_id = $delivery_location_id;
    }
}
