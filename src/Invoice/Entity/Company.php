<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\Company\CompanyRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class Company
{
    #[Column(type: 'datetime')]
    private readonly DateTimeImmutable $date_created;

    #[Column(type: 'datetime')]
    private readonly DateTimeImmutable $date_modified;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'tinyInteger(1)', nullable: false, default: 0)]
        private ?int $current = 0,
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $address_1 = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $address_2 = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $city = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $state = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $zip = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $country = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $phone = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $fax = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $email = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $web = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $slack = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $facebook = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $twitter = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $linkedin = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $whatsapp = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $arbitrationBody = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $arbitrationJurisdiction = '',
    ) {
        $this->date_created = new DateTimeImmutable();
        $this->date_modified = new DateTimeImmutable();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCurrent(): int|null
    {
        return $this->current;
    }

    public function setCurrent(int $current): void
    {
        $this->current = $current;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAddress_1(): string|null
    {
        return $this->address_1;
    }

    public function setAddress_1(string $address_1): void
    {
        $this->address_1 = $address_1;
    }

    public function getAddress_2(): string|null
    {
        return $this->address_2;
    }

    public function setAddress_2(string $address_2): void
    {
        $this->address_2 = $address_2;
    }

    public function getCity(): string|null
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getState(): string|null
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getZip(): string|null
    {
        return $this->zip;
    }

    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCountry(): string|null
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getPhone(): string|null
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getFax(): string|null
    {
        return $this->fax;
    }

    public function setFax(string $fax): void
    {
        $this->fax = $fax;
    }

    public function getEmail(): string|null
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getWeb(): string|null
    {
        return $this->web;
    }

    public function setWeb(string $web): void
    {
        $this->web = $web;
    }

    public function getSlack(): string|null
    {
        return $this->slack;
    }

    public function setSlack(string $slack): void
    {
        $this->slack = $slack;
    }

    public function getTwitter(): string|null
    {
        return $this->twitter;
    }

    public function setTwitter(string $twitter): void
    {
        $this->twitter = $twitter;
    }

    public function getFacebook(): string|null
    {
        return $this->facebook;
    }

    public function setFacebook(string $facebook): void
    {
        $this->facebook = $facebook;
    }

    public function getLinkedIn(): string|null
    {
        return $this->linkedin;
    }

    public function setLinkedIn(string $linkedin): void
    {
        $this->linkedin = $linkedin;
    }

    public function getWhatsapp(): string|null
    {
        return $this->whatsapp;
    }

    public function setWhatsapp(string $whatsapp): void
    {
        $this->whatsapp = $whatsapp;
    }

    public function getArbitrationBody(): string|null
    {
        return $this->arbitrationBody;
    }

    public function setArbitrationBody(string $arbitrationBody): void
    {
        $this->arbitrationBody = $arbitrationBody;
    }

    public function getArbitrationJurisdiction(): string|null
    {
        return $this->arbitrationJurisdiction;
    }

    public function setArbitrationJurisdiction(string $arbitrationJurisdiction): void
    {
        $this->arbitrationJurisdiction = $arbitrationJurisdiction;
    }

    public function getDate_created(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function getDate_modified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function isNewRecord(): bool
    {
        return $this->getId() === null;
    }
}
