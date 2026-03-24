<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryLocation;

use App\Invoice\Entity\DeliveryLocation;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class DeliveryLocationForm extends FormModel
{
    
    private readonly DateTimeImmutable $date_created;
    private readonly DateTimeImmutable $date_modified;

    private ?int $id = null;
    
    #[Required]
    private ?string $client_id = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $name = '';

    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    private ?string $building_number = '';

    #[Required]
    #[Length(min: 1, max: 100)]
    private ?string $address_1 = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $address_2 = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $city = '';

    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $state = '';

    #[Required]
    #[Length(min: 1, max: 10)]
    private ?string $zip = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $country = '';

    #[Length(min: 0, max: 13, skipOnEmpty: true)]
    private ?string $global_location_number = '';

    #[Length(min: 0, max: 4, skipOnEmpty: true)]
    private ?string $electronic_address_scheme = '';

    public function __construct(DeliveryLocation $del)
    {
        $this->id = $del->getId();
        $this->date_created = $del->getDateCreated();
        $this->date_modified = $del->getDateModified();
        $this->client_id = $del->getClientId();
        $this->name = $del->getName();
        $this->building_number = $del->getBuildingNumber();
        $this->address_1 = $del->getAddress1();
        $this->address_2 = $del->getAddress2();
        $this->city = $del->getCity();
        $this->state = $del->getState();
        $this->zip = $del->getZip();
        $this->country = $del->getCountry();
        // 13 digit code
        $this->global_location_number = $del->getGlobalLocationNumber();
        // the key of the array is saved
        $this->electronic_address_scheme = $del->getElectronicAddressScheme();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }    

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getBuildingNumber(): ?string
    {
        return $this->building_number;
    }

    public function getAddress1(): ?string
    {
        return $this->address_1;
    }

    public function getAddress2(): ?string
    {
        return $this->address_2;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getGlobalLocationNumber(): ?string
    {
        return $this->global_location_number;
    }

    public function getElectronicAddressScheme(): ?string
    {
        return $this->electronic_address_scheme;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
