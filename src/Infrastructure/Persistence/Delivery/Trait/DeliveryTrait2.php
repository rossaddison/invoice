<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Delivery\Trait;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use DateTimeImmutable;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait DeliveryTrait2
{

    public function setDateCreated(DateTimeImmutable $date_created): void
    {
        $this->date_created = $date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->date_modified */
        return $this->date_modified;
    }

    public function setDateModified(DateTimeImmutable $date_modified): void
    {
        $this->date_modified = $date_modified;
    }

    public function hasDeliveryLocationId(): bool
    {
        return $this->delivery_location_id !== null;
    }

    public function reqDeliveryLocationId(): int
    {
        return $this->requireId($this->delivery_location_id, 'DeliveryLocation');
    }

    public function setDeliveryLocationId(int $delivery_location_id): void
    {
        $this->delivery_location_id = $delivery_location_id;
    }

    public function hasDeliveryPartyId(): bool
    {
        return $this->delivery_party_id !== null;
    }

    public function reqDeliveryPartyId(): int
    {
        return $this->requireId($this->delivery_party_id, 'DeliveryParty');
    }

    public function setDeliveryPartyId(int $delivery_party_id): void
    {
        $this->delivery_party_id = $delivery_party_id;
    }
}
