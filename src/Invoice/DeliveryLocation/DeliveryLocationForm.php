<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryLocation;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class DeliveryLocationForm extends FormModel
{
    private mixed $date_created = '';
    private mixed $date_modified = '';

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
    
    public static function show(DeliveryLocation $del): self
    {
        $form = new self();
        $form->date_created = $del->getDateCreated();
        $form->date_modified = $del->getDateModified();
        $form->client_id = (string) $del->getClientId();
        $form->name = $del->getName();
        $form->building_number = $del->getBuildingNumber();
        $form->address_1 = $del->getAddress1();
        $form->address_2 = $del->getAddress2();
        $form->city = $del->getCity();
        $form->state = $del->getState();
        $form->zip = $del->getZip();
        $form->country = $del->getCountry();
        // 13 digit code
        $form->global_location_number = $del->getGlobalLocationNumber();
        // the key of the array is saved
        $form->electronic_address_scheme = $del->getElectronicAddressScheme();
        return $form;
    }
    
    public function getDateCreated(): DateTimeImmutable
    {
        
        if ($this->date_created instanceof DateTimeImmutable) {
            return $this->date_created;
        }
        if (is_string($this->date_created) && $this->date_created !== '') {
            return new DateTimeImmutable($this->date_created);
        }
        return new DateTimeImmutable('now');
    }

    public function getDateModified(): DateTimeImmutable
    {
        if ($this->date_modified instanceof DateTimeImmutable) {
            return $this->date_modified;
        }
        if (is_string($this->date_modified) && $this->date_modified !== '') {
            return new DateTimeImmutable($this->date_modified);
        }
        return new DateTimeImmutable('now');
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
