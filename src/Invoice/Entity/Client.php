<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\ORM\Entity\Behavior;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\Client\ClientRepository::class)]
#[Behavior\CreatedAt(field: 'client_date_created', column: 'client_date_created')]
#[Behavior\UpdatedAt(field: 'client_date_modified', column: 'client_date_modified')]
class Client
{
    #[Column(type: 'primary')]
    public ?int $id = null;
 
    #[Column(type: 'datetime')]
    private DateTimeImmutable $client_date_created;

    #[Column(type: 'datetime')]
    private DateTimeImmutable $client_date_modified;

    #[Column(type: 'string(151)', nullable: true)]
    private ?string $client_full_name = '';

    /**
     * @var ArrayCollection<array-key, DeliveryLocation>
     */
    #[HasMany(target: DeliveryLocation::class)]
    private readonly ArrayCollection $delivery_locations;

    /**
     * @var ArrayCollection<array-key, Inv>
     */
    #[HasMany(target: Inv::class)]
    private ArrayCollection $invs;

    /**
     * @var ArrayCollection<array-key, ProductClient>
     */
    #[HasMany(target: ProductClient::class)]
    private ArrayCollection $product_associations;

    public function __construct(
        #[Column(type: 'string(254)', nullable: true)]
        private string $client_email = '',
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $client_mobile = '',
        #[Column(type: 'string(10)', nullable: true)]
        private ?string $client_title = '',
        // treat as firstname
        #[Column(type: 'string(50)')]
        private string $client_name = '',
        #[Column(type: 'string(151)', nullable: true)]
        private ?string $client_surname = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $client_group = '',
        #[Column(type: 'string(15)', nullable: true)]
        private ?string $client_frequency = '',
        #[Column(type: 'string(12)', nullable: true)]
        private ?string $client_number = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_address_1 = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_address_2 = '',
        #[Column(type: 'string(10)', nullable: true)]
        private ?string $client_building_number = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_city = '',
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $client_state = '',
        #[Column(type: 'string(10)', nullable: true)]
        private ?string $client_zip = '',
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $client_country = '',
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $client_phone = '',
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $client_fax = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $client_web = '',
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $client_vat_id = '',
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $client_tax_code = '',
        #[Column(type: 'string(151)', nullable: true)]
        private ?string $client_language = '',
        #[Column(type: 'bool', default: false)]
        private bool $client_active = false,
        #[Column(type: 'date', nullable: true)]
        private mixed $client_birthdate = null,
        #[Column(type: 'integer', nullable: false, default: 0)]
        private ?int $client_age = 0,
        #[Column(type: 'tinyInteger(4)', nullable: false, default: 0)]
        private ?int $client_gender = 0,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $postaladdress_id = null,
    ) {
        $this->client_full_name = ltrim(rtrim($this->client_name . ' ' . ($this->client_surname ?? 'surname_unknown')));
        $this->client_date_created = new DateTimeImmutable();
        $this->client_date_modified = new DateTimeImmutable();
        $this->delivery_locations = new ArrayCollection();
        $this->invs = new ArrayCollection();
        $this->product_associations = new ArrayCollection();
    }
    
