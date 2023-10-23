<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryLocation;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class DeliveryLocationForm extends FormModel {

  private ?string $client_id = '';
  private ?string $name = '';
  private ?string $address_1 = '';
  private ?string $address_2 = '';
  private ?string $city = '';
  private ?string $state = '';
  private ?string $zip = '';
  private ?string $country = '';
  private ?string $global_location_number = '';
  private ?string $electronic_address_scheme = '';

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

  public function getRules(): array {
    return [
      'current' => [new Required()],
      'name' => [new Required()],
      'address_1' => [new Required()],
      'address_2' => [new Required()],
      'city' => [new Required()],
      'state' => [new Required()],
      'zip' => [new Required()],
      'country' => [new Required()],
    ];
  }

}
