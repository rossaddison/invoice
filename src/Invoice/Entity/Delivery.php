<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use App\Invoice\Entity\DeliveryLocation;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
use \DateTimeImmutable;

#[Entity(repository: \App\Invoice\Delivery\DeliveryRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class Delivery {

  #[Column(type: 'primary')]
  private ?int $id = null;

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

  #[BelongsTo(target: DeliveryLocation::class, nullable: true, fkAction: "NO ACTION")]
  private ?DeliveryLocation $delivery_location = null;

  #[Column(type: 'integer(11)', nullable: true)]
  private ?int $delivery_location_id = null;

  #[Column(type: 'integer(11)', nullable: true)]
  private ?int $delivery_party_id = null;

  #[Column(type: 'integer(11)', nullable: false)]
  private ?int $inv_id = null;

  // This field will normally have a null value
  // unless there is a separate delivery address for the item to the invoice delivery address
  #[Column(type: 'integer(11)', nullable: true)]
  private ?int $inv_item_id = null;

  public function __construct(
    int $id = null,
    int $inv_id = null,
    // nullable
    int $inv_item_id = null,
    int $delivery_location_id = null,
    int $delivery_party_id = null,
  ) {
    $this->id = $id;
    $this->inv_id = $inv_id;
    $this->inv_item_id = $inv_item_id;
    $this->delivery_location_id = $delivery_location_id;
    $this->delivery_party_id = $delivery_party_id;
    $this->actual_delivery_date = new \DateTimeImmutable();
    $this->date_created = new \DateTimeImmutable();
    $this->date_modified = new \DateTimeImmutable();
    $this->start_date = new \DateTimeImmutable();
    $this->end_date = new \DateTimeImmutable();
  }

  public function getId(): int|null {
    return $this->id;
  }

  public function getDelivery_location(): DeliveryLocation|null {
    return $this->delivery_location;
  }

  public function setId(int $id): void {
    $this->id = $id;
  }

  public function getInv_id(): int|null {
    return $this->inv_id;
  }

  public function setInv_id(int $inv_id): void {
    $this->inv_id = $inv_id;
  }

  public function getInv_item_id(): int|null {
    return $this->inv_item_id;
  }

  public function setInv_item_id(int $inv_item_id): void {
    $this->inv_item_id = $inv_item_id;
  }

  public function getStart_date(): DateTimeImmutable {
    /** @var DateTimeImmutable $this->start_date */
    return $this->start_date;
  }

  public function setStart_date(DateTimeImmutable $start_date): void {
    $this->start_date = $start_date;
  }

  public function getActual_delivery_date(): ?DateTimeImmutable {
    /** @var DateTimeImmutable|null $this->actual_delivey_date */
    return $this->actual_delivery_date;
  }

  public function setActual_delivery_date(?DateTimeImmutable $actual_delivery_date): void {
    $this->actual_delivery_date = $actual_delivery_date;
  }

  public function getEnd_date(): DateTimeImmutable {
    /** @var DateTimeImmutable $this->end_date */
    return $this->end_date;
  }

  public function setEnd_date(DateTimeImmutable $end_date): void {
    $this->end_date = $end_date;
  }

  public function getDate_created(): DateTimeImmutable {
    return $this->date_created;
  }

  public function setDate_created(DateTimeImmutable $date_created): void {
    $this->date_created = $date_created;
  }

  public function getDate_modified(): DateTimeImmutable {
    return $this->date_modified;
  }

  public function setDate_modified(DateTimeImmutable $date_modified): void {
    $this->date_modified = $date_modified;
  }

  public function getDelivery_location_id(): string {
    return (string) $this->delivery_location_id;
  }

  public function setDelivery_location_id(int $delivery_location_id): void {
    $this->delivery_location_id = $delivery_location_id;
  }

  public function getDelivery_party_id(): string {
    return (string) $this->delivery_party_id;
  }

  public function setDelivery_party_id(int $delivery_party_id): void {
    $this->delivery_party_id = $delivery_party_id;
  }

  public function isNewRecord(): bool {
    return $this->getId() === null;
  }

}
