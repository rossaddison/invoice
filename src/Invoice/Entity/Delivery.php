<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\Delivery\DeliveryRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class Delivery
{
    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_created;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_modified;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $start_date;

    #[Column(type: 'datetime', nullable: true)]
    private ?DateTimeImmutable $actual_delivery_date;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $end_date;

    #[BelongsTo(target: DeliveryLocation::class, nullable: true, fkAction: 'NO ACTION')]
    private ?DeliveryLocation $delivery_location = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        // nullable
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $inv_item_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $delivery_location_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $delivery_party_id = null,
    ) {
        $this->actual_delivery_date = new DateTimeImmutable();
        $this->date_created = new DateTimeImmutable();
        $this->date_modified = new DateTimeImmutable();
        $this->start_date = new DateTimeImmutable(date('Y-m-01'));
        $this->end_date = new DateTimeImmutable(date('Y-m-t'));
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDeliveryLocationId(): string
    {
        return (string) $this->delivery_location_id;
    }

    public function setDeliveryLocationId(int $delivery_location_id): void
    {
        $this->delivery_location_id = $delivery_location_id;
    }

    public function getDeliveryPartyId(): string
    {
        return (string) $this->delivery_party_id;
    }

    public function setDeliveryPartyId(int $delivery_party_id): void
    {
        $this->delivery_party_id = $delivery_party_id;
    }

    public function isNewRecord(): bool
    {
        return $this->getId() === null;
    }
}
