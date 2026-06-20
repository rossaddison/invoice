<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Delivery\Trait;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use DateTimeImmutable;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait DeliveryTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Delivery');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function getDeliveryLocation(): ?DeliveryLocation
    {
        return $this->delivery_location;
    }

    public function setDeliveryLocation(
        ?DeliveryLocation $delivery_location
    ): void {
        $this->delivery_location = $delivery_location;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function setInvId(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function getInvItemId(): ?int
    {
        return $this->inv_item_id;
    }

    public function setInvItemId(int $inv_item_id): void
    {
        $this->inv_item_id = $inv_item_id;
    }

    public function getStartDate(): ?DateTimeImmutable
    {
        /** @var DateTimeImmutable|null $this->start_date */
        return $this->start_date;
    }

    public function setStartDate(DateTimeImmutable $start_date): void
    {
        $this->start_date = $start_date;
    }

    public function getActualDeliveryDate(): ?DateTimeImmutable
    {
        /** @var DateTimeImmutable|null $this->actual_delivey_date */
        return $this->actual_delivery_date;
    }

    public function setActualDeliveryDate(?DateTimeImmutable $actual_delivery_date): void
    {
        $this->actual_delivery_date = $actual_delivery_date;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        /** @var DateTimeImmutable|null $this->end_date */
        return $this->end_date;
    }

    public function setEndDate(DateTimeImmutable $end_date): void
    {
        $this->end_date = $end_date;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->date_created */
        return $this->date_created;
    }
}
