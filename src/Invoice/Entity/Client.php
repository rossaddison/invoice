<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;

#[Entity(repository: \App\Invoice\Client\ClientRepository::class)]
#[Behavior\CreatedAt(field: 'client_date_created', column: 'client_date_created')]
#[Behavior\UpdatedAt(field: 'client_date_modified', column: 'client_date_modified')]
class Client
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'datetime')]
    private \DateTimeImmutable $client_date_created;

    #[Column(type: 'datetime')]
    private \DateTimeImmutable $client_date_modified;

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
        #[Column(type: 'string(3)', nullable: true)]
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
        #[Column(type: 'string(16)', nullable: true)]
        private ?string $client_avs = '',
        #[Column(type: 'string(151)', nullable: true)]
        private ?string $client_insurednumber = '',
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $client_veka = '',
        #[Column(type: 'date', nullable: true)]
        private mixed $client_birthdate = null,
        #[Column(type: 'integer', nullable: false, default: 0)]
        private ?int $client_age = 0,
        #[Column(type: 'tinyInteger(4)', nullable: false, default: 0)]
        private ?int $client_gender = 0,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $postaladdress_id = null,
    ) {
        $this->client_full_name     = ltrim(rtrim($this->client_name.' '.($this->client_surname ?? 'surname_unknown')));
        $this->client_date_created  = new \DateTimeImmutable();
        $this->client_date_modified = new \DateTimeImmutable();
        $this->delivery_locations   = new ArrayCollection();
        $this->invs                 = new ArrayCollection();
    }

    public function getClient_id(): ?int
    {
        return $this->id;
    }

    public function getClient_email(): string
    {
        return $this->client_email;
    }

    public function setClient_email(string $client_email): void
    {
        $this->client_email = $client_email;
    }

    public function getClient_mobile(): ?string
    {
        return $this->client_mobile;
    }

    public function setClient_mobile(string $client_mobile): void
    {
        $this->client_mobile = $client_mobile;
    }

    public function setClient_date_created(string $client_date_created): void
    {
        /*
         * @see ImportController insertClients function
         */
        $this->client_date_created = (new \DateTimeImmutable())->createFromFormat('Y-m-d h:i:s', $client_date_created) ?: new \DateTimeImmutable('now');
    }

    public function getClient_date_created(): \DateTimeImmutable
    {
        /* @var DateTimeImmutable $this->client_date_created */
        return $this->client_date_created;
    }

    public function getClient_date_modified(): \DateTimeImmutable
    {
        /* @var DateTimeImmutable $this->client_date_created */
        return $this->client_date_modified;
    }

    // Used in ImportController to import Invoiceplane $client_date_modified
    public function setClient_date_modified(string $client_date_modified): void
    {
        $this->client_date_modified = (new \DateTimeImmutable())->createFromFormat('Y-m-d h:i:s', $client_date_modified) ?: new \DateTimeImmutable('now');
    }

    public function getClient_title(): ?string
    {
        return $this->client_title;
    }

    public function setClient_title(?string $client_title): void
    {
        $this->client_title = $client_title;
    }

    public function setClient_full_name(string $client_full_name): void
    {
        $this->client_full_name = $client_full_name;
    }

    public function getClient_full_name(): string
    {
        if (null == $this->client_full_name) {
            if (null !== $this->client_surname) {
                return ltrim(rtrim($this->client_name.' '.$this->client_surname));
            }

            return ltrim(rtrim($this->client_name));
        }

        return $this->client_full_name;
    }

    public function getClient_name(): string
    {
        return $this->client_name;
    }

    public function setClient_name(string $client_name): void
    {
        $this->client_name = $client_name;
    }

    public function getClient_surname(): ?string
    {
        return $this->client_surname;
    }

    public function setClient_surname(string $client_surname): void
    {
        $this->client_surname = $client_surname;
    }

    public function getClient_frequency(): ?string
    {
        return $this->client_frequency;
    }

    public function setClient_frequency(string $client_frequency): void
    {
        $this->client_frequency = $client_frequency;
    }

    public function getClient_group(): ?string
    {
        return $this->client_group;
    }

    public function setClient_group(string $client_group): void
    {
        $this->client_group = $client_group;
    }

    public function getClient_address_1(): ?string
    {
        return $this->client_address_1;
    }

    public function setClient_address_1(string $client_address_1): void
    {
        $this->client_address_1 = $client_address_1;
    }

    public function getClient_address_2(): ?string
    {
        return $this->client_address_2;
    }

    public function setClient_address_2(string $client_address_2): void
    {
        $this->client_address_2 = $client_address_2;
    }

    public function getClient_building_number(): ?string
    {
        return $this->client_building_number;
    }

    public function setClient_building_number(string $client_building_number): void
    {
        $this->client_building_number = $client_building_number;
    }

    public function getClient_city(): ?string
    {
        return $this->client_city;
    }

    public function setClient_city(string $client_city): void
    {
        $this->client_city = $client_city;
    }

    public function getClient_state(): ?string
    {
        return $this->client_state;
    }

    public function setClient_state(string $client_state): void
    {
        $this->client_state = $client_state;
    }

    public function getClient_zip(): ?string
    {
        return $this->client_zip;
    }

    public function setClient_zip(string $client_zip): void
    {
        $this->client_zip = $client_zip;
    }

    public function getClient_country(): ?string
    {
        return $this->client_country;
    }

    public function setClient_country(string $client_country): void
    {
        $this->client_country = $client_country;
    }

    public function getClient_phone(): ?string
    {
        return $this->client_phone;
    }

    public function setClient_phone(string $client_phone): void
    {
        $this->client_phone = $client_phone;
    }

    public function getClient_fax(): ?string
    {
        return $this->client_fax;
    }

    public function setClient_fax(string $client_fax): void
    {
        $this->client_fax = $client_fax;
    }

    public function getClient_web(): ?string
    {
        return $this->client_web;
    }

    public function setClient_web(string $client_web): void
    {
        $this->client_web = $client_web;
    }

    public function getClient_vat_id(): string
    {
        return (string) $this->client_vat_id;
    }

    public function setClient_vat_id(string $client_vat_id): void
    {
        $this->client_vat_id = $client_vat_id;
    }

    public function getClient_tax_code(): ?string
    {
        return $this->client_tax_code;
    }

    public function setClient_tax_code(string $client_tax_code): void
    {
        $this->client_tax_code = $client_tax_code;
    }

    public function getClient_language(): ?string
    {
        return $this->client_language;
    }

    public function setClient_language(string $client_language): void
    {
        $this->client_language = $client_language;
    }

    public function getClient_active(): bool
    {
        return $this->client_active;
    }

    public function setClient_active(bool $client_active): void
    {
        $this->client_active = $client_active;
    }

    public function getClient_avs(): ?string
    {
        return $this->client_avs;
    }

    public function setClient_avs(string $client_avs): void
    {
        $this->client_avs = $client_avs;
    }

    public function getClient_insurednumber(): ?string
    {
        return $this->client_insurednumber;
    }

    public function setClient_insurednumber(string $client_insurednumber): void
    {
        $this->client_insurednumber = $client_insurednumber;
    }

    public function getClient_veka(): ?string
    {
        return $this->client_veka;
    }

    public function setClient_veka(string $client_veka): void
    {
        $this->client_veka = $client_veka;
    }

    // cycle
    public function getClient_birthdate(): \DateTimeImmutable|string|null
    {
        /* @var DateTimeImmutable|string|null $this->client_birthdate */
        return $this->client_birthdate;
    }

    public function setClient_birthdate(?\DateTime $client_birthdate): void
    {
        $this->client_birthdate = $client_birthdate;
    }

    public function getClient_age(): ?int
    {
        return $this->client_age;
    }

    public function setClient_age(int $client_age): void
    {
        $this->client_age = $client_age;
    }

    public function getClient_number(): ?string
    {
        return $this->client_number;
    }

    public function setClient_number(?string $client_number): void
    {
        $this->client_number = $client_number;
    }

    public function getClient_gender(): ?int
    {
        return $this->client_gender;
    }

    public function setClient_gender(int $client_gender): void
    {
        $this->client_gender = $client_gender;
    }

    public function setPostaladdress_id(int $postaladdress_id): void
    {
        $this->postaladdress_id = $postaladdress_id;
    }

    public function getPostaladdress_id(): ?int
    {
        return $this->postaladdress_id;
    }

    public function getDelivery_locations(): ArrayCollection
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

    public function isNewRecord(): bool
    {
        return null === $this->getClient_id() ? true : false;
    }
}
