<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use App\Invoice\Entity\Client;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
use \DateTimeImmutable;

#[Entity(repository: \App\Invoice\DeliveryLocation\DeliveryLocationRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class DeliveryLocation {

  #[Column(type: 'primary')]
  private ?int $id = null;

  #[Column(type: 'text', nullable: true)]
  private ?string $name = '';

  #[Column(type: 'text', nullable: true)]
  private ?string $building_number = '';

  #[Column(type: 'text', nullable: true)]
  private ?string $address_1 = '';

  #[Column(type: 'text', nullable: true)]
  private ?string $address_2 = '';

  #[Column(type: 'text', nullable: true)]
  private ?string $city = '';

  #[Column(type: 'text', nullable: true)]
  private ?string $state = '';

  #[Column(type: 'text', nullable: true)]
  private ?string $zip = '';

  #[Column(type: 'text', nullable: true)]
  private ?string $country = '';

  #[Column(type: 'string(13)', nullable: true)]
  private ?string $global_location_number = '';

  #[Column(type: 'string(4)', nullable: true)]
  private ?string $electronic_address_scheme = '';

  #[Column(type: 'datetime')]
  private DateTimeImmutable $date_created;

  #[Column(type: 'datetime')]
  private DateTimeImmutable $date_modified;

  #[BelongsTo(target: \App\Invoice\Entity\Client::class, nullable: false, fkAction: 'NO ACTION')]
  private ?Client $client = null;

  #[Column(type: 'integer(11)', nullable: false)]
  private ?int $client_id = null;

  public function __construct(
    int $id = null,
    int $client_id = null,
    string $name = '',
    string $building_number = '',
    string $address_1 = '',
    string $address_2 = '',
    string $city = '',
    string $state = '',
    string $zip = '',
    string $country = '',
    string $global_location_number = '',
    string $electronic_address_scheme = ''
  ) {
    $this->id = $id;
    $this->client_id = $client_id;
    $this->name = $name;
    $this->building_number = $building_number;
    $this->address_1 = $address_1;
    $this->address_2 = $address_2;
    $this->city = $city;
    $this->state = $state;
    $this->zip = $zip;
    $this->country = $country;
    //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-Delivery/cac-DeliveryLocation/cbc-ID/
    $this->global_location_number = $global_location_number;
    //4 digit https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/
    $this->electronic_address_scheme = $electronic_address_scheme;
    $this->date_created = new \DateTimeImmutable();
    $this->date_modified = new \DateTimeImmutable();
  }

  public function getId(): int|null {
    return $this->id;
  }

  public function setId(int $id): void {
    $this->id = $id;
  }

  public function getName(): string|null {
    return $this->name;
  }

  public function setName(string $name): void {
    $this->name = $name;
  }

  // 2.1 ... not 3.0
  public function getBuildingNumber(): string|null {
    return $this->building_number;
  }

  public function setBuildingNumber(string $building_number): void {
    $this->building_number = $building_number;
  }

  // building number normally included in address_1 for 3.0
  public function getAddress_1(): string|null {
    return $this->address_1;
  }

  public function setAddress_1(string $address_1): void {
    $this->address_1 = $address_1;
  }

  public function getAddress_2(): string|null {
    return $this->address_2;
  }

  public function setAddress_2(string $address_2): void {
    $this->address_2 = $address_2;
  }

  public function getCity(): string|null {
    return $this->city;
  }

  public function setCity(string $city): void {
    $this->city = $city;
  }

  public function getState(): string|null {
    return $this->state;
  }

  public function setState(string $state): void {
    $this->state = $state;
  }

  public function getZip(): string|null {
    return $this->zip;
  }

  public function setZip(string $zip): void {
    $this->zip = $zip;
  }

  public function getCountry(): string|null {
    return $this->country;
  }

  public function setCountry(string $country): void {
    $this->country = $country;
  }

  // https://www.gs1.org/standards/id-keys/gln
  public function getGlobal_location_number(): ?string {
    return $this->global_location_number;
  }

  public function setGlobal_location_number(?string $global_location_number): void {
    $this->global_location_number = $global_location_number;
  }

  public function getElectronic_address_scheme(): ?string {
    return $this->electronic_address_scheme;
  }

  public function setElectronic_address_scheme(?string $electronic_address_scheme): void {
    $this->electronic_address_scheme = $electronic_address_scheme;
  }

  public function getDate_created(): DateTimeImmutable {
    return $this->date_created;
  }

  public function getDate_modified(): DateTimeImmutable {
    return $this->date_modified;
  }

  public function getClient_id(): string {
    return (string) $this->client_id;
  }

  public function setClient_id(int $client_id): void {
    $this->client_id = $client_id;
  }

  public function getClient(): Client|null {
    return $this->client;
  }

  public function setClient(?Client $client): void {
    $this->client = $client;
  }

  public function isNewRecord(): bool {
    return $this->getId() === null;
  }

}