    /**
     * Returns the database identifier for this Client.
     *
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqClientId(): int
    {
        if ($this->id === null) {
            throw new \LogicException('Client has no ID (not persisted yet)');
        }

        return $this->id;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }
    
    public function getClientEmail(): string
    {
        return $this->client_email;
    }

    public function setClientEmail(string $client_email): void
    {
        $this->client_email = $client_email;
    }

    public function getClientMobile(): ?string
    {
        return $this->client_mobile;
    }

    public function setClientMobile(string $client_mobile): void
    {
        $this->client_mobile = $client_mobile;
    }

    public function setClientDateCreated(string $client_date_created): void
    {
        /**
         * Related logic: see ImportController insertClients function
         */
        $this->client_date_created =  new DateTimeImmutable()->createFromFormat('Y-m-d h:i:s', $client_date_created) ?: new DateTimeImmutable('now');
    }

    public function getClientDateCreated(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->client_date_created */
        return $this->client_date_created;
    }

    public function getClientDateModified(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->client_date_created */
        return $this->client_date_modified;
    }

    // Used in ImportController to import Invoiceplane $client_date_modified
    public function setClientDateModified(string $client_date_modified): void
    {
        $this->client_date_modified =  new DateTimeImmutable()->createFromFormat('Y-m-d h:i:s', $client_date_modified) ?: new DateTimeImmutable('now');
    }

    public function getClientTitle(): ?string
    {
        return $this->client_title;
    }

    public function setClientTitle(?string $client_title): void
    {
        $this->client_title = $client_title;
    }

    public function setClientFullName(string $client_full_name): void
    {
        $this->client_full_name = $client_full_name;
    }

    public function getClientFullName(): string
    {
        if (null == $this->client_full_name) {
            if (null !== $this->client_surname) {
                return ltrim(rtrim($this->client_name . ' ' . $this->client_surname));
            }
            return ltrim(rtrim($this->client_name));
        }
        return $this->client_full_name;
    }

    public function getClientName(): string
    {
        return $this->client_name;
    }

    public function setClientName(string $client_name): void
    {
        $this->client_name = $client_name;
    }

    public function getClientSurname(): ?string
    {
        return $this->client_surname;
    }

    public function setClientSurname(string $client_surname): void
    {
        $this->client_surname = $client_surname;
    }

    public function getClientFrequency(): ?string
    {
        return $this->client_frequency;
    }

    public function setClientFrequency(string $client_frequency): void
    {
        $this->client_frequency = $client_frequency;
    }

    public function getClientGroup(): ?string
    {
        return $this->client_group;
    }

    public function setClientGroup(string $client_group): void
    {
        $this->client_group = $client_group;
    }

    public function getClientAddress1(): ?string
    {
        return $this->client_address_1;
    }

    public function setClientAddress1(string $client_address_1): void
    {
        $this->client_address_1 = $client_address_1;
    }

    public function getClientAddress2(): ?string
    {
        return $this->client_address_2;
    }

    public function setClientAddress2(string $client_address_2): void
    {
        $this->client_address_2 = $client_address_2;
    }

    public function getClientBuildingNumber(): ?string
    {
        return $this->client_building_number;
    }

    public function setClientBuildingNumber(string $client_building_number): void
    {
        $this->client_building_number = $client_building_number;
    }

    public function getClientCity(): ?string
    {
        return $this->client_city;
    }

    public function setClientCity(string $client_city): void
    {
        $this->client_city = $client_city;
    }

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
        return (string) $this->client_vat_id;
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

    public function getClientLanguage(): ?string
    {
        return $this->client_language;
    }

    public function setClientLanguage(string $client_language): void
    {
        $this->client_language = $client_language;
    }

    public function getClientActive(): bool
    {
        return $this->client_active;
    }

    public function setClientActive(bool $client_active): void
    {
        $this->client_active = $client_active;
    }

    //cycle
    public function getClientBirthdate(): DateTimeImmutable|string|null
    {
        /** @var DateTimeImmutable|string|null $this->client_birthdate */
        return $this->client_birthdate;
    }

    public function setClientBirthdate(?DateTime $client_birthdate): void
    {
        $this->client_birthdate = $client_birthdate;
    }

    public function getClientAge(): ?int
    {
        return $this->client_age;
    }

    public function setClientAge(int $client_age): void
    {
        $this->client_age = $client_age;
    }

    public function getClientNumber(): ?string
    {
        return $this->client_number;
    }

    public function setClientNumber(?string $client_number): void
    {
        $this->client_number = $client_number;
    }

    public function getClientGender(): ?int
    {
        return $this->client_gender;
    }

    public function setClientGender(int $client_gender): void
    {
        $this->client_gender = $client_gender;
    }

    public function setPostaladdressId(int $postaladdress_id): void
    {
        $this->postaladdress_id = $postaladdress_id;
    }

    public function getPostaladdressId(): ?int
    {
        return $this->postaladdress_id;
    }

    public function getDeliveryLocations(): ArrayCollection
    {
        return $this->delivery_locations;
    }

    public function getInvs(): ArrayCollection
    {
        return $this->invs;
    }

    public function setInvs(): void
    {
        $this->invs = new ArrayCollection();
    }

    public function addInv(Inv $inv): void
    {
        $this->invs[] = $inv;
    }
}
