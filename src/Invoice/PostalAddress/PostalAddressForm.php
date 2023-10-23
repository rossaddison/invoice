<?php
declare(strict_types=1);

namespace App\Invoice\PostalAddress;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class PostalAddressForm extends FormModel
{   
    private ?int    $id=null;
    private ?int    $client_id=null;
    private ?string $street_name='';
    private ?string $additional_street_name='';
    private ?string $building_number='';
    private ?string $city_name='';
    private ?string $postalzone='';
    private ?string $countrysubentity='';
    private ?string $country='';

    public function getId() : int|null
    {
      return $this->id;
    }
    
    public function getClient_id() : int|null
    {
      return $this->client_id;
    }

    public function getStreet_name() : string|null
    {
      return $this->street_name;
    }

    public function getAdditional_street_name() : string|null
    {
      return $this->additional_street_name;
    }

    public function getBuilding_number() : string|null
    {
      return $this->building_number;
    }

    public function getCity_name() : string|null
    {
      return $this->city_name;
    }

    public function getPostalzone() : string|null
    {
      return $this->postalzone;
    }

    public function getCountrysubentity() : string|null
    {
      return $this->countrysubentity;
    }

    public function getCountry() : string|null
    {
      return $this->country;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }

    public function getRules(): array    {
      return [
        'client_id' => [new Required()],  
        'street_name' => [new Required()],
        'additional_street_name' => [new Required()],
        'building_number' => [new Required()],
        'city_name' => [new Required()],
        'postalzone' => [new Required()],
        'countrysubentity' => [new Required()],
        'country' => [new Required()],
      ];
}
}
