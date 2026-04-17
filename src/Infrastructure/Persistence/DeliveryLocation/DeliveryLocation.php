<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\DeliveryLocation;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

#[Entity(repository: DeliveryLocationRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class DeliveryLocation
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'integer', nullable: false)]
    private int $client_id = 0;

    #[Column(type: 'string(100)', nullable: true)]
    private ?string $name = '';

    #[Column(type: 'string(100)', nullable: true)]
    private ?string $address_1 = '';

    #[Column(type: 'string(100)', nullable: true)]
    private ?string $address_2 = '';

    #[Column(type: 'string(10)', nullable: true)]
    private ?string $building_number = '';

    #[Column(type: 'string(100)', nullable: true)]
    private ?string $city = '';

    #[Column(type: 'string(30)', nullable: true)]
    private ?string $state = '';

    #[Column(type: 'string(10)', nullable: true)]
    private ?string $zip = '';

    #[Column(type: 'string(30)', nullable: true)]
    private ?string $country = '';

    #[Column(type: 'string(50)', nullable: true)]
    private ?string $global_location_number = '';

    #[Column(type: 'string(10)', nullable: true)]
    private ?string $electronic_address_scheme = '';

    #[Column(type: 'datetime')]
    private DateTimeImmutable $date_created;

    #[Column(type: 'datetime')]
    private DateTimeImmutable $date_modified;

    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    public function __construct(
    )
    {
        $this->date_created = new DateTimeImmutable();
        $this->date_modified = new DateTimeImmutable();
    }

    /**
     * Returns the database identifier for this DeliveryLocation
     *
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException('DeliveryLocation'
                . ' has no ID (not persisted yet)');
        }

        return $this->id;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getClientId(): int
    {
        return $this->client_id;
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
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

    public function getBuildingNumber(): ?string
    {
        return $this->building_number;
    }

    public function setBuildingNumber(string $building_number): void
    {
        $this->building_number = $building_number;
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

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getGlobalLocationNumber(): ?string
    {
        return $this->global_location_number;
    }

    public function setGlobalLocationNumber(string $global_location_number): void
    {
        $this->global_location_number = $global_location_number;
    }

    public function getElectronicAddressScheme(): ?string
    {
        return $this->electronic_address_scheme;
    }

    public function setElectronicAddressScheme(
        string $electronic_address_scheme
    ): void
    {
        $this->electronic_address_scheme = $electronic_address_scheme;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function setDateCreated(DateTimeImmutable $date_created): void
    {
        $this->date_created = $date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function setDateModified(DateTimeImmutable $date_modified): void
    {
        $this->date_modified = $date_modified;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function isNewRecord(): bool
    {
        return $this->id === null;
    }
}
