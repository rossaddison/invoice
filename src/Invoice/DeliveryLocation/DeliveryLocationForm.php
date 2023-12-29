<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryLocation;

use App\Invoice\Entity\DeliveryLocation;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class DeliveryLocationForm extends FormModel {

  private ?string $client_id = '';
  #[Required]
  private ?string $name = '';
  #[Required]
  private ?string $address_1 = '';
  #[Required]
  private ?string $address_2 = '';
  #[Required]
  private ?string $city = '';
  #[Required]
  private ?string $state = '';
  #[Required]
  private ?string $zip = '';
  #[Required]
  private ?string $country = '';
  private ?string $global_location_number = '';
  private ?string $electronic_address_scheme = '';
  
  public function __construct(DeliveryLocation $del) {
    $this->client_id = $del->getClient_id();
    $this->name = $del->getName();
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

  public function getClient_id(): string|null {
    return $this->client_id;
  }

  public function getName(): string|null {
    return $this->name;
  }

  public function getAddress_1(): string|null {
    return $this->address_1;
  }

  public function getAddress_2(): string|null {
    return $this->address_2;
  }

  public function getCity(): string|null {
    return $this->city;
  }

  public function getState(): string|null {
    return $this->state;
  }

  public function getZip(): string|null {
    return $this->zip;
  }

  public function getCountry(): string|null {
    return $this->country;
  }

  public function getGlobal_location_number(): string|null {
    return $this->global_location_number;
  }

  public function getElectronic_address_scheme(): string|null {
    return $this->electronic_address_scheme;
  }

  /**
   * @return string
   * @psalm-return ''
   */
  public function getFormName(): string {
    return '';
  }
}
