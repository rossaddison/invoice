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
        $this->date_created = $del->getDate_created();
        $this->date_modified = $del->getDate_modified();
        $this->client_id = $del->getClient_id();
        $this->name = $del->getName();
        $this->building_number = $del->getBuildingNumber();
        $this->address_1 = $del->getAddress_1();
        $this->address_2 = $del->getAddress_2();
        $this->city = $del->getCity();
        $this->state = $del->getState();
        $this->zip = $del->getZip();
        $this->country = $del->getCountry();
        // 13 digit code
        $this->global_location_number = $del->getGlobal_location_number();
        // the key of the array is saved
        $this->electronic_address_scheme = $del->getElectronic_address_scheme();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }    

    public function getDate_created(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function getDate_modified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function getClient_id(): ?string
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

    public function getAddress_1(): ?string
    {
        return $this->address_1;
    }

    public function getAddress_2(): ?string
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

    public function getGlobal_location_number(): ?string
    {
        return $this->global_location_number;
    }

    public function getElectronic_address_scheme(): ?string
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
