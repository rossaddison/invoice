<?php

declare(strict_types=1);

namespace App\Invoice\PostalAddress;

use App\Infrastructure\Persistence\PostalAddress\PostalAddress;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class PostalAddressForm extends FormModel
{
    #[Required]
    #[Length(min: 0, max: 50)]
    private ?string $street_name = '';
    #[Required]
    #[Length(min: 0, max: 50)]
    private ?string $additional_street_name = '';

    #[Length(min: 0, max: 4, skipOnEmpty: true)]
    private ?string $building_number = '';
    #[Required]
    #[Length(min: 0, max: 50)]
    private ?string $city_name = '';
    #[Required]
    #[Length(min: 0, max: 7)]
    private ?string $postalzone = '';
    #[Required]
    #[Length(min: 0, max: 50)]
    private ?string $countrysubentity = '';
    #[Required]
    #[Length(min: 0, max: 50)]
    private ?string $country = '';
    private ?int $client_id = null;    
    
    public static function show(
        PostalAddress $postalAddress,
        ?int $client_id   
    ): self
    {
        $form = new self();
        $form->street_name = $postalAddress->getStreetName();
        $form->additional_street_name = $postalAddress->getAdditionalStreetName();
        $form->building_number = $postalAddress->getBuildingNumber();
        $form->city_name = $postalAddress->getCityName();
        $form->postalzone = $postalAddress->getPostalzone();
        $form->countrysubentity = $postalAddress->getCountrysubentity();
        $form->country = $postalAddress->getCountry();
        $form->client_id = $postalAddress->getClientId() > 0 ?
            $postalAddress->getClientId() : $client_id;
        return $form;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function getStreetName(): ?string
    {
        return $this->street_name;
    }

    public function getAdditionalStreetName(): ?string
    {
        return $this->additional_street_name;
    }

    public function getBuildingNumber(): ?string
    {
        return $this->building_number;
    }

    public function getCityName(): ?string
    {
        return $this->city_name;
    }

    public function getPostalzone(): ?string
    {
        return $this->postalzone;
    }

    public function getCountrysubentity(): ?string
    {
        return $this->countrysubentity;
    }

    public function getCountry(): ?string
    {
        return $this->country;
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
