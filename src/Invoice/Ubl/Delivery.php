<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use DateTime;

class Delivery implements XmlSerializable {

  private ?DateTime $actualDeliveryDate;
  private array $deliveryLocationID_scheme;
  private ?Address $deliveryLocation;
  private ?Party $deliveryParty;

  public function __construct(?DateTime $actualDeliveryDate, array $deliveryLocationID_scheme, ?Address $deliveryLocation, ?Party $deliveryParty) {
    $this->actualDeliveryDate = $actualDeliveryDate;
    $this->deliveryLocationID_scheme = $deliveryLocationID_scheme;
    $this->deliveryLocation = $deliveryLocation;
    $this->deliveryParty = $deliveryParty;
  }

  /**
   *
   * @param Writer $writer
   * @return void
   */
  public function xmlSerialize(Writer $writer): void {
    if ($this->actualDeliveryDate !== null) {
      $writer->write([
        Schema::CBC . 'ActualDeliveryDate' => $this->actualDeliveryDate->format('Y-m-d')
      ]);
    }
    if ($this->deliveryLocation !== null) {
      $writer->write([
                 ['name' => Schema::CAC . 'DeliveryLocation',
                  'value' => [[
                    'name' => Schema::CBC . 'ID',
                    'value' => [$this->deliveryLocationID_scheme['ID']],
                    'attributes' => $this->deliveryLocationID_scheme['attributes']],
                    ['name' => Schema::CAC . 'Address',
                    'value' => $this->deliveryLocation]
                  ]   
      ]]);
    }
    if ($this->deliveryParty !== null) {
      $writer->write([
        Schema::CAC . 'DeliveryParty' => $this->deliveryParty
      ]);
    }
  }

}
